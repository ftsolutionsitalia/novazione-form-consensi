<?php
/**
 * Revoca del consenso (art. 7.3 GDPR: revocare deve essere facile quanto prestarlo)
 * e richiesta di esercizio dei diritti (accesso, cancellazione...).
 */
require __DIR__ . '/includes/bootstrap.php';

$done = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')), 'UTF-8');
    $scope = in_array($_POST['scope'] ?? '', ['marketing', 'all'], true) ? $_POST['scope'] : 'marketing';
    $erase = !empty($_POST['erase']);
    $ip = Security::clientIp();

    if (!Security::checkCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Sessione scaduta: ricarica la pagina e riprova.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Inserisci l\'indirizzo email usato nel modulo.';
    } elseif (!Security::rateLimit('revoke', $ip, 5, 3600)) {
        $error = 'Troppe richieste. Riprova più tardi.';
    } else {
        $ids = ConsentLedger::idsByEmail($email);
        $ev = ConsentLedger::append('withdrawal', [
            'refs'       => $ids,
            'email_key'  => ConsentLedger::emailKey($email),
            'scope'      => $scope,
            'erase_request' => $erase,
            'ip_hash'    => hash_hmac('sha256', 'ip|' . $ip, Storage::secretKey()),
        ]);

        Mailer::send(cfg('admin_email'), 'Revoca consenso' . ($erase ? ' + richiesta cancellazione' : '') . ' – ' . $email, Mailer::eventTemplate('Richiesta di revoca ricevuta', [
            'Email' => $email,
            'Ambito' => $scope === 'all' ? 'Tutti i consensi (privacy e marketing)' : 'Solo marketing',
            'Cancellazione dati richiesta' => $erase ? 'SÌ – rispondere entro 30 giorni (art. 12.3 GDPR)' : 'No',
            'Pratiche collegate' => $ids ? implode(', ', $ids) : 'nessuna trovata',
            'Data e ora' => date('d/m/Y H:i:s T'),
            'IP' => $ip,
            'Hash evento' => $ev['hash'],
        ]));
        if ($ids) {
            // Notifica all'interessato: lo avvisa anche se la revoca l'ha fatta qualcun altro
            Mailer::send($email, 'Revoca del consenso registrata – ' . cfg('company.name'), Mailer::eventTemplate('Revoca registrata', [
                'Ambito' => $scope === 'all' ? 'Tutti i consensi' : 'Comunicazioni commerciali',
                'Cancellazione dati' => $erase ? 'Richiesta inoltrata al titolare' : 'Non richiesta',
                'Data e ora' => date('d/m/Y H:i:s T'),
                'Non sei stato tu?' => 'Rispondi a questa email o contatta ' . cfg('company.email'),
            ]));
        }
        app_log('Revoca registrata (evento ' . $ev['seq'] . ', pratiche: ' . count($ids) . ')');
        $done = true;
    }
}

page_header('Revoca consenso – ' . cfg('mail_from_name'));
?>
<main id="main" class="page">
    <div class="container">
        <div class="card">
            <h1>Revoca del consenso</h1>
            <?php if ($done): ?>
                <div class="notice ok">Richiesta registrata. Se l'indirizzo è presente nel nostro archivio riceverai una email di conferma. La revoca ha effetto da subito per le comunicazioni future.</div>
                <a class="btn btn-outline" href="index.php"><?= icon('home') ?> Home</a>
            <?php else: ?>
                <p>Puoi revocare in qualsiasi momento i consensi prestati. La revoca non pregiudica la liceità del trattamento effettuato prima (art. 7.3 GDPR).</p>
                <?php if ($error): ?><div class="notice ko" role="alert"><?= e($error) ?></div><?php endif; ?>
                <form method="post" class="simple-form">
                    <div>
                        <label for="email">Email usata nel modulo</label>
                        <input type="email" id="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                    <fieldset style="border:0;display:grid;gap:8px">
                        <legend style="font-weight:600;margin-bottom:6px">Cosa vuoi revocare?</legend>
                        <label style="font-weight:400"><input type="radio" name="scope" value="marketing" checked> Solo le comunicazioni commerciali (marketing)</label>
                        <label style="font-weight:400"><input type="radio" name="scope" value="all"> Tutti i consensi: non desidero più essere ricontattato</label>
                    </fieldset>
                    <label style="font-weight:400"><input type="checkbox" name="erase" value="1"> Chiedo anche la cancellazione dei miei dati personali (art. 17 GDPR)</label>
                    <input type="hidden" name="csrf_token" value="<?= e(Security::csrfToken()) ?>">
                    <div><button class="btn btn-primary" type="submit">Invia revoca</button></div>
                </form>
                <p style="margin-top:18px;font-size:.9rem;color:var(--gray-500)">Puoi anche scrivere a <a href="mailto:<?= e(cfg('company.email')) ?>"><?= e(cfg('company.email')) ?></a>.</p>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php page_footer(); ?>
