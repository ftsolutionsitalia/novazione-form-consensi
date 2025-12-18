<?php
/**
 * Generatore PDF per Consensi Privacy
 *
 * Genera un documento PDF con i dati del consenso e un hash di integrità
 */

// Impedisci accesso diretto
if (!defined('NOVAZIONE_APP')) {
    die('Accesso negato');
}

require_once VENDOR_PATH . 'tcpdf/tcpdf.php';

class PDFGenerator
{
    private $pdf;
    private $data;
    private $documentHash;

    /**
     * Costruttore
     *
     * @param array $data Dati del form
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->initPDF();
    }

    /**
     * Inizializza il documento PDF
     */
    private function initPDF()
    {
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Informazioni documento
        $this->pdf->SetCreator('Novazione');
        $this->pdf->SetAuthor(AZIENDA_NOME);
        $this->pdf->SetTitle('Consenso Privacy - ' . $this->data['nome'] . ' ' . $this->data['cognome']);
        $this->pdf->SetSubject('Registrazione Consenso Privacy');

        // Disabilita header e footer di default
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);

        // Margini
        $this->pdf->SetMargins(20, 20, 20);
        $this->pdf->SetAutoPageBreak(true, 25);

        // Font
        $this->pdf->SetFont('helvetica', '', 10);

