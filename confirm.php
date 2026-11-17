<?php
/**
 * Double opt-in del consenso marketing.
 * Il link nell'email apre questa pagina; la conferma avviene con un click
 * (POST), così gli scanner antispam che "aprono" i link non confermano da soli.
 */
require __DIR__ . '/includes/bootstrap.php';

$id = (string) ($_REQUEST['id'] ?? '');
$t  = (string) ($_REQUEST['t'] ?? '');
$valid = preg_match('/^CNS-\d{8}-[A-Z0-9]{8}$/', $id)
    && hash_equals(hash_hmac('sha256', 'doi|' . $id, Storage::secretKey()), $t);

$state = 'invalid';
$record = $valid ? ConsentLedger::findConsent($id, true) : null;

if ($record && !empty($record['marketing']) && $record['record']) {
    $already = (bool) $record['status']['confirmed'];
    $revoked = (bool) $record['status']['withdrawn'];
    $person = $record['record'];
    if ($revoked) {
        $state = 'revoked';
    } elseif ($already) {
        $state = 'already';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::checkCsrf($_POST['csrf_token'] ?? null)) {
        $ip = Security::clientIp();
        $ua = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 400);
        $ev = ConsentLedger::append('marketing_confirmed', [
            'ref'      => $id,
            'evidence' => hash_hmac('sha256', $ip . '|' . $ua, Storage::secretKey()),
        ]);
        // IP e browser della conferma: nel file dati (cancellabile), non nel registro
        $person['double_opt_in'] = ['at' => $ev['at'], 'ip' => $ip, 'user_agent' => $ua, 'event_hash' => $ev['hash']];
        ConsentLedger::saveRecord($person);
        $ev['ip'] = $ip;
        Mailer::send(cfg('admin_email'), 'Consenso marketing confermato ' . $id, Mailer::eventTemplate('Double opt-in confermato', [
            'ID pratica' => $id,
            'Interessato' => $person['nome'] . ' ' . $person['cognome'] . ' <' . $person['email'] . '>',
            'Data e ora' => date('d/m/Y H:i:s T'),
            'IP' => $ev['ip'],
            'Hash evento' => $ev['hash'],
        ]));
        $state = 'confirmed';
    } else {
        $state = 'ask';
    }
}

page_header('Conferma iscrizione – ' . cfg('mail_from_name'));
?>
<main id="main" class="page">
    <div class="container">
        <div class="card result">
            <?php if ($state === 'ask'): ?>
                <div class="result-icon ok"><?= icon('mail') ?></div>
                <h1>Conferma la tua iscrizione</h1>
                <p>Clicca sul pulsante per confermare che desideri ricevere comunicazioni commerciali da <?= e(cfg('company.name')) ?>. Potrai revocare il consenso in qualsiasi momento.</p>
                <form method="post" class="actions">
                    <input type="hidden" name="id" value="<?= e($id) ?>">
                    <input type="hidden" name="t" value="<?= e($t) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(Security::csrfToken()) ?>">
                    <button class="btn btn-primary" type="submit"><?= icon('check') ?> Confermo</button>
                </form>
            <?php elseif ($state === 'confirmed' || $state === 'already'): ?>
                <div class="result-icon ok"><?= icon('check') ?></div>
                <h1><?= $state === 'confirmed' ? 'Iscrizione confermata' : 'Iscrizione già confermata' ?></h1>
                <p>Grazie! La conferma è stata registrata nel nostro archivio dei consensi.</p>
            <?php elseif ($state === 'revoked'): ?>
                <div class="result-icon warn"><?= icon('alert') ?></div>
                <h1>Consenso revocato</h1>
                <p>Per questa pratica risulta una revoca del consenso: non è possibile riattivarlo da questo link. Compila di nuovo il modulo se desideri iscriverti.</p>
            <?php else: ?>
                <div class="result-icon ko"><?= icon('x') ?></div>
                <h1>Link non valido</h1>
                <p>Il link di conferma non è valido o è incompleto. Copialo per intero dall'email ricevuta.</p>
            <?php endif; ?>
            <div class="actions" style="margin-top:18px"><a href="index.php" class="btn btn-outline"><?= icon('home') ?> Home</a></div>
        </div>
    </div>
</main>
<?php page_footer(); ?>
