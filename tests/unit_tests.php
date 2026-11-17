<?php
/**
 * Test unitari (CLI):  NOVAZIONE_STORAGE=/tmp/nvz_unit php tests/unit_tests.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$tmp = getenv('NOVAZIONE_STORAGE') ?: sys_get_temp_dir() . '/nvz_unit_' . getmypid();
putenv('NOVAZIONE_STORAGE=' . $tmp);
$_SERVER['HTTP_HOST'] = 'example.test';
require dirname(__DIR__) . '/includes/bootstrap.php';
$GLOBALS['APP_CONFIG']['storage_dir'] = $tmp;

$pass = 0; $fail = 0;
function t($name, $cond) { global $pass, $fail; if ($cond) { $pass++; echo "  OK   $name\n"; } else { $fail++; echo "  FAIL $name\n"; } }

echo "Security::ipInRanges\n";
t('IPv4 in CIDR', Security::ipInRanges('104.16.5.9', ['104.16.0.0/13']));
t('IPv4 fuori CIDR', !Security::ipInRanges('104.24.0.1', ['104.16.0.0/13']));
t('IPv4 /20 bordo', Security::ipInRanges('173.245.63.255', ['173.245.48.0/20']) && !Security::ipInRanges('173.245.64.0', ['173.245.48.0/20']));
t('IPv6 in CIDR', Security::ipInRanges('2606:4700:10::1', ['2606:4700::/32']));
t('IP singolo', Security::ipInRanges('10.0.0.1', ['10.0.0.1']) && !Security::ipInRanges('10.0.0.2', ['10.0.0.1']));
t('IP non valido', !Security::ipInRanges('nonip', ['0.0.0.0/0']));

echo "Security::clientIp\n";
$GLOBALS['APP_CONFIG']['trusted_proxies'] = ['cloudflare'];
$_SERVER['REMOTE_ADDR'] = '203.0.113.7'; $_SERVER['HTTP_CF_CONNECTING_IP'] = '1.2.3.4';
t('CF-Connecting-IP falsificato da IP non Cloudflare ignorato', Security::clientIp() === '203.0.113.7');
$_SERVER['REMOTE_ADDR'] = '162.158.1.1';
t('CF-Connecting-IP accettato da Cloudflare', Security::clientIp() === '1.2.3.4');
unset($_SERVER['HTTP_CF_CONNECTING_IP']);
$GLOBALS['APP_CONFIG']['trusted_proxies'] = [];
$_SERVER['REMOTE_ADDR'] = '198.51.100.1'; $_SERVER['HTTP_X_FORWARDED_FOR'] = '6.6.6.6';
t('X-Forwarded-For ignorato senza proxy fidati', Security::clientIp() === '198.51.100.1');
$GLOBALS['APP_CONFIG']['trusted_proxies'] = ['10.0.0.0/8'];
$_SERVER['REMOTE_ADDR'] = '10.1.2.3'; $_SERVER['HTTP_X_FORWARDED_FOR'] = '6.6.6.6, 5.5.5.5, 10.9.9.9';
t('X-Forwarded-For: ultimo IP non fidato da destra', Security::clientIp() === '5.5.5.5');

echo "Token temporale\n";
t('Token appena emesso rifiutato (troppo veloce)', !Security::checkFormTiming(Security::formIssuedToken(), 3));
t('Token retrodatato accettato', Security::checkFormTiming(Security::formIssuedToken(5), 3));
t('Pagina aperta da 3 giorni accettata', Security::checkFormTiming(Security::formIssuedToken(3 * 86400), 3));
t('Token manomesso rifiutato', !Security::checkFormTiming(preg_replace('/\.[a-f0-9]+$/', '.0000000000000000', Security::formIssuedToken(10)), 3));

echo "JSON e registro\n";
$bad = "abc\xC3\x28def";
t('JSON con UTF-8 non valido non fallisce', is_string(ConsentLedger::json(['x' => $bad])));
$e1 = ConsentLedger::append('test', ['n' => 1]);
$e2 = ConsentLedger::append('test', ['n' => 2, 'big' => str_repeat('x', 20000)]);
$e3 = ConsentLedger::append('test', ['n' => 3]);
t('Numerazione continua leggendo solo la coda', $e1['seq'] === 1 && $e2['seq'] === 2 && $e3['seq'] === 3);
t('Collegamento prev corretto anche dopo riga > 8 KB', $e3['prev'] === $e2['hash'] && $e2['prev'] === $e1['hash']);
t('Catena verificata', ConsentLedger::verify()['ok'] === true);
t('Chiave segreta stabile', Storage::secretKey() === trim(file_get_contents($tmp . '/keys/secret.key')));
t('Token DOI deterministico', Security::doiToken('CNS-20260101-AAAAAAAA') === Security::doiToken('CNS-20260101-AAAAAAAA'));

exec('rm -rf ' . escapeshellarg($tmp));
echo "\nUnit: $pass superati, $fail falliti\n";
exit($fail ? 1 : 0);
