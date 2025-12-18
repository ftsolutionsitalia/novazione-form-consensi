<?php
/**
 * Processamento Form Contatti
 *
 * Gestisce la validazione, generazione PDF e invio email
 */

// Definisci la costante per l'accesso alle classi
define('NOVAZIONE_APP', true);

// Includi la configurazione
require_once __DIR__ . '/includes/config.php';

// Verifica che la richiesta sia POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithError('Metodo non consentito');
}

// Sanitizza e valida i dati del form
$nome = sanitizeInput($_POST['nome'] ?? '');
$cognome = sanitizeInput($_POST['cognome'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$telefono = sanitizeInput($_POST['telefono'] ?? '');
$messaggio = sanitizeInput($_POST['messaggio'] ?? '');
$privacy = isset($_POST['privacy']) && $_POST['privacy'] === 'on';
$marketing = isset($_POST['marketing']) && $_POST['marketing'] === 'on';

// Valida i campi obbligatori
$errors = [];

if (empty($nome) || strlen($nome) < 2) {
    $errors[] = 'Nome non valido';
}

if (empty($cognome) || strlen($cognome) < 2) {
    $errors[] = 'Cognome non valido';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email non valida';
}

if (empty($telefono) || !preg_match('/^[\+]?[0-9\s]{10,15}$/', str_replace(' ', '', $telefono))) {
    $errors[] = 'Numero di telefono non valido';
}

if (!$privacy) {
    $errors[] = 'Devi accettare l\'informativa sulla privacy';
}

// Se ci sono errori, reindirizza con messaggio
if (!empty($errors)) {
    redirectWithError(implode(', ', $errors));
}

// Ottieni IP e timestamp
$ip = getClientIP();
$timestamp = date('Y-m-d H:i:s');

// Prepara i dati
$formData = [
    'nome' => $nome,
    'cognome' => $cognome,
    'email' => $email,
    'telefono' => $telefono,
    'messaggio' => $messaggio,
    'privacy' => $privacy,
    'marketing' => $marketing,
    'ip' => $ip,
    'timestamp' => $timestamp
];

try {
    // Includi le classi necessarie
    require_once __DIR__ . '/includes/PDFGenerator.php';
    require_once __DIR__ . '/includes/EmailSender.php';

    // Genera il PDF
    $pdfGenerator = new PDFGenerator($formData);
    $pdfPath = $pdfGenerator->generate();
    $documentHash = $pdfGenerator->getHash();

    logMessage("PDF generato: $pdfPath con hash: $documentHash");

    // Invia email all'amministratore
    $adminEmail = new EmailSender(
        EMAIL_DESTINATARIO,
        'Nuovo Consenso: ' . $nome . ' ' . $cognome
    );
    $adminEmail->setBody(EmailSender::getAdminTemplate($formData, $documentHash));
    $adminEmail->addAttachment($pdfPath);
    $adminSent = $adminEmail->send();

    logMessage("Email admin inviata: " . ($adminSent ? 'OK' : 'ERRORE'));

    // Invia email di conferma all'utente
    $userEmail = new EmailSender(
        $email,
        'Conferma ricezione richiesta - ' . AZIENDA_NOME
    );
    $userEmail->setBody(EmailSender::getUserTemplate($formData));
    $userEmail->addAttachment($pdfPath);
    $userSent = $userEmail->send();

    logMessage("Email utente inviata: " . ($userSent ? 'OK' : 'ERRORE'));

    // Salva i dati in un file JSON per backup (opzionale)
    saveConsentData($formData, $documentHash, $pdfPath);

    // Reindirizza alla pagina di successo
    header('Location: success.php?hash=' . urlencode(substr($documentHash, 0, 16)));
    exit;

} catch (Exception $e) {
    logMessage("Errore: " . $e->getMessage(), 'ERROR');
    redirectWithError('Si è verificato un errore durante l\'elaborazione. Riprova più tardi.');
}

/**
 * Funzioni helper
 */

/**
 * Sanitizza l'input
 */
function sanitizeInput($input)
{
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Ottiene l'IP del client
 */
function getClientIP()
{
    $ipHeaders = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_X_FORWARDED_FOR',      // Proxy
        'HTTP_X_REAL_IP',            // Nginx
        'HTTP_CLIENT_IP',            // Altro proxy
        'REMOTE_ADDR'                // Standard
    ];

    foreach ($ipHeaders as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            // Se contiene più IP (proxy chain), prendi il primo
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            // Valida l'IP
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return 'Unknown';
}

/**
 * Salva i dati del consenso per backup
 */
function saveConsentData($data, $hash, $pdfPath)
{
    $dataDir = ROOT_PATH . '/data/';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    $consent = [
        'data' => $data,
        'hash' => $hash,
        'pdf_path' => basename($pdfPath),
        'created_at' => date('c')
    ];

    $filename = $dataDir . 'consent_' . date('Ymd_His') . '_' . substr($hash, 0, 8) . '.json';
    file_put_contents($filename, json_encode($consent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Reindirizza con messaggio di errore
 */
function redirectWithError($message)
{
    $encodedMessage = urlencode($message);
    header("Location: error.php?message=$encodedMessage");
    exit;
}
