<?php
/**
 * Gestione della cartella dati privata (PDF, registro consensi, chiavi, log).
 */

if (!defined('NOVAZIONE_APP')) {
    http_response_code(403);
    exit;
}

class Storage
{
    /** Restituisce (e crea se serve) una sottocartella dello storage. */
    public static function path($sub = '')
    {
        $root = rtrim(cfg('storage_dir'), '/\\');
        if (!is_dir($root)) {
            if (!@mkdir($root, 0750, true) && !is_dir($root)) {
                throw new RuntimeException('Impossibile creare la cartella dati: verifica i permessi di scrittura.');
            }
        }
        self::protect($root);

        if ($sub === '') {
            return $root;
        }
        $dir = $root . '/' . $sub;
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0750, true) && !is_dir($dir)) {
                throw new RuntimeException("Impossibile creare la cartella dati '$sub'.");
            }
        }
        return $dir;
    }

    /** Blocca l'accesso web alla cartella (Apache 2.2/2.4) e il listing. */
    private static function protect($dir)
    {
        $ht = $dir . '/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\nOptions -Indexes\n");
        }
        if (!is_file($dir . '/index.html')) {
            @file_put_contents($dir . '/index.html', '');
        }
    }

    /**
     * Controlla dall'esterno che la cartella dati NON sia scaricabile dal web
     * (es. server nginx che ignora .htaccess). Ritorna true = protetta,
     * false = PUBBLICA, null = verifica non possibile.
     */
    public static function isWebProtected($baseUrl)
    {
        $root = realpath(self::path());
        $app = realpath(APP_ROOT);
        if ($root === false || $app === false || strpos($root, $app . DIRECTORY_SEPARATOR) !== 0) {
            return true; // fuori dalla cartella pubblicata
        }
        $probe = $root . '/probe.txt';
        if (!is_file($probe)) {
            @file_put_contents($probe, 'riservato');
        }
        $url = rtrim($baseUrl, '/') . '/' . str_replace('\\', '/', substr($root, strlen($app) + 1)) . '/probe.txt';
        $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true, 'follow_location' => 0], 'ssl' => ['verify_peer' => true]]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false || empty($http_response_header[0])) {
            return null;
        }
        return !(strpos($http_response_header[0], ' 200') !== false && trim($body) === 'riservato');
    }

    /** Scrittura atomica: scrive su file temporaneo e rinomina. */
    public static function writeAtomic($file, $data)
    {
        $tmp = $file . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (file_put_contents($tmp, $data, LOCK_EX) === false) {
            throw new RuntimeException('Scrittura su disco non riuscita.');
        }
        @chmod($tmp, 0640);
        if (!rename($tmp, $file)) {
            @unlink($tmp);
            throw new RuntimeException('Scrittura su disco non riuscita.');
        }
    }

    /**
     * Chiave segreta per HMAC e token, generata al primo avvio e mai versionata.
     */
    public static function secretKey()
    {
        static $key = null;
        if ($key !== null) {
            return $key;
        }
        $file = self::path('keys') . '/secret.key';
        $fp = fopen($file, 'c+');
        if (!$fp || !flock($fp, LOCK_EX)) {
            throw new RuntimeException('Chiave segreta non accessibile.');
        }
        $value = trim((string) stream_get_contents($fp));
        if ($value === '') {
            $value = bin2hex(random_bytes(32));
            fwrite($fp, $value);
            fflush($fp);
        }
        flock($fp, LOCK_UN);
        fclose($fp);
        @chmod($file, 0600);
        if (strlen($value) < 64) {
            throw new RuntimeException('Chiave segreta non valida.');
        }
        return $key = $value;
    }

    /** Esegue $fn con un lock esclusivo su un file di lock nello storage. */
    public static function withLock($name, callable $fn)
    {
        $fp = fopen(self::path('keys') . '/' . $name . '.lock', 'c');
        if (!$fp || !flock($fp, LOCK_EX)) {
            throw new RuntimeException('Lock non disponibile.');
        }
        try {
            return $fn();
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
