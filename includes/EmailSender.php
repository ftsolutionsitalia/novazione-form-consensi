<?php
/**
 * Email Sender per Consensi Privacy
 *
 * Gestisce l'invio delle email con allegato PDF
 */

// Impedisci accesso diretto
if (!defined('NOVAZIONE_APP')) {
    die('Accesso negato');
}

class EmailSender
{
    private $to;
    private $subject;
    private $body;
    private $headers;
    private $attachments = [];

    /**
     * Costruttore
     *
     * @param string $to Destinatario
     * @param string $subject Oggetto
     */
    public function __construct($to, $subject)
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->headers = [];
    }

    /**
     * Imposta il corpo dell'email
     *
     * @param string $body Corpo HTML
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Aggiunge un allegato
     *
     * @param string $filePath Percorso del file
     * @param string $fileName Nome del file (opzionale)
     * @return $this
     */
    public function addAttachment($filePath, $fileName = null)
    {
        if (file_exists($filePath)) {
            $this->attachments[] = [
                'path' => $filePath,
                'name' => $fileName ?: basename($filePath)
            ];
        }
        return $this;
    }

    /**
     * Invia l'email
     *
     * @return bool
     */
    public function send()
    {
        $boundary = md5(time());

        // Headers
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'From: ' . EMAIL_NOME_MITTENTE . ' <' . EMAIL_MITTENTE . '>';
        $headers[] = 'Reply-To: ' . EMAIL_MITTENTE;
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        if (empty($this->attachments)) {
            // Email semplice HTML
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $body = $this->body;
        } else {
            // Email con allegati
            $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

            $body = "--{$boundary}\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $body .= $this->body . "\r\n\r\n";

            // Aggiungi allegati
            foreach ($this->attachments as $attachment) {
                $fileContent = file_get_contents($attachment['path']);
                $fileEncoded = chunk_split(base64_encode($fileContent));
                $mimeType = mime_content_type($attachment['path']) ?: 'application/octet-stream';

                $body .= "--{$boundary}\r\n";
                $body .= "Content-Type: {$mimeType}; name=\"{$attachment['name']}\"\r\n";
                $body .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $body .= $fileEncoded . "\r\n";
            }

            $body .= "--{$boundary}--";
        }

        $headersString = implode("\r\n", $headers);

        return mail($this->to, $this->subject, $body, $headersString);
    }

    /**
     * Genera il template email per l'amministratore
     *
     * @param array $data Dati del form
     * @param string $hash Hash del documento
     * @return string HTML
     */
    public static function getAdminTemplate($data, $hash)
    {
        $timestamp = date('d/m/Y H:i:s', strtotime($data['timestamp']));
        $privacyStatus = $data['privacy'] ? '✅ Accettato' : '❌ Non accettato';
        $marketingStatus = $data['marketing'] ? '✅ Accettato' : '❌ Non accettato';

        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f3f4f6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f3f4f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Nuovo Consenso Ricevuto</h1>
                            <p style="color: #bfdbfe; margin: 10px 0 0 0; font-size: 14px;">Novazione - Form Contatti</p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px;">
                            <!-- User Data -->
                            <h2 style="color: #1f2937; font-size: 18px; margin: 0 0 20px 0; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb;">
                                📋 Dati Utente
                            </h2>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 8px 0; color: #6b7280; font-size: 14px; width: 120px;">Nome:</td>
                                    <td style="padding: 8px 0; color: #1f2937; font-size: 14px; font-weight: bold;">' . htmlspecialchars($data['nome']) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Cognome:</td>
                                    <td style="padding: 8px 0; color: #1f2937; font-size: 14px; font-weight: bold;">' . htmlspecialchars($data['cognome']) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Email:</td>
                                    <td style="padding: 8px 0; color: #3b82f6; font-size: 14px;"><a href="mailto:' . htmlspecialchars($data['email']) . '" style="color: #3b82f6;">' . htmlspecialchars($data['email']) . '</a></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Telefono:</td>
                                    <td style="padding: 8px 0; color: #1f2937; font-size: 14px; font-weight: bold;"><a href="tel:' . htmlspecialchars($data['telefono']) . '" style="color: #3b82f6;">' . htmlspecialchars($data['telefono']) . '</a></td>
                                </tr>
                            </table>

                            ' . (!empty($data['messaggio']) ? '
                            <!-- Message -->
                            <h2 style="color: #1f2937; font-size: 18px; margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb;">
                                💬 Messaggio
                            </h2>
                            <p style="color: #374151; font-size: 14px; line-height: 1.6; background-color: #f9fafb; padding: 15px; border-radius: 8px; margin: 0 0 30px 0;">
                                ' . nl2br(htmlspecialchars($data['messaggio'])) . '
                            </p>
                            ' : '') . '

                            <!-- Consents -->
                            <h2 style="color: #1f2937; font-size: 18px; margin: 0 0 20px 0; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb;">
                                🔐 Consensi
                            </h2>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 10px 0; color: #6b7280; font-size: 14px;">Privacy Policy:</td>
                                    <td style="padding: 10px 0; color: #1f2937; font-size: 14px; font-weight: bold;">' . $privacyStatus . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 0; color: #6b7280; font-size: 14px;">Marketing:</td>
                                    <td style="padding: 10px 0; color: #1f2937; font-size: 14px; font-weight: bold;">' . $marketingStatus . '</td>
                                </tr>
                            </table>

                            <!-- Technical Data -->
                            <h2 style="color: #1f2937; font-size: 18px; margin: 0 0 20px 0; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb;">
                                ⚙️ Dati Tecnici
                            </h2>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 20px;">
                                <tr>
                                    <td style="padding: 8px 0; color: #6b7280; font-size: 14px; width: 120px;">Timestamp:</td>
                                    <td style="padding: 8px 0; color: #1f2937; font-size: 14px;">' . $timestamp . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">IP Address:</td>
                                    <td style="padding: 8px 0; color: #1f2937; font-size: 14px; font-family: monospace;">' . htmlspecialchars($data['ip']) . '</td>
                                </tr>
                            </table>

                            <!-- Hash -->
                            <div style="background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 15px; margin-top: 20px;">
                                <p style="margin: 0 0 8px 0; color: #0369a1; font-size: 12px; font-weight: bold;">🔒 HASH DI INTEGRITÀ (SHA-256)</p>
                                <p style="margin: 0; color: #1f2937; font-size: 11px; font-family: monospace; word-break: break-all;">' . $hash . '</p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; color: #6b7280; font-size: 12px;">
                                📎 Il documento PDF del consenso è allegato a questa email.
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="color: #9ca3af; font-size: 12px; margin-top: 20px; text-align: center;">
                    © ' . date('Y') . ' ' . AZIENDA_NOME . ' - Tutti i diritti riservati
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Genera il template email per l'utente (conferma)
     *
     * @param array $data Dati del form
     * @return string HTML
     */
    public static function getUserTemplate($data)
    {
        $timestamp = date('d/m/Y H:i:s', strtotime($data['timestamp']));

        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f3f4f6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f3f4f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 40px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">' . AZIENDA_NOME . '</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="color: #1f2937; font-size: 22px; margin: 0 0 20px 0;">
                                Grazie per averci contattato, ' . htmlspecialchars($data['nome']) . '!
                            </h2>

                            <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 25px 0;">
                                Abbiamo ricevuto la tua richiesta e ti risponderemo il prima possibile, solitamente entro 24 ore lavorative.
                            </p>

                            <div style="background-color: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                                <p style="margin: 0; color: #166534; font-size: 14px;">
                                    ✅ <strong>Consenso registrato con successo</strong><br>
                                    <span style="font-size: 13px; color: #15803d;">Data: ' . $timestamp . '</span>
                                </p>
                            </div>

                            <p style="color: #374151; font-size: 14px; line-height: 1.6; margin: 0 0 25px 0;">
                                In allegato trovi una copia del documento di consenso che hai sottoscritto, contenente tutti i dettagli e l\'hash di integrità per la verifica dell\'autenticità.
                            </p>

                            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">

                            <h3 style="color: #1f2937; font-size: 16px; margin: 0 0 15px 0;">Hai bisogno di assistenza?</h3>
                            <p style="color: #6b7280; font-size: 14px; margin: 0;">
                                📧 Email: <a href="mailto:' . AZIENDA_EMAIL . '" style="color: #3b82f6;">' . AZIENDA_EMAIL . '</a><br>
                                📞 Telefono: <a href="tel:' . AZIENDA_TELEFONO . '" style="color: #3b82f6;">' . AZIENDA_TELEFONO . '</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1f2937; padding: 30px; text-align: center;">
                            <p style="margin: 0 0 10px 0; color: #ffffff; font-size: 14px;">
                                ' . AZIENDA_NOME . '
                            </p>
                            <p style="margin: 0; color: #9ca3af; font-size: 12px;">
                                ' . AZIENDA_INDIRIZZO . '
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="color: #9ca3af; font-size: 11px; margin-top: 20px; text-align: center; max-width: 500px;">
                    Questa email è stata generata automaticamente a seguito della tua richiesta di contatto.
                    I tuoi dati sono trattati nel rispetto del GDPR (Reg. UE 2016/679).
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
}
