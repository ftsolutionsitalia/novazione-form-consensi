<?php
/**
 * Generazione dell'attestazione di consenso in PDF.
 *
 * Protezioni applicate:
 *  1. Cifratura AES-256 con password proprietario casuale (mai salvata):
 *     modifiche, annotazioni e riassemblaggio sono disabilitati.
 *  2. Firma digitale (certificazione DocMDP livello 1): qualsiasi modifica
 *     successiva invalida la firma ed è segnalata da Acrobat / pdfsig.
 *  3. Impronta HMAC-SHA256 dei dati stampata nel documento.
 *  4. Impronta SHA-256 del file registrata nel registro e inviata per email
 *     (verificabile da verify.php).
 */

if (!defined('NOVAZIONE_APP')) {
    http_response_code(403);
    exit;
}

class PDFGenerator
{
    /** Impronta HMAC dei dati del consenso (stabile e riproducibile). */
    public static function dataHash(array $record)
    {
        $fields = ['id', 'timestamp', 'nome', 'cognome', 'email', 'telefono', 'messaggio',
            'privacy', 'marketing', 'consent_version', 'privacy_text', 'marketing_text', 'ip', 'user_agent', 'source_url'];
        $subset = [];
        foreach ($fields as $f) {
            $subset[$f] = $record[$f] ?? null;
        }
        return hash_hmac('sha256', ConsentLedger::canonical($subset), Storage::secretKey());
    }

    /**
     * Crea il PDF e lo salva nello storage.
     * @return array ['path' => string, 'sha256' => string, 'signed' => bool]
     */
    public static function generate(array $r, $dataHash, $verifyUrl)
    {
        $c = cfg('company');

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Novazione Form Consensi');
        $pdf->SetAuthor($c['name']);
        $pdf->SetTitle('Attestazione consenso ' . $r['id']);
        $pdf->SetSubject('Prova del consenso al trattamento dei dati personali (GDPR)');
        $pdf->SetKeywords($r['id'] . ' GDPR consenso');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(18, 16, 18);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->setFontSubsetting(true);
        $pdf->SetFont('dejavusans', '', 9.5);

        // 1. Permessi: blocca modifica, annotazioni, compilazione e riassemblaggio.
        //    (In TCPDF l'elenco indica i permessi da NEGARE.)
        $pdf->SetProtection(['modify', 'annot-forms', 'fill-forms', 'assemble'], '', bin2hex(random_bytes(24)), 3);

        // 2. Firma digitale con certificato del server
        $signed = false;
        $cert = self::signingCertificate();
        if ($cert) {
            $pdf->setSignature($cert['crt'], $cert['key'], '', '', 1, [
                'Name'        => $c['name'],
                'Location'    => $c['address'],
                'Reason'      => 'Attestazione consenso ' . $r['id'],
                'ContactInfo' => $c['email'],
            ]);
            $signed = true;
        }

        $pdf->AddPage();
        $pdf->writeHTML(self::html($r, $dataHash, $verifyUrl, $signed), true, false, true, false, '');

        // QR code verso la pagina di verifica
        $y = $pdf->GetY() + 2;
        if ($y > 248) {
            $pdf->AddPage();
            $y = $pdf->GetY();
        }
        $pdf->write2DBarcode($verifyUrl, 'QRCODE,M', 18, $y, 28, 28, ['fgcolor' => [30, 58, 138]], 'N');
        $pdf->SetXY(50, $y + 2);
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->SetTextColor(75, 85, 99);
        $pdf->MultiCell(142, 0, "Verifica l'autenticità e lo stato attuale di questo consenso inquadrando il codice QR oppure su:\n" . $verifyUrl . "\nPer verificare che il file non sia stato alterato, caricalo nella stessa pagina: l'impronta SHA-256 del file viene confrontata con quella registrata al momento della firma.", 0, 'L');

        if ($signed) {
            $pdf->setSignatureAppearance(18, $y, 28, 28);
        }

        // Piè di pagina su ogni pagina
        $pages = $pdf->getNumPages();
        for ($i = 1; $i <= $pages; $i++) {
            $pdf->setPage($i);
            $pdf->SetAutoPageBreak(false);
            $pdf->SetY(-12);
            $pdf->SetFont('dejavusans', '', 7);
            $pdf->SetTextColor(156, 163, 175);
            $pdf->Cell(0, 5, $r['id'] . '  ·  ' . $c['name'] . '  ·  Pagina ' . $i . ' di ' . $pages, 0, 0, 'C');
        }

        $file = Storage::path('pdfs') . '/' . $r['id'] . '.pdf';
        $bytes = $pdf->Output('', 'S');
        Storage::writeAtomic($file, $bytes);

        return ['path' => $file, 'sha256' => hash('sha256', $bytes), 'signed' => $signed];
    }

