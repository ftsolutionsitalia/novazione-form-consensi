<?php
/**
 * Sicurezza: sessione, CSRF, anti-spam, rate limit, IP del client, header HTTP.
 */

if (!defined('NOVAZIONE_APP')) {
    http_response_code(403);
    exit;
}

class Security
{
    /** Reti pubblicate da Cloudflare (https://www.cloudflare.com/ips/). */
    const CLOUDFLARE_RANGES = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18',
        '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
        '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
        '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    /** true se $ip è uguale a uno degli indirizzi o dentro una delle reti CIDR indicate. */
    public static function ipInRanges($ip, array $ranges)
    {
        $bin = @inet_pton($ip);
        if ($bin === false) {
            return false;
        }
        foreach ($ranges as $range) {
            $parts = explode('/', $range, 2);
            $net = @inet_pton($parts[0]);
            if ($net === false || strlen($net) !== strlen($bin)) {
                continue;
            }
            $bits = isset($parts[1]) ? (int) $parts[1] : strlen($bin) * 8;
            $bytes = intdiv($bits, 8);
            if (substr($bin, 0, $bytes) !== substr($net, 0, $bytes)) {
                continue;
            }
            $rem = $bits % 8;
            if ($rem === 0) {
                return true;
            }
            $mask = chr((0xFF << (8 - $rem)) & 0xFF);
            if ((($bin[$bytes] ^ $net[$bytes]) & $mask) === "\0") {
                return true;
            }
        }
        return false;
    }

    public static function startSession()
    {
        if (session_status() === PHP_SESSION_ACTIVE || PHP_SAPI === 'cli') {
            return;
        }
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_name('nvz_sid');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', '14400'); // 4 ore: chi scrive con calma non perde la sessione
        session_start();
    }

    public static function sendSecurityHeaders()
    {
        if (headers_sent() || PHP_SAPI === 'cli') {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'self'; base-uri 'self'; object-src 'none'");
    }

    // ---------------------------------------------------------------- CSRF

    public static function csrfToken()
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function checkCsrf($token)
    {
        return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    }

    /** Segna l'istante in cui il form è stato mostrato (trappola temporale anti-bot). */
    public static function formIssuedToken($backdate = 0)
    {
        $t = (int) floor(microtime(true) * 1000) - (int) $backdate * 1000; // millisecondi
        return $t . '.' . substr(hash_hmac('sha256', (string) $t, Storage::secretKey()), 0, 16);
    }

    /** Verifica il token temporale: firmato e non troppo veloce/vecchio. */
    public static function checkFormTiming($token, $minSeconds)
    {
        if (!is_string($token) || !preg_match('/^(\d{12,14})\.([a-f0-9]{16})$/', $token, $m)) {
            return false;
        }
        $expected = substr(hash_hmac('sha256', $m[1], Storage::secretKey()), 0, 16);
        if (!hash_equals($expected, $m[2])) {
            return false;
        }
        $ageMs = (int) floor(microtime(true) * 1000) - (int) $m[1];
        return $ageMs >= $minSeconds * 1000;
    }

    /** Token del link di conferma double opt-in per una pratica. */
    public static function doiToken($id)
    {
        return hash_hmac('sha256', 'doi|' . $id, Storage::secretKey());
    }

    // ---------------------------------------------------------- Rate limit

    /** true se l'IP può ancora inviare; registra il tentativo. */
    public static function rateLimit($bucket, $ip, $max, $window)
    {
        $dir = Storage::path('ratelimit');
        $file = $dir . '/' . hash_hmac('sha256', $bucket . '|' . $ip, Storage::secretKey()) . '.json';
        $now = time();

        $fp = fopen($file, 'c+');
        if (!$fp) {
            return true; // in caso di problemi di disco non bloccare utenti reali
        }
        flock($fp, LOCK_EX);
        $raw = stream_get_contents($fp);
        $hits = json_decode($raw ?: '[]', true);
        if (!is_array($hits)) {
            $hits = [];
        }
        $hits = array_values(array_filter($hits, function ($t) use ($now, $window) {
            return is_int($t) && $t > $now - $window;
        }));
        $allowed = count($hits) < $max;
        if ($allowed) {
            $hits[] = $now;
        }
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($hits));
        flock($fp, LOCK_UN);
        fclose($fp);

        // Pulizia occasionale dei file scaduti
        if (random_int(1, 50) === 1) {
            foreach (glob($dir . '/*.json') ?: [] as $f) {
                if (filemtime($f) < $now - 2 * $window) {
                    @unlink($f);
                }
            }
        }
        return $allowed;
    }

    // ------------------------------------------------------------- IP

    /**
     * IP reale del client. Gli header X-Forwarded-For sono falsificabili,
     * quindi vengono letti SOLO se la richiesta arriva da un proxy fidato.
     */
    public static function clientIp()
    {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        $trusted = (array) cfg('trusted_proxies', []);

        // CF-Connecting-IP vale solo se la richiesta arriva DAVVERO da Cloudflare
        if (in_array('cloudflare', $trusted, true) && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])
            && self::ipInRanges($remote, self::CLOUDFLARE_RANGES)) {
            $cf = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
            if (filter_var($cf, FILTER_VALIDATE_IP)) {
                return $cf;
            }
        }

        $proxies = array_values(array_diff($trusted, ['cloudflare']));
        if ($remote && self::ipInRanges($remote, $proxies) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Il client reale è l'ultimo indirizzo non fidato da destra
            $chain = array_reverse(array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));
            foreach ($chain as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP) && !self::ipInRanges($ip, $proxies)) {
                    return $ip;
                }
            }
        }

        return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '0.0.0.0';
    }
}
