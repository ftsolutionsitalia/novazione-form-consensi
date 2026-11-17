<?php
require __DIR__ . '/includes/bootstrap.php';
$message = flash('error') ?: 'Non siamo riusciti a completare la richiesta.';
$c = cfg('company');
http_response_code(400);
page_header('Errore – ' . cfg('mail_from_name'));
?>
<main id="main" class="page">
    <div class="container">
        <div class="card result">
            <div class="result-icon ko"><?= icon('x') ?></div>
            <h1>Si è verificato un problema</h1>
            <div class="notice ko"><?= e($message) ?></div>
            <div class="actions">
                <a href="index.php#contact" class="btn btn-primary">Riprova</a>
                <a href="tel:<?= e(preg_replace('/[^+\d]/', '', $c['phone'])) ?>" class="btn btn-outline"><?= icon('phone') ?> <?= e($c['phone']) ?></a>
            </div>
        </div>
    </div>
</main>
<?php page_footer(); ?>
