<?php
/**
 * Area riservata: elenco pratiche, download PDF, export CSV,
 * verifica integrità del registro, cancellazione dati (art. 17).
 */
require __DIR__ . '/includes/bootstrap.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$hash = (string) cfg('admin_password_hash');
$ip = Security::clientIp();
$loginError = '';

if ($hash === '') {
    page_header('Area riservata');
    echo '<main id="main" class="page"><div class="container"><div class="card"><h1>Area riservata disattivata</h1>'
        . '<p>Per attivarla genera l\'hash della password con<br><code>php -r "echo password_hash(\'LaTuaPassword\', PASSWORD_DEFAULT);"</code><br>'
        . 'e inseriscilo come <code>admin_password_hash</code> in <code>includes/config.local.php</code>.</p></div></div></main>';
    page_footer();
    exit;
}

// --- Login / logout ------------------------------------------------------
if (isset($_POST['login'])) {
    if (!Security::checkCsrf($_POST['csrf_token'] ?? null)) {
        $loginError = 'Sessione scaduta, riprova.';
    } elseif (!Security::rateLimit('admin-login', $ip, 8, 900)) {
        $loginError = 'Troppi tentativi. Riprova tra 15 minuti.';
    } elseif (password_verify((string) ($_POST['password'] ?? ''), $hash)) {
        session_regenerate_id(true);
        $_SESSION['admin'] = time();
        app_log('Accesso admin da ' . $ip);
        header('Location: admin.php', true, 303);
        exit;
    } else {
        $loginError = 'Password errata.';
        app_log('Login admin fallito da ' . $ip, 'WARN');
    }
}
if (isset($_POST['logout']) && Security::checkCsrf($_POST['csrf_token'] ?? null)) {
    $_SESSION = [];
    session_regenerate_id(true);
    header('Location: admin.php', true, 303);
    exit;
}

$logged = !empty($_SESSION['admin']) && time() - $_SESSION['admin'] < 7200;
if ($logged) {
    $_SESSION['admin'] = time(); // scadenza dopo 2 ore di inattività
}

if (!$logged) {
    page_header('Accesso area riservata');
    ?>
    <main id="main" class="page"><div class="container"><div class="card" style="max-width:440px;margin:0 auto">
        <h1>Area riservata</h1>
        <?php if ($loginError): ?><div class="notice ko" role="alert"><?= e($loginError) ?></div><?php endif; ?>
        <form method="post" class="simple-form">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password" autofocus>
            <input type="hidden" name="csrf_token" value="<?= e(Security::csrfToken()) ?>">
            <button class="btn btn-primary" type="submit" name="login" value="1"><?= icon('lock') ?> Accedi</button>
        </form>
    </div></div></main>
    <?php
    page_footer();
    exit;
}

// --- Azioni autenticate --------------------------------------------------
$validId = function ($id) {
    return is_string($id) && preg_match('/^CNS-\d{8}-[A-Z0-9]{8}$/', $id);
};

if (isset($_GET['pdf']) && $validId($_GET['pdf'])) {
    $file = Storage::path('pdfs') . '/' . $_GET['pdf'] . '.pdf';
    if (!is_file($file)) {
        http_response_code(404);
        exit('PDF non disponibile (cancellato o mai generato).');
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Consenso_' . $_GET['pdf'] . '.pdf"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

$csvCell = function ($v) {
    $v = is_bool($v) ? ($v ? 'SI' : 'NO') : (string) $v;
    // Protezione da CSV/formula injection in Excel
    if ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false) {
        $v = "'" . $v;
    }
    return $v;
};

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="consensi_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM per Excel
    fputcsv($out, ['ID', 'Data', 'Nome', 'Cognome', 'Email', 'Cellulare', 'Privacy', 'Marketing', 'Marketing confermato', 'Revocato', 'Ambito revoca', 'Dati cancellati', 'IP', 'Versione testi', 'SHA-256 PDF', 'Messaggio'], ';');
    foreach (ConsentLedger::consents() as $c) {
        $r = $c['record'] ?: [];
        $s = $c['status'];
        fputcsv($out, array_map($csvCell, [
            $c['id'], $c['timestamp'], $r['nome'] ?? '', $r['cognome'] ?? '', $r['email'] ?? '', $r['telefono'] ?? '',
            $c['privacy'], $c['marketing'], $s['confirmed'] ?: '', $s['withdrawn'] ? $s['withdrawn']['at'] : '',
            $s['withdrawn'] ? $s['withdrawn']['scope'] : '', $s['erased'] ?: '', $r['ip'] ?? '', $c['consent_version'], $c['pdf_sha256'], $r['messaggio'] ?? '',
        ]), ';');
    }
    fclose($out);
    exit;
}

$notice = '';
if (isset($_POST['erase']) && Security::checkCsrf($_POST['csrf_token'] ?? null) && $validId($_POST['erase'])) {
    if (($_POST['confirm'] ?? '') === $_POST['erase']) {
        ConsentLedger::erase($_POST['erase'], 'Cancellazione manuale da area admin');
        app_log('Dati cancellati per ' . $_POST['erase']);
        $notice = 'Dati personali e PDF della pratica ' . $_POST['erase'] . ' cancellati. Nel registro restano solo le impronte.';
    } else {
        $notice = 'Per cancellare, riscrivi esattamente il codice della pratica.';
    }
}

