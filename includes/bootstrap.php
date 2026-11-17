<?php
/**
 * Bootstrap comune: configurazione, autoload, sessione, helper.
 */

define('NOVAZIONE_APP', true);
define('APP_ROOT', dirname(__DIR__));

$GLOBALS['APP_CONFIG'] = require __DIR__ . '/config.php';

date_default_timezone_set(cfg('timezone', 'Europe/Rome'));

if (cfg('debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

require APP_ROOT . '/vendor/autoload.php';
require __DIR__ . '/Storage.php';
require __DIR__ . '/Security.php';
require __DIR__ . '/ConsentLedger.php';
require __DIR__ . '/PDFGenerator.php';
require __DIR__ . '/Mailer.php';
require __DIR__ . '/icons.php';
require __DIR__ . '/layout.php';

ini_set('log_errors', '1');
ini_set('error_log', Storage::path('logs') . '/php-error.log');

Security::startSession();
Security::sendSecurityHeaders();

/** Legge un valore di configurazione con notazione a punti: cfg('smtp.host'). */
function cfg($key, $default = null)
{
    $value = $GLOBALS['APP_CONFIG'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/** Escape HTML. */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Log applicativo (sempre attivo, senza dati personali in chiaro). */
function app_log($message, $level = 'INFO')
{
    $line = sprintf("[%s] [%s] %s\n", date('c'), $level, $message);
    @file_put_contents(Storage::path('logs') . '/app.log', $line, FILE_APPEND | LOCK_EX);
}

/** Messaggi flash in sessione (evita di passare testo nell'URL). */
function flash($key, $value = null)
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }
    $v = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $v;
}

/** URL base dell'applicazione (es. https://dominio.it/sviluppo). */
function base_url()
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $host);
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir;
}

/** Indirizzo mittente: configurato oppure noreply@<dominio del sito>. */
function mail_from_address()
{
    $from = cfg('mail_from');
    if ($from) {
        return $from;
    }
    $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
    $host = preg_replace('/^www\./', '', $host);
    if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $host)) {
        $host = 'localhost.localdomain';
    }
    return 'noreply@' . $host;
}