        // Protezione del PDF (non modificabile)
        $this->pdf->SetProtection(
            ['print', 'copy'], // Permessi consentiti
            '',                 // Password utente (vuota = no password per apertura)
            null,              // Password proprietario (generata automaticamente)
            0,                  // Encryption strength
            null               // Permessi aggiuntivi
        );
    }

    /**
     * Genera l'hash del documento
     *
     * @return string Hash SHA-256
     */
    private function generateHash()
    {
        $dataString = json_encode([
            'nome' => $this->data['nome'],
            'cognome' => $this->data['cognome'],
            'email' => $this->data['email'],
            'telefono' => $this->data['telefono'],
            'privacy' => $this->data['privacy'],
            'marketing' => $this->data['marketing'],
            'ip' => $this->data['ip'],
            'timestamp' => $this->data['timestamp'],
            'secret' => SECRET_KEY
        ]);

        $this->documentHash = hash(HASH_ALGORITHM, $dataString);
        return $this->documentHash;
    }

    /**
     * Genera il contenuto del PDF
     *
     * @return string Percorso del file PDF generato
     */
    public function generate()
    {
        // Genera hash
        $this->generateHash();

        // Aggiungi pagina
        $this->pdf->AddPage();

        // Header con logo
        $this->addHeader();

        // Titolo
        $this->addTitle();

        // Dati utente
        $this->addUserData();

        // Consensi
        $this->addConsents();

        // Dati tecnici
        $this->addTechnicalData();

        // Hash di integrità
        $this->addIntegrityHash();

        // Footer con note legali
        $this->addLegalFooter();

        // Salva il file
        $filename = 'consenso_' . time() . '_' . substr($this->documentHash, 0, 8) . '.pdf';
        $filepath = PDF_PATH . $filename;

        // Crea la directory se non esiste
        if (!is_dir(PDF_PATH)) {
            mkdir(PDF_PATH, 0755, true);
        }

        $this->pdf->Output($filepath, 'F');

        return $filepath;
    }

    /**
     * Aggiunge l'header del documento
     */
    private function addHeader()
    {
        // Logo placeholder (box colorato con nome azienda)
        $this->pdf->SetFillColor(37, 99, 235); // Primary blue
        $this->pdf->RoundedRect(20, 20, 170, 25, 3, '1111', 'F');

        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('helvetica', 'B', 18);
        $this->pdf->SetXY(20, 25);
        $this->pdf->Cell(170, 15, AZIENDA_NOME, 0, 0, 'C');

        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(35);
    }

    /**
     * Aggiunge il titolo del documento
     */
    private function addTitle()
    {
        $this->pdf->SetFont('helvetica', 'B', 16);
        $this->pdf->SetTextColor(30, 64, 175); // Primary-800
        $this->pdf->Cell(0, 10, 'CERTIFICATO DI CONSENSO PRIVACY', 0, 1, 'C');

        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetTextColor(107, 114, 128); // Gray-500
        $this->pdf->Cell(0, 6, 'Documento generato ai sensi del Regolamento UE 2016/679 (GDPR)', 0, 1, 'C');

        $this->pdf->Ln(10);
    }

    /**
     * Aggiunge i dati dell'utente
     */
    private function addUserData()
    {
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->SetTextColor(31, 41, 55); // Gray-800
        $this->pdf->Cell(0, 8, 'DATI DELL\'INTERESSATO', 0, 1, 'L');

        $this->pdf->SetDrawColor(229, 231, 235); // Gray-200
        $this->pdf->Line(20, $this->pdf->GetY(), 190, $this->pdf->GetY());
        $this->pdf->Ln(5);

        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetTextColor(55, 65, 81); // Gray-700

        $userData = [
            'Nome' => $this->data['nome'],
            'Cognome' => $this->data['cognome'],
            'Email' => $this->data['email'],
            'Telefono' => $this->data['telefono']
        ];

        foreach ($userData as $label => $value) {
            $this->pdf->SetFont('helvetica', 'B', 10);
            $this->pdf->Cell(40, 7, $label . ':', 0, 0, 'L');
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(0, 7, $value, 0, 1, 'L');
        }

        if (!empty($this->data['messaggio'])) {
            $this->pdf->Ln(3);
            $this->pdf->SetFont('helvetica', 'B', 10);
            $this->pdf->Cell(40, 7, 'Messaggio:', 0, 1, 'L');
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->MultiCell(0, 6, $this->data['messaggio'], 0, 'L');
        }

        $this->pdf->Ln(10);
    }

    /**
     * Aggiunge i consensi
     */
    private function addConsents()
    {
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->SetTextColor(31, 41, 55);
        $this->pdf->Cell(0, 8, 'CONSENSI ESPRESSI', 0, 1, 'L');

        $this->pdf->SetDrawColor(229, 231, 235);
        $this->pdf->Line(20, $this->pdf->GetY(), 190, $this->pdf->GetY());
        $this->pdf->Ln(5);

        // Consenso Privacy
        $this->addConsentRow(
            'Consenso al trattamento dei dati personali',
            $this->data['privacy'],
            'Art. 6 GDPR - Trattamento necessario per rispondere alla richiesta'
        );

        // Consenso Marketing
        $this->addConsentRow(
            'Consenso per finalità di marketing',
            $this->data['marketing'],
            'Art. 7 GDPR - Consenso esplicito per comunicazioni commerciali'
        );

        $this->pdf->Ln(10);
    }

    /**
     * Aggiunge una riga di consenso
     */
    private function addConsentRow($title, $accepted, $description)
    {
        $this->pdf->SetFont('helvetica', '', 10);

        // Checkbox
        $this->pdf->SetFillColor($accepted ? 16 : 239, $accepted ? 185 : 68, $accepted ? 129 : 68);
        $this->pdf->RoundedRect($this->pdf->GetX(), $this->pdf->GetY() + 1, 5, 5, 1, '1111', 'F');

        if ($accepted) {
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->SetFont('zapfdingbats', '', 8);
            $this->pdf->SetXY($this->pdf->GetX() + 0.5, $this->pdf->GetY() + 0.5);
            $this->pdf->Cell(5, 5, '4', 0, 0, 'C'); // Checkmark
        }

        $this->pdf->SetTextColor(55, 65, 81);
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->SetX(30);
        $this->pdf->Cell(0, 7, $title . ': ' . ($accepted ? 'ACCETTATO' : 'NON ACCETTATO'), 0, 1, 'L');

        $this->pdf->SetFont('helvetica', 'I', 9);
        $this->pdf->SetTextColor(107, 114, 128);
        $this->pdf->SetX(30);
        $this->pdf->Cell(0, 5, $description, 0, 1, 'L');

        $this->pdf->Ln(3);
    }

    /**
     * Aggiunge i dati tecnici
     */
    private function addTechnicalData()
    {
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->SetTextColor(31, 41, 55);
        $this->pdf->Cell(0, 8, 'DATI TECNICI DI REGISTRAZIONE', 0, 1, 'L');

        $this->pdf->SetDrawColor(229, 231, 235);
        $this->pdf->Line(20, $this->pdf->GetY(), 190, $this->pdf->GetY());
        $this->pdf->Ln(5);

        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetTextColor(55, 65, 81);

        $techData = [
            'Data e Ora' => date('d/m/Y H:i:s', strtotime($this->data['timestamp'])),
            'Indirizzo IP' => $this->data['ip'],
            'Fuso Orario' => 'Europe/Rome (UTC+1)'
        ];

        foreach ($techData as $label => $value) {
            $this->pdf->SetFont('helvetica', 'B', 10);
            $this->pdf->Cell(50, 7, $label . ':', 0, 0, 'L');
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(0, 7, $value, 0, 1, 'L');
        }

        $this->pdf->Ln(10);
    }

    /**
     * Aggiunge l'hash di integrità
     */
    private function addIntegrityHash()
    {
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->SetTextColor(31, 41, 55);
        $this->pdf->Cell(0, 8, 'HASH DI INTEGRITA\' DOCUMENTO', 0, 1, 'L');

        $this->pdf->SetDrawColor(229, 231, 235);
        $this->pdf->Line(20, $this->pdf->GetY(), 190, $this->pdf->GetY());
        $this->pdf->Ln(5);

        // Box con hash
        $this->pdf->SetFillColor(243, 244, 246); // Gray-100
        $this->pdf->RoundedRect(20, $this->pdf->GetY(), 170, 20, 3, '1111', 'F');

        $this->pdf->SetFont('courier', '', 9);
        $this->pdf->SetTextColor(55, 65, 81);
        $this->pdf->SetXY(25, $this->pdf->GetY() + 3);
        $this->pdf->Cell(160, 6, 'Algoritmo: ' . strtoupper(HASH_ALGORITHM), 0, 1, 'L');
        $this->pdf->SetX(25);
        $this->pdf->Cell(160, 6, 'Hash: ' . $this->documentHash, 0, 1, 'L');

        $this->pdf->Ln(5);
        $this->pdf->SetFont('helvetica', 'I', 8);
        $this->pdf->SetTextColor(107, 114, 128);
        $this->pdf->MultiCell(170, 4, 'Questo hash garantisce l\'integrità del documento. Qualsiasi modifica ai dati originali produrrebbe un hash differente.', 0, 'L');

        $this->pdf->Ln(10);
    }

    /**
     * Aggiunge il footer legale
     */
    private function addLegalFooter()
    {
        // Vai in fondo alla pagina
        $this->pdf->SetY(-60);

        $this->pdf->SetDrawColor(229, 231, 235);
        $this->pdf->Line(20, $this->pdf->GetY(), 190, $this->pdf->GetY());
        $this->pdf->Ln(5);

        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->SetTextColor(55, 65, 81);
        $this->pdf->Cell(0, 5, 'TITOLARE DEL TRATTAMENTO', 0, 1, 'L');

        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->SetTextColor(107, 114, 128);
        $this->pdf->MultiCell(0, 4, AZIENDA_NOME . "\n" . AZIENDA_INDIRIZZO . "\nP.IVA: " . AZIENDA_PIVA . "\nEmail: " . AZIENDA_EMAIL . " | Tel: " . AZIENDA_TELEFONO, 0, 'L');

        $this->pdf->Ln(5);
        $this->pdf->SetFont('helvetica', 'I', 8);
        $this->pdf->MultiCell(0, 4, 'Documento generato automaticamente. La validità legale del consenso è garantita dalla registrazione elettronica dei dati e dall\'hash di integrità.', 0, 'L');
    }

    /**
     * Restituisce l'hash del documento
     *
     * @return string
     */
    public function getHash()
    {
        return $this->documentHash;
    }
}
