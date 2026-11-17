<?php
/**
 * Configurazione predefinita.
 *
 * NON mettere password in questo file: è versionato su Git.
 * Per sovrascrivere i valori (password SMTP, password admin, dati aziendali)
 * copia includes/config.local.example.php in includes/config.local.php
 * (quel file è escluso da Git e protetto da .htaccess).
 */

if (!defined('NOVAZIONE_APP')) {
    http_response_code(403);
    exit;
}

$config = [
    // --- Destinatario delle prove di consenso ---------------------------
    'admin_email'        => '73.leonardo.v@gmail.com',

    // Mittente: deve appartenere al dominio del sito, altrimenti
    // Gmail scarta o mette in spam (SPF/DMARC). Vuoto = noreply@<dominio>.
    'mail_from'          => '',
    'mail_from_name'     => 'Novazione',
    'send_user_copy'     => true,   // invia copia del PDF anche all'utente

    // SMTP (consigliato). Se 'host' è vuoto si usa mail() di PHP.
    'smtp' => [
        'host'       => '',
        'port'       => 587,
        'username'   => '',
        'password'   => '',
        'encryption' => 'tls',      // 'tls' (587), 'ssl' (465) o ''
    ],

    // --- Titolare del trattamento (compare nel PDF e nell'informativa) ---
    'company' => [
        'name'      => 'Novazione S.r.l.',
        'address'   => 'Via Roma 123, 20121 Milano (MI)',
        'vat'       => 'IT00000000000',
        'email'     => 'info@novazione.it',
        'phone'     => '+39 02 1234567',
        'pec'       => '',
        'dpo_email' => '',
    ],

    // --- Testi dei consensi (versionati) ----------------------------------
    // Se modifichi un testo, aumenta 'version': ogni PDF riporta il testo
    // esatto e la versione accettata (art. 7.1 GDPR: onere della prova).
    'consents' => [
        'version'     => '2026-11-17.1',
        'policy_url'  => 'privacy.php',
        'privacy'     => "Dichiaro di aver letto l'Informativa Privacy e acconsento al trattamento dei miei dati personali per essere ricontattato in merito alla mia richiesta (art. 6.1.a/b GDPR).",
        'marketing'   => "Acconsento a ricevere comunicazioni commerciali e promozionali via email, telefono, SMS e WhatsApp (facoltativo, revocabile in qualsiasi momento - art. 6.1.a GDPR).",
    ],

    // --- Sicurezza ---------------------------------------------------------
    // Proxy fidati: solo se REMOTE_ADDR è in questa lista si legge X-Forwarded-For.
    // Usa 'cloudflare' come voce per fidarsi dell'header CF-Connecting-IP.
    'trusted_proxies'    => [],
    'rate_limit'         => ['max' => 5, 'window' => 3600],  // invii per IP all'ora
    'min_fill_seconds'   => 3,      // invii più rapidi = bot
    'double_opt_in'      => true,   // conferma via email del consenso marketing

    // Area admin: genera l'hash con
    //   php -r "echo password_hash('LaTuaPassword', PASSWORD_DEFAULT);"
    // e incollalo in config.local.php. Vuoto = area admin disattivata.
    'admin_password_hash' => '',

    // Cartella dati (PDF, registro, chiavi). Meglio FUORI dalla public_html,
    // es. dirname(__DIR__, 2) . '/consensi_storage'. Di default è protetta da .htaccess.
    'storage_dir'        => dirname(__DIR__) . '/storage',

    'timezone'           => 'Europe/Rome',
    'debug'              => false,
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $override = require $local;
    if (is_array($override)) {
        $config = array_replace_recursive($config, $override);
    }
}

return $config;
