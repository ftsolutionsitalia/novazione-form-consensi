<?php
/**
 * Riceve il modulo, valida, genera l'attestazione PDF firmata, registra
 * l'evento nel registro a catena di hash e invia le email.
 * Risponde in JSON alle richieste fetch() e con redirect (PRG) altrimenti.
 */

require __DIR__ . '/includes/bootstrap.php';

$wantsJson = stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

function respond($ok, array $payload, $wantsJson)
{
    if ($wantsJson) {
        http_response_code($ok ? 200 : ($payload['status'] ?? 422));
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        unset($payload['status']);
        echo json_encode(['ok' => $ok] + $payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($ok) {
        header('Location: success.php', true, 303);
    } elseif (!empty($payload['errors'])) {
        flash('errors', $payload['errors']);
        flash('old', $payload['old'] ?? []);
        header('Location: index.php#contact', true, 303);
    } else {
        flash('error', $payload['message'] ?? 'Errore imprevisto.');
        header('Location: error.php', true, 303);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php', true, 303);
    exit;
}

/** Testo pulito: niente caratteri di controllo (blocca header injection), spazi normalizzati. */
function clean_line($v, $max)
{
    $v = is_string($v) ? $v : '';
    $v = preg_replace('/[\x00-\x1F\x7F\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', ' ', $v);
    $v = trim(preg_replace('/\s+/u', ' ', (string) $v));
    return mb_substr($v, 0, $max, 'UTF-8');
}

function clean_text($v, $max)
{
    $v = is_string($v) ? str_replace(["\r\n", "\r"], "\n", $v) : '';
    $v = preg_replace('/[\x00-\x08\x0B-\x1F\x7F\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', $v);
    return mb_substr(trim((string) $v), 0, $max, 'UTF-8');
}

/** Normalizza un numero di cellulare in formato internazionale (+39 di default). */
function normalize_phone($raw)
{
    $p = preg_replace('/[\s\-\.\/\(\)]/', '', (string) $raw);
    if (strpos($p, '00') === 0) {
        $p = '+' . substr($p, 2);
    }
    if ($p !== '' && $p[0] !== '+') {
        $p = '+39' . $p;
    }
    return $p;
}

try {
    $ip = Security::clientIp();

    // --- Controlli anti-abuso --------------------------------------------
    if (!Security::checkCsrf($_POST['csrf_token'] ?? null)) {
        respond(false, [
            'status'  => 403,
            'message' => 'Sessione scaduta: ricarica la pagina e riprova.',
            // Token nuovi per un nuovo tentativo automatico: una pagina di un altro sito
            // non può leggere questa risposta (same-origin policy), quindi la protezione resta.
            'refresh' => ['csrf_token' => Security::csrfToken(), 'form_ts' => Security::formIssuedToken((int) cfg('min_fill_seconds', 3))],
        ], $wantsJson);
    }

    $isBot = trim((string) ($_POST['website'] ?? '')) !== ''
        || !Security::checkFormTiming($_POST['form_ts'] ?? null, (int) cfg('min_fill_seconds', 3));
    if ($isBot) {
        // Risposta finta di successo: il bot non capisce di essere stato bloccato
        app_log('Invio scartato (honeypot/tempo) da IP ' . $ip, 'WARN');
        respond(true, ['redirect' => 'success.php'], $wantsJson);
    }

    $rl = cfg('rate_limit');
    $tooMany = 'Hai inviato troppe richieste. Riprova tra un\'ora o contattaci telefonicamente.';
    // Limite largo su tutti i tentativi (anche errati), stretto solo sugli invii validi
    if (!Security::rateLimit('attempt', $ip, 10 * (int) $rl['max'], (int) $rl['window'])) {
        respond(false, ['status' => 429, 'message' => $tooMany], $wantsJson);
    }

    // --- Validazione ------------------------------------------------------
    $nome      = clean_line($_POST['nome'] ?? '', 60);
    $cognome   = clean_line($_POST['cognome'] ?? '', 60);
    $email     = mb_strtolower(clean_line($_POST['email'] ?? '', 254), 'UTF-8');
    $telefono  = normalize_phone(clean_line($_POST['telefono'] ?? '', 30));
    $messaggio = clean_text($_POST['messaggio'] ?? '', 2000);
    $privacy   = ($_POST['privacy'] ?? '') === '1';
    $marketing = ($_POST['marketing'] ?? '') === '1';

    $errors = [];
    $namePattern = "/^[\\p{L}\\p{M}][\\p{L}\\p{M}' .\\-]{1,59}$/u";
    if (!preg_match($namePattern, $nome)) {
        $errors['nome'] = 'Inserisci un nome valido (almeno 2 lettere).';
    }
    if (!preg_match($namePattern, $cognome)) {
        $errors['cognome'] = 'Inserisci un cognome valido (almeno 2 lettere).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/\.[a-z]{2,}$/i', $email)) {
        $errors['email'] = 'Inserisci un indirizzo email valido.';
    }
    if (!preg_match('/^\+[1-9]\d{7,14}$/', $telefono)) {
        $errors['telefono'] = 'Inserisci un numero di cellulare valido (es. 333 123 4567).';
    }
    if (!$privacy) {
        $errors['privacy'] = 'Per inviare la richiesta è necessario accettare l\'informativa privacy.';
    }

    if ($errors) {
        respond(false, [
            'errors'  => $errors,
            'message' => 'Controlla i campi evidenziati.',
            'old'     => compact('nome', 'cognome', 'email', 'messaggio') + ['telefono' => $_POST['telefono'] ?? '', 'marketing' => $marketing],
        ], $wantsJson);
    }

    if (!Security::rateLimit('submit', $ip, (int) $rl['max'], (int) $rl['window'])) {
        respond(false, ['status' => 429, 'message' => $tooMany], $wantsJson);
    }

    // --- Costruzione del record ------------------------------------------
    $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $sameHost = parse_url($referer, PHP_URL_HOST) === parse_url(base_url(), PHP_URL_HOST);
    $sourceUrl = $sameHost ? clean_line(strtok($referer, '#'), 300) : '';
    if ($sourceUrl === '') {
        $sourceUrl = base_url() . '/index.php';
    }

    $record = [
        'id'              => ConsentLedger::newId(),
        'timestamp'       => (new DateTime())->format('Y-m-d\TH:i:sP'),
        'nome'            => $nome,
        'cognome'         => $cognome,
        'email'           => $email,
        'telefono'        => $telefono,
        'messaggio'       => $messaggio,
        'privacy'         => $privacy,
        'marketing'       => $marketing,
        'consent_version' => cfg('consents.version'),
        'privacy_text'    => cfg('consents.privacy'),
        'marketing_text'  => cfg('consents.marketing'),
        'ip'              => $ip,
        'user_agent'      => clean_line($_SERVER['HTTP_USER_AGENT'] ?? 'n/d', 400),
        'source_url'      => $sourceUrl,
    ];

    $dataHash  = PDFGenerator::dataHash($record);
    $verifyUrl = base_url() . '/verify.php?id=' . rawurlencode($record['id']);
    $pdf       = PDFGenerator::generate($record, $dataHash, $verifyUrl);

    ConsentLedger::saveRecord($record);
    // Nel registro solo stati e impronte: niente dati personali in chiaro
    $entry = ConsentLedger::append('consent', [
        'id'              => $record['id'],
        'timestamp'       => $record['timestamp'],
        'privacy'         => $privacy,
        'marketing'       => $marketing,
        'marketing_confirmed' => $marketing && !cfg('double_opt_in'),
        'consent_version' => $record['consent_version'],
        'email_key'       => ConsentLedger::emailKey($email),
        'data_hmac'       => $dataHash,
        'pdf_sha256'      => $pdf['sha256'],
        'pdf_signed'      => $pdf['signed'],
    ]);

    app_log('Consenso registrato ' . $record['id'] . ' (evento ' . $entry['seq'] . ')');

    // --- Email -----------------------------------------------------------
    $attachment = [$pdf['path'] => 'Consenso_' . $record['id'] . '.pdf'];

    $adminOk = Mailer::send(
        cfg('admin_email'),
        'Nuovo consenso ' . $record['id'] . ' – ' . $nome . ' ' . $cognome,
        Mailer::adminTemplate($record, $entry, $pdf['sha256'], $dataHash, $verifyUrl),
        $attachment,
        $email
    );

    $userOk = null;
    if (cfg('send_user_copy')) {
        $confirmUrl = ($marketing && cfg('double_opt_in'))
            ? base_url() . '/confirm.php?id=' . rawurlencode($record['id']) . '&t=' . Security::doiToken($record['id'])
            : null;
        $userOk = Mailer::send(
            $email,
            'Abbiamo ricevuto la tua richiesta – ' . cfg('company.name'),
            Mailer::userTemplate($record, $confirmUrl, base_url() . '/revoca.php'),
            $attachment
        );
    }

    ConsentLedger::append('delivery', [
        'ref'        => $record['id'],
        'admin_mail' => $adminOk,
        'user_mail'  => $userOk,
    ]);

    // Nuovo token CSRF dopo un invio riuscito (evita doppio invio)
    unset($_SESSION['csrf']);
    flash('consent_id', $record['id']);
    flash('user_mail', $userOk);

    respond(true, ['redirect' => 'success.php', 'id' => $record['id']], $wantsJson);
} catch (Throwable $ex) {
    app_log('Errore elaborazione: ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine(), 'ERROR');
    respond(false, ['status' => 500, 'message' => 'Si è verificato un errore tecnico e la richiesta non è stata registrata. Riprova tra qualche minuto o contattaci telefonicamente.'], $wantsJson);
}