    private static function html(array $r, $dataHash, $verifyUrl, $signed)
    {
        $c = cfg('company');
        $dt = new DateTime($r['timestamp']);
        $when = $dt->format('d/m/Y \o\r\e H:i:s') . ' (UTC' . $dt->format('P') . ')';

        $yes = '<span style="color:#047857;font-weight:bold;">&#10004; PRESTATO</span>';
        $no  = '<span style="color:#b91c1c;font-weight:bold;">&#10008; NON PRESTATO</span>';

        $row = function ($label, $value) {
            return '<tr><td width="30%" style="color:#6b7280;">' . e($label) . '</td><td width="70%"><b>' . $value . '</b></td></tr>';
        };

        $msg = trim((string) $r['messaggio']) !== '' ? nl2br(e($r['messaggio'])) : '<i style="color:#9ca3af;">— nessun messaggio —</i>';

        $sec = function ($title) {
            return '<h3 style="color:#1e3a8a;font-size:11pt;border-bottom:1px solid #bfdbfe;">' . $title . '</h3>';
        };

        $protection = 'Documento cifrato AES-256 con modifica disabilitata';
        $protection .= $signed
            ? '; certificato con firma digitale (DocMDP livello 1): qualunque modifica successiva invalida la firma.'
            : '.';

        return '
<table cellpadding="10" style="background-color:#1e40af;color:#ffffff;">
  <tr><td>
    <span style="font-size:15pt;font-weight:bold;">' . e($c['name']) . '</span><br>
    <span style="font-size:9pt;color:#dbeafe;">Attestazione di consenso al trattamento dei dati personali · Reg. (UE) 2016/679</span>
  </td></tr>
</table>
<br>
<table cellpadding="6" style="background-color:#eff6ff;border:1px solid #bfdbfe;">
  <tr>
    <td width="50%"><span style="color:#6b7280;font-size:8pt;">ID PRATICA</span><br><b style="font-size:12pt;color:#1e3a8a;">' . e($r['id']) . '</b></td>
    <td width="50%"><span style="color:#6b7280;font-size:8pt;">DATA E ORA DI REGISTRAZIONE</span><br><b>' . e($when) . '</b></td>
  </tr>
</table>

' . $sec('1. Dati dell\'interessato') . '
<table cellpadding="2">
' . $row('Nome', e($r['nome'])) . '
' . $row('Cognome', e($r['cognome'])) . '
' . $row('Email', e($r['email'])) . '
' . $row('Cellulare', e($r['telefono'])) . '
<tr><td width="30%" style="color:#6b7280;">Messaggio</td><td width="70%">' . $msg . '</td></tr>
</table>

' . $sec('2. Consensi espressi') . '
<table cellpadding="3" style="border:1px solid #e5e7eb;">
  <tr style="background-color:#f9fafb;"><td width="25%"><b>Privacy</b> (obbligatorio)<br>' . ($r['privacy'] ? $yes : $no) . '</td>
      <td width="75%" style="font-size:8.5pt;">&laquo;' . e($r['privacy_text']) . '&raquo;</td></tr>
  <tr><td width="25%"><b>Marketing</b> (facoltativo)<br>' . ($r['marketing'] ? $yes : $no) . '</td>
      <td width="75%" style="font-size:8.5pt;">&laquo;' . e($r['marketing_text']) . '&raquo;</td></tr>
</table>
<p style="font-size:8pt;color:#6b7280;">Versione testi e informativa: <b>' . e($r['consent_version']) . '</b>. Caselle non preselezionate, spuntate attivamente dall\'interessato.
Il consenso è revocabile in qualsiasi momento (art. 7.3 GDPR) dalla pagina &laquo;Revoca consenso&raquo; del sito o scrivendo a ' . e($c['email']) . '.'
. ($r['marketing'] && cfg('double_opt_in') ? ' Il consenso marketing è soggetto a conferma via email (double opt-in).' : '') . '</p>

' . $sec('3. Dati tecnici della raccolta') . '
<table cellpadding="2">
' . $row('Indirizzo IP', e($r['ip'])) . '
' . $row('Timestamp ISO 8601', e($r['timestamp'])) . '
' . $row('Pagina del modulo', e($r['source_url'])) . '
<tr><td width="30%" style="color:#6b7280;">Browser (User-Agent)</td><td width="70%" style="font-size:7.5pt;">' . e($r['user_agent']) . '</td></tr>
</table>

' . $sec('4. Integrità del documento') . '
<table cellpadding="3" style="background-color:#f3f4f6;">
  <tr><td><span style="font-size:8pt;color:#6b7280;">IMPRONTA DEI DATI (HMAC-SHA256)</span><br>
  <span style="font-family:dejavusansmono;font-size:8pt;">' . e($dataHash) . '</span></td></tr>
</table>
<p style="font-size:8pt;color:#4b5563;">' . e($protection) . ' L\'impronta SHA-256 del file è conservata nel registro consensi a catena di hash del titolare e inviata via email al titolare al momento della registrazione.</p>

' . $sec('5. Titolare del trattamento') . '
<p style="font-size:8.5pt;">' . e($c['name']) . ' — ' . e($c['address']) . ' — P.IVA ' . e($c['vat']) . '<br>
Email: ' . e($c['email']) . ($c['pec'] ? ' — PEC: ' . e($c['pec']) : '') . ' — Tel: ' . e($c['phone']) . '</p>';
    }

    /**
     * Certificato di firma auto-generato (autofirmato) e conservato nello storage.
     * Per una firma riconosciuta legalmente si può sostituire con un certificato
     * qualificato: basta mettere sign.crt / sign.key nella cartella storage/keys.
     */
    private static function signingCertificate()
    {
        $dir = Storage::path('keys');
        $crt = $dir . '/sign.crt';
        $key = $dir . '/sign.key';

        if (!is_file($crt) || !is_file($key)) {
            $made = Storage::withLock('sign', function () use ($crt, $key) {
                clearstatcache();
                return (is_file($crt) && is_file($key)) ? true : self::createCertificate($crt, $key);
            });
            if (!$made) {
                return null;
            }
        }
        return ['crt' => 'file://' . realpath($crt), 'key' => 'file://' . realpath($key)];
    }

    private static function createCertificate($crt, $key)
    {
        if (!function_exists('openssl_pkey_new')) {
            app_log('OpenSSL non disponibile: PDF non firmato', 'WARN');
            return false;
        }
        $c = cfg('company');
        $pkey = @openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if (!$pkey) {
            app_log('Generazione chiave di firma fallita: ' . openssl_error_string(), 'WARN');
            return false;
        }
        $dn = ['commonName' => mb_substr($c['name'], 0, 64), 'organizationName' => mb_substr($c['name'], 0, 64), 'countryName' => 'IT', 'stateOrProvinceName' => 'Italia', 'localityName' => mb_substr($c['address'], 0, 64)];
        $csr = @openssl_csr_new($dn, $pkey, ['digest_alg' => 'sha256']);
        $x509 = $csr ? @openssl_csr_sign($csr, null, $pkey, 3650, ['digest_alg' => 'sha256'], random_int(1, PHP_INT_MAX)) : false;
        if (!$x509) {
            app_log('Generazione certificato di firma fallita', 'WARN');
            return false;
        }
        openssl_x509_export($x509, $crtPem);
        openssl_pkey_export($pkey, $keyPem);
        Storage::writeAtomic($key, $keyPem);
        Storage::writeAtomic($crt, $crtPem);
        @chmod($key, 0600);
        return true;
    }
}
