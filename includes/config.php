<?php
/**
 * Configurazione Novazione Form Consensi
 *
 * File di configurazione principale dell'applicazione
 */

// Impedisci accesso diretto
if (!defined('NOVAZIONE_APP')) {
    die('Accesso negato');
}

// Configurazione email
define('EMAIL_DESTINATARIO', '73.leonardo.v@gmail.com');
define('EMAIL_MITTENTE', 'noreply@novazione.it');
define('EMAIL_NOME_MITTENTE', 'Novazione');

// Configurazione azienda
define('AZIENDA_NOME', 'Novazione S.r.l.');
define('AZIENDA_INDIRIZZO', 'Via Roma 123, 20121 Milano (MI)');
define('AZIENDA_PIVA', 'IT00000000000');
define('AZIENDA_EMAIL', 'info@novazione.it');
define('AZIENDA_TELEFONO', '+39 02 1234567');

// Configurazione percorsi
define('ROOT_PATH', dirname(__DIR__));
define('PDF_PATH', ROOT_PATH . '/pdfs/');
define('VENDOR_PATH', ROOT_PATH . '/vendor/');

// Configurazione sicurezza
define('HASH_ALGORITHM', 'sha256');
define('SECRET_KEY', 'NovazioneSaltKey2024!SecureHash'); // Cambiare in produzione

// Configurazione debug (disattivare in produzione)
define('DEBUG_MODE', false);

// Timezone
date_default_timezone_set('Europe/Rome');

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Funzione per logging
function logMessage($message, $level = 'INFO') {
    if (!DEBUG_MODE) return;

    $logFile = ROOT_PATH . '/logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;

    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }

    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
