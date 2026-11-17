<?php
/**
 * Intestazione e piè di pagina comuni.
 */

if (!defined('NOVAZIONE_APP')) {
    http_response_code(403);
    exit;
}

function page_header($title, $description = '', $bodyClass = '')
{
    $brand = cfg('mail_from_name', 'Novazione');
    $ver = @filemtime(APP_ROOT . '/assets/css/style.css') ?: 1;
    ?><!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <?php if ($description): ?><meta name="description" content="<?= e($description) ?>"><?php endif; ?>
    <meta name="theme-color" content="#1e40af">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231d4ed8'/%3E%3Cpath d='M9 22V10l14 12V10' stroke='white' stroke-width='3' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= (int) $ver ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#main">Vai al contenuto</a>
<header class="header" id="top">
    <nav class="nav-container" aria-label="Navigazione principale">
        <a class="logo" href="index.php" aria-label="<?= e($brand) ?> – home">
            <span class="logo-icon"><?= icon('cube') ?></span>
            <span class="logo-text"><?= e($brand) ?></span>
        </a>
        <ul class="nav-links">
            <li><a href="index.php#services">Servizi</a></li>
            <li><a href="index.php#contact" class="nav-cta">Contattaci</a></li>
        </ul>
    </nav>
</header>
<?php
}

function page_footer()
{
    $c = cfg('company');
    $brand = cfg('mail_from_name', 'Novazione');
    $ver = @filemtime(APP_ROOT . '/assets/js/main.js') ?: 1;
    ?>
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-brand">
                <div class="logo">
                    <span class="logo-icon"><?= icon('cube') ?></span>
                    <span class="logo-text"><?= e($brand) ?></span>
                </div>
                <p><?= e($c['name']) ?> · <?= e($c['address']) ?><br>P.IVA <?= e($c['vat']) ?></p>
            </div>
            <div class="footer-links">
                <h2 class="footer-title">Privacy</h2>
                <ul>
                    <li><a href="privacy.php">Informativa privacy</a></li>
                    <li><a href="privacy.php#cookie">Cookie</a></li>
                    <li><a href="revoca.php">Revoca consenso</a></li>
                    <li><a href="verify.php">Verifica attestazione</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= e($c['name']) ?>. Questo sito non usa cookie di profilazione né servizi di terze parti.</p>
        </div>
    </div>
</footer>
<script src="assets/js/main.js?v=<?= (int) $ver ?>" defer></script>
</body>
</html>
<?php
}