$chain = ConsentLedger::verify();
$consents = ConsentLedger::consents();
$q = trim((string) ($_GET['q'] ?? ''));
if ($q !== '') {
    $consents = array_values(array_filter($consents, function ($c) use ($q) {
        $hay = $c['id'] . ' ' . implode(' ', array_map('strval', array_intersect_key($c['record'] ?: [], array_flip(['nome', 'cognome', 'email', 'telefono']))));
        return mb_stripos($hay, $q) !== false;
    }));
}
$total = count($consents);
$mk = count(array_filter($consents, function ($c) { return $c['marketing'] && $c['status']['confirmed'] && !$c['status']['withdrawn']; }));

page_header('Registro consensi – area riservata');
?>
<main id="main" class="page">
    <div class="container" style="max-width:1200px">
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                <h1 style="margin:0">Registro consensi</h1>
                <form method="post"><input type="hidden" name="csrf_token" value="<?= e(Security::csrfToken()) ?>"><button class="btn btn-outline" name="logout" value="1">Esci</button></form>
            </div>

            <?php if ($notice): ?><div class="notice info"><?= e($notice) ?></div><?php endif; ?>

            <?php if ($chain['ok']): ?>
                <div class="notice ok">&#10004; Registro integro: <?= (int) $chain['count'] ?> eventi verificati, catena di hash intatta.</div>
            <?php else: ?>
                <div class="notice ko"><strong>&#10008; ATTENZIONE: registro alterato.</strong> <?= e($chain['error']) ?>. Confronta con gli hash ricevuti via email.</div>
            <?php endif; ?>

            <p><b><?= $total ?></b> pratiche<?= $q !== '' ? ' trovate' : '' ?> · <b><?= $mk ?></b> contatti marketing attivi (confermati e non revocati)</p>

            <form method="get" class="actions" style="justify-content:flex-start;margin:12px 0 18px">
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cerca nome, email, telefono, ID" style="padding:10px 12px;border:1.5px solid var(--gray-300);border-radius:10px;min-width:260px;font:inherit">
                <button class="btn btn-outline" type="submit"><?= icon('search') ?> Cerca</button>
                <a class="btn btn-primary" href="admin.php?export=1"><?= icon('file') ?> Esporta CSV</a>
            </form>

            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Data</th><th>ID</th><th>Interessato</th><th>Contatti</th><th>Privacy</th><th>Marketing</th><th>Stato</th><th>Email</th><th>PDF</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($consents as $c): $r = $c['record']; $s = $c['status']; ?>
                        <tr>
                            <td><?= e((new DateTime($c['timestamp']))->format('d/m/Y H:i')) ?></td>
                            <td class="mono"><?= e($c['id']) ?></td>
                            <td><?= $r ? e($r['nome'] . ' ' . $r['cognome']) : '<i>cancellato</i>' ?>
                                <?php if ($r && !$c['record_ok']): ?><br><span class="pill ko">dati alterati!</span><?php endif; ?></td>
                            <td><?= $r ? e($r['email']) . '<br>' . e($r['telefono']) : '—' ?></td>
                            <td><span class="pill <?= $c['privacy'] ? 'ok' : 'ko' ?>"><?= $c['privacy'] ? 'Sì' : 'No' ?></span></td>
                            <td><?php if (!$c['marketing']): ?><span class="pill ko">No</span>
                                <?php elseif ($s['confirmed']): ?><span class="pill ok">Confermato</span>
                                <?php else: ?><span class="pill wait">Da confermare</span><?php endif; ?></td>
                            <td><?php if ($s['erased']): ?><span class="pill ko">Dati cancellati</span>
                                <?php elseif ($s['withdrawn']): ?><span class="pill ko">Revocato<?= $s['withdrawn']['scope'] === 'all' ? ' (tutto)' : ' (mkt)' ?></span>
                                <?php if (!empty($s['withdrawn']['erase_request'])): ?><br><span class="pill wait">Chiede cancellazione</span><?php endif; ?>
                                <?php else: ?><span class="pill ok">Attivo</span><?php endif; ?></td>
                            <td><?php $d = $s['delivery']; if ($d): ?>
                                <span class="pill <?= $d['admin_mail'] ? 'ok' : 'ko' ?>" title="Email al titolare">T</span>
                                <?php if ($d['user_mail'] !== null): ?><span class="pill <?= $d['user_mail'] ? 'ok' : 'ko' ?>" title="Email all'utente">U</span><?php endif; ?>
                                <?php endif; ?></td>
                            <td><?php if ($r): ?><a href="admin.php?pdf=<?= e(rawurlencode($c['id'])) ?>">Scarica</a><?php endif; ?></td>
                            <td><?php if ($r): ?>
                                <details><summary style="cursor:pointer;color:var(--error)">Cancella</summary>
                                    <form method="post" style="display:grid;gap:6px;margin-top:6px">
                                        <input type="hidden" name="csrf_token" value="<?= e(Security::csrfToken()) ?>">
                                        <input type="hidden" name="erase" value="<?= e($c['id']) ?>">
                                        <input name="confirm" placeholder="Riscrivi l'ID" style="padding:6px;border:1px solid var(--gray-300);border-radius:6px">
                                        <button class="btn btn-outline" style="padding:6px 10px">Conferma</button>
                                    </form></details>
                                <?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$consents): ?><tr><td colspan="10" style="text-align:center;color:var(--gray-500)">Nessuna pratica.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<?php page_footer(); ?>
