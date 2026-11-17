<?php
/**
 * Copia questo file in config.local.php e personalizzalo.
 * config.local.php NON viene salvato su Git ed è bloccato da .htaccess.
 * Indica solo i valori che vuoi cambiare rispetto a config.php.
 */
return [
    'mail_from'      => 'noreply@studioortopedicopremium.it',
    'mail_from_name' => 'Novazione',

    // SMTP della casella mittente (consigliato: migliora la consegna su Gmail)
    'smtp' => [
        'host'       => 'mail.studioortopedicopremium.it',
        'port'       => 587,
        'username'   => 'noreply@studioortopedicopremium.it',
        'password'   => 'PASSWORD_DELLA_CASELLA',
        'encryption' => 'tls',
    ],

    'company' => [
        'name'    => 'Ragione Sociale S.r.l.',
        'address' => 'Indirizzo completo',
        'vat'     => 'IT00000000000',
        'email'   => 'info@tuodominio.it',
        'phone'   => '+39 000 0000000',
        'pec'     => '',
    ],

    // php -r "echo password_hash('LaTuaPassword', PASSWORD_DEFAULT);"
    'admin_password_hash' => '',

    // Se il sito è dietro Cloudflare:
    // 'trusted_proxies' => ['cloudflare'],
];
