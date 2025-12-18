<?php
/**
 * Script per upload FTP automatico
 * Esegui: php upload_ftp.php
 */

$ftp_host = 'ftp.studioortopedicopremium.it';
$ftp_user = 'leo@studioortopedicopremium.it';
$ftp_pass = 'Studio@2025';
$ftp_dest = '/public_html/sviluppo';

// File e cartelle da caricare (escludi questo script e file git)
$exclude = ['.git', 'upload_ftp.php', '.gitignore', 'composer.lock'];

echo "=== Upload FTP Novazione Form Consensi ===\n\n";

// Connessione FTP
echo "Connessione a $ftp_host...\n";
$ftp = ftp_connect($ftp_host);
if (!$ftp) {
    die("Errore: Impossibile connettersi a $ftp_host\n");
}

// Login
if (!ftp_login($ftp, $ftp_user, $ftp_pass)) {
    ftp_close($ftp);
    die("Errore: Login fallito\n");
}

echo "Connesso con successo!\n\n";

// Modalità passiva (richiesta dalla maggior parte degli hosting)
ftp_pasv($ftp, true);

// Funzione per creare cartelle ricorsivamente
function ftp_mkdirs($ftp, $dir) {
    $parts = explode('/', $dir);
    $path = '';
    foreach ($parts as $part) {
        if (empty($part)) continue;
        $path .= '/' . $part;
        @ftp_mkdir($ftp, $path);
    }
}

// Funzione per upload ricorsivo
function uploadDirectory($ftp, $localDir, $remoteDir, $exclude) {
    $files = scandir($localDir);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if (in_array($file, $exclude)) continue;

        $localPath = $localDir . '/' . $file;
        $remotePath = $remoteDir . '/' . $file;

        if (is_dir($localPath)) {
            echo "Creazione cartella: $remotePath\n";
            @ftp_mkdir($ftp, $remotePath);
            uploadDirectory($ftp, $localPath, $remotePath, $exclude);
        } else {
            echo "Upload: $file... ";
            if (ftp_put($ftp, $remotePath, $localPath, FTP_BINARY)) {
                echo "OK\n";
            } else {
                echo "ERRORE\n";
            }
        }
    }
}

// Crea la cartella di destinazione
echo "Creazione cartella $ftp_dest...\n";
ftp_mkdirs($ftp, $ftp_dest);

// Upload di tutti i file
echo "\nInizio upload dei file...\n\n";
uploadDirectory($ftp, __DIR__, $ftp_dest, $exclude);

// Imposta permessi sulle cartelle scrivibili
$writableDirs = ['pdfs', 'data', 'logs'];
foreach ($writableDirs as $dir) {
    @ftp_chmod($ftp, 0755, $ftp_dest . '/' . $dir);
}

ftp_close($ftp);

echo "\n=== Upload completato! ===\n";
echo "Visita: https://studioortopedicopremium.it/sviluppo/\n";
