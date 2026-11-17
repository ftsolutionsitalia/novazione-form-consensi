<?php
/**
 * Invio email tramite PHPMailer (SMTP se configurato, altrimenti mail()).
 * PHPMailer gestisce codifica, allegati e blocca l'header injection.
 */

if (!defined('NOVAZIONE_APP')) {
    http_response_code(403);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class Mailer
{
    /** @return bool */
    public static function send($to, $subject, $html, array $attachments = [], $replyTo = null)
    {
        $mail = new PHPMailer(true);
        try {
            $smtp = cfg('smtp');
            if (!empty($smtp['host'])) {
                $mail->isSMTP();
                $mail->Host = $smtp['host'];
                $mail->Port = (int) $smtp['port'];
                $mail->SMTPAuth = $smtp['username'] !== '';
                $mail->Username = $smtp['username'];
                $mail->Password = $smtp['password'];
                if ($smtp['encryption'] === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($smtp['encryption'] === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }
                $mail->Timeout = 15;
            } else {
                $mail->isMail();
            }

            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Encoding = PHPMailer::ENCODING_BASE64;
            $from = mail_from_address();
            $mail->setFrom($from, cfg('mail_from_name'));
            $mail->Sender = $from; // Return-Path allineato al mittente (SPF)
            // Message-ID sul dominio del mittente, non sull'header Host della richiesta
            $mail->Hostname = substr(strrchr($from, '@'), 1);
            $mail->addAddress($to);
            if ($replyTo && PHPMailer::validateAddress($replyTo)) {
                $mail->addReplyTo($replyTo);
            }
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->AltBody = self::toText($html);

            foreach ($attachments as $path => $name) {
                $mail->addAttachment($path, $name, PHPMailer::ENCODING_BASE64, 'application/pdf');
            }

            $mail->send();
            return true;
        } catch (MailException $ex) {
            app_log('Invio email fallito verso ' . self::mask($to) . ': ' . $mail->ErrorInfo, 'ERROR');
            return false;
        }
    }

    private static function toText($html)
    {
        $text = preg_replace(['/<br\s*\/?>/i', '/<\/(p|div|tr|h\d)>/i'], "\n", $html);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
        return trim(preg_replace("/\n{3,}/", "\n\n", preg_replace('/[ \t]+/', ' ', $text)));
    }

    /** Maschera un indirizzo per i log (minimizzazione dei dati). */
    public static function mask($email)
    {
        $parts = explode('@', (string) $email);
        return substr($parts[0], 0, 2) . '***@' . ($parts[1] ?? '');
    }

    // ------------------------------------------------------------ Template

    private static function layout($title, $inner)
    {
        $c = cfg('company');
        return '<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px;"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;">
<tr><td style="background:#1e40af;padding:24px 28px;color:#ffffff;">
<div style="font-size:20px;font-weight:bold;">' . e($title) . '</div>
<div style="font-size:13px;color:#dbeafe;margin-top:4px;">' . e($c['name']) . '</div></td></tr>
<tr><td style="padding:24px 28px;font-size:14px;line-height:1.6;">' . $inner . '</td></tr>
<tr><td style="background:#f8fafc;padding:16px 28px;font-size:11px;color:#64748b;border-top:1px solid #e2e8f0;">
' . e($c['name']) . ' · ' . e($c['address']) . ' · ' . e($c['email']) . '</td></tr>
</table></td></tr></table></body></html>';
    }

    private static function rows(array $rows)
    {
        $h = '<table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:14px;">';
        foreach ($rows as $k => $v) {
            $h .= '<tr><td style="color:#64748b;width:38%;border-bottom:1px solid #f1f5f9;vertical-align:top;">' . e($k) . '</td><td style="border-bottom:1px solid #f1f5f9;"><b>' . $v . '</b></td></tr>';
        }
        return $h . '</table>';
    }

    public static function adminTemplate(array $r, $entry, $pdfSha, $dataHash, $verifyUrl)
    {
        $ok = '<span style="color:#047857;">&#10004; Prestato</span>';
        $ko = '<span style="color:#b91c1c;">&#10008; Non prestato</span>';
        $dt = (new DateTime($r['timestamp']))->format('d/m/Y H:i:s T');
        $inner = '<p>È stato registrato un nuovo consenso. L\'attestazione PDF firmata è allegata.</p>'
            . self::rows([
                'ID pratica' => e($r['id']),
                'Data e ora' => e($dt),
                'Nome' => e($r['nome'] . ' ' . $r['cognome']),
                'Email' => '<a href="mailto:' . e($r['email']) . '">' . e($r['email']) . '</a>',
                'Cellulare' => '<a href="tel:' . e(preg_replace('/[^+\d]/', '', $r['telefono'])) . '">' . e($r['telefono']) . '</a>',
                'Consenso privacy' => $r['privacy'] ? $ok : $ko,
                'Consenso marketing' => $r['marketing'] ? $ok . (cfg('double_opt_in') ? ' <span style="color:#b45309;font-weight:normal;">(in attesa di conferma via email)</span>' : '') : $ko,
                'Indirizzo IP' => e($r['ip']),
                'Versione testi' => e($r['consent_version']),
            ]);
        if (trim($r['messaggio']) !== '') {
            $inner .= '<p style="margin-top:18px;color:#64748b;">Messaggio</p><div style="background:#f8fafc;border-radius:8px;padding:12px;">' . nl2br(e($r['messaggio'])) . '</div>';
        }
        $inner .= '<div style="margin-top:20px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px;font-size:12px;">'
            . '<b>Impronte di integrità</b> (conserva questa email: è una copia esterna al server)<br>'
            . 'SHA-256 file PDF:<br><code style="word-break:break-all;">' . e($pdfSha) . '</code><br>'
            . 'HMAC dati:<br><code style="word-break:break-all;">' . e($dataHash) . '</code><br>'
            . 'Registro: evento n. ' . (int) $entry['seq'] . ' · hash <code style="word-break:break-all;">' . e($entry['hash']) . '</code><br>'
            . 'Verifica: <a href="' . e($verifyUrl) . '">' . e($verifyUrl) . '</a></div>';
        return self::layout('Nuovo consenso registrato', $inner);
    }

    public static function userTemplate(array $r, $confirmUrl, $revokeUrl)
    {
        $inner = '<p>Gentile ' . e($r['nome']) . ',</p>'
            . '<p>grazie per averci contattato: abbiamo ricevuto la tua richiesta e ti risponderemo al più presto.</p>'
            . '<p>In allegato trovi l\'attestazione dei consensi che hai espresso (ID <b>' . e($r['id']) . '</b>), firmata digitalmente e non modificabile.</p>';
        if ($confirmUrl) {
            $inner .= '<div style="margin:22px 0;padding:16px;background:#eff6ff;border-radius:8px;">'
                . '<p style="margin:0 0 12px 0;">Hai scelto di ricevere le nostre comunicazioni commerciali. Per attivarle conferma il tuo indirizzo:</p>'
                . '<a href="' . e($confirmUrl) . '" style="display:inline-block;background:#1d4ed8;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:bold;">Confermo l\'iscrizione</a>'
                . '<p style="margin:12px 0 0 0;font-size:12px;color:#64748b;">Se non confermi, non ti invieremo comunicazioni commerciali.</p></div>';
        }
        $inner .= '<p style="font-size:12px;color:#64748b;">Puoi revocare i consensi in qualsiasi momento: <a href="' . e($revokeUrl) . '">' . e($revokeUrl) . '</a></p>';
        return self::layout('Richiesta ricevuta', $inner);
    }

    public static function eventTemplate($title, array $rows)
    {
        $esc = [];
        foreach ($rows as $k => $v) {
            $esc[$k] = e($v);
        }
        return self::layout($title, self::rows($esc));
    }
}
