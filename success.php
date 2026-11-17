<?php
require __DIR__ . '/includes/bootstrap.php';
$id = flash('consent_id');
$userMail = flash('user_mail');
page_header('Richiesta inviata – ' . cfg('mail_from_name'));
?>
<main id="main" class="page">
    <div class="container">
        <div class="card result">
            <div class="result-icon ok"><?= icon('check') ?></div>
            <h1>Richiesta inviata!</h1>
            <p>Grazie per averci contattato. Ti ricontatteremo entro 24 ore lavorative.</p>
            <?php if ($id): ?>
                <p>Codice della tua pratica:</p>
                <span class="badge-id"><?= e($id) ?></span>
            <?php endif; ?>
            <ul class="checklist">
                <li><?= icon('check') ?> Consenso registrato con data, ora e indirizzo IP</li>
                <li><?= icon('check') ?> Attestazione PDF generata, cifrata e firmata digitalmente</li>
                <?php if ($userMail === false): ?>
                    <li><?= icon('alert') ?> Non siamo riusciti a inviarti la copia via email: la riceverai su richiesta.</li>
                <?php elseif (cfg('send_user_copy')): ?>
                    <li><?= icon('check') ?> Copia dell'attestazione inviata al tuo indirizzo email (controlla anche lo spam)</li>
                <?php endif; ?>
            </ul>
            <div class="actions">
                <a href="index.php" class="btn btn-primary"><?= icon('home') ?> Torna alla home</a>
                <?php if ($id): ?>
                    <a href="verify.php?id=<?= e(rawurlencode($id)) ?>" class="btn btn-outline"><?= icon('search') ?> Verifica attestazione</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php page_footer(); ?>
