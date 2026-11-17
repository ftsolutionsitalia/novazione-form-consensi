<?php
/**
 * Pubblica il sito via FTP/FTPS. Da eseguire sul TUO computer:
 *
 *   php deploy/deploy_ftp.php
 *
 * Le credenziali NON sono salvate nel file: vengono chieste a ogni esecuzione
 * (oppure lette dalle variabili d'ambiente FTP_HOST, FTP_USER, FTP_PASS, FTP_DIR).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
if (!function_exists('ftp_connect')) {
    fwrite(STDERR, "Estensione PHP 'ftp' non disponibile. Usa FileZilla (vedi README).\n");
    exit(1);
}

function ask($label, $default = '', $hidden = false)
{
    $suffix = $default !== '' ? " [$default]" : '';
    echo $label . $suffix . ': ';
    if ($hidden && DIRECTORY_SEPARATOR === '/') {
        system('stty -echo');
        $v = trim((string) fgets(STDIN));
        system('stty echo');
        echo "\n";
    } else {
        $v = trim((string) fgets(STDIN));
    }
    return $v !== '' ? $v : $default;
}

$host = getenv('FTP_HOST') ?: ask('Host FTP', 'ftp.studioortopedicopremium.it');
$user = getenv('FTP_USER') ?: ask('Utente FTP');
$pass = getenv('FTP_PASS') ?: ask('Password FTP', '', true);
$dest = rtrim(getenv('FTP_DIR') ?: ask('Cartella remota', '/public_html/sviluppo'), '/');

$root = dirname(__DIR__);
$skip = ['.git', 'storage', 'tests', 'deploy', '.github', 'node_modules'];

// Prima prova FTPS (cifrato), poi FTP semplice
$ftp = function_exists('ftp_ssl_connect') ? @ftp_ssl_connect($host, 21, 20) : false;
$secure = $ftp && @ftp_login($ftp, $user, $pass);
if (!$secure) {
    if ($ftp) {
        @ftp_close($ftp);
    }
    $ftp = @ftp_connect($host, 21, 20);
    if (!$ftp || !@ftp_login($ftp, $user, $pass)) {
        fwrite(STDERR, "Connessione o login non riusciti. Controlla host, utente e password.\n");
        exit(1);
    }
    echo "ATTENZIONE: il server non supporta FTPS, connessione non cifrata.\n";
} else {
    echo "Connesso con FTPS (cifrato).\n";
}
ftp_pasv($ftp, true);

function mkdirs($ftp, $dir)
{
    $path = '';
    foreach (explode('/', trim($dir, '/')) as $part) {
        $path .= '/' . $part;
        @ftp_mkdir($ftp, $path);
    }
}

$count = 0;
$errors = 0;
$it = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        function ($f) use ($root, $skip) {
            $rel = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($root))), '/');
            return !in_array(explode('/', $rel)[0], $skip, true);
        }
    ),
    RecursiveIteratorIterator::SELF_FIRST
);

mkdirs($ftp, $dest);
foreach ($it as $f) {
    $rel = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($root))), '/');
    $remote = $dest . '/' . $rel;
    if ($f->isDir()) {
        @ftp_mkdir($ftp, $remote);
        continue;
    }
    if (ftp_put($ftp, $remote, $f->getPathname(), FTP_BINARY)) {
        $count++;
        echo "  OK  $rel\n";
    } else {
        $errors++;
        echo "  ERR $rel\n";
    }
}
ftp_close($ftp);

echo "\nCaricati $count file" . ($errors ? ", $errors errori" : '') . ".\n";
echo "Apri il sito e poi verifica: /sviluppo/tests/ NON deve essere raggiungibile (errore 403/404).\n";
exit($errors ? 1 : 0);
