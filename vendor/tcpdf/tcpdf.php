<?php
/**
 * TCPDF Wrapper - Simplified PDF Generator
 *
 * Questa è una versione semplificata per la generazione di PDF.
 * Per un utilizzo in produzione, installare TCPDF tramite Composer:
 * composer require tecnickcom/tcpdf
 */

// Costanti PDF
define('PDF_PAGE_ORIENTATION', 'P');
define('PDF_UNIT', 'mm');
define('PDF_PAGE_FORMAT', 'A4');

class TCPDF
{
    protected $pdf;
    protected $pageWidth = 210;
    protected $pageHeight = 297;
    protected $margin = ['left' => 20, 'top' => 20, 'right' => 20];
    protected $x = 20;
    protected $y = 20;
    protected $fontFamily = 'helvetica';
    protected $fontSize = 10;
    protected $fontStyle = '';
    protected $textColor = [0, 0, 0];
    protected $drawColor = [0, 0, 0];
    protected $fillColor = [255, 255, 255];
    protected $pages = [];
    protected $currentPage = -1;
    protected $content = '';
    protected $protection = [];
    protected $info = [];
    protected $autoPageBreak = true;
    protected $pageBreakTrigger = 272;

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false)
    {
        $this->pageWidth = 210;
        $this->pageHeight = 297;
    }

    public function SetCreator($creator) { $this->info['creator'] = $creator; }
    public function SetAuthor($author) { $this->info['author'] = $author; }
    public function SetTitle($title) { $this->info['title'] = $title; }
    public function SetSubject($subject) { $this->info['subject'] = $subject; }

    public function setPrintHeader($val) {}
    public function setPrintFooter($val) {}

    public function SetMargins($left, $top, $right = -1)
    {
        $this->margin['left'] = $left;
        $this->margin['top'] = $top;
        $this->margin['right'] = $right > 0 ? $right : $left;
        $this->x = $left;
        $this->y = $top;
    }

    public function SetAutoPageBreak($auto, $margin = 0)
    {
        $this->autoPageBreak = $auto;
        $this->pageBreakTrigger = $this->pageHeight - $margin;
    }

    public function SetFont($family, $style = '', $size = 0)
    {
        $this->fontFamily = strtolower($family);
        $this->fontStyle = $style;
        if ($size > 0) {
            $this->fontSize = $size;
        }
    }

    public function SetProtection($permissions = [], $userPass = '', $ownerPass = null, $mode = 0, $pubkeys = null)
    {
        $this->protection = [
            'permissions' => $permissions,
            'user_pass' => $userPass,
            'owner_pass' => $ownerPass
        ];
    }

    public function AddPage($orientation = '', $format = '')
    {
        $this->currentPage++;
        $this->pages[$this->currentPage] = '';
        $this->y = $this->margin['top'];
        $this->x = $this->margin['left'];
    }

    public function SetTextColor($r, $g = -1, $b = -1)
    {
        if ($g == -1) {
            $this->textColor = [$r, $r, $r];
        } else {
            $this->textColor = [$r, $g, $b];
        }
    }

    public function SetDrawColor($r, $g = -1, $b = -1)
    {
        if ($g == -1) {
            $this->drawColor = [$r, $r, $r];
        } else {
            $this->drawColor = [$r, $g, $b];
        }
    }

    public function SetFillColor($r, $g = -1, $b = -1)
    {
        if ($g == -1) {
            $this->fillColor = [$r, $r, $r];
        } else {
            $this->fillColor = [$r, $g, $b];
        }
    }

    public function GetX() { return $this->x; }
    public function GetY() { return $this->y; }
    public function SetX($x) { $this->x = $x; }
    public function SetY($y) { $this->y = $y; }
    public function SetXY($x, $y) { $this->x = $x; $this->y = $y; }

    public function Ln($h = null)
    {
        $this->x = $this->margin['left'];
        $this->y += ($h !== null) ? $h : $this->fontSize * 0.4;
    }

    public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
    {
        $this->pages[$this->currentPage] .= $this->formatText($txt, $w, $h, $align);

        if ($ln == 1) {
            $this->Ln($h);
        } elseif ($ln == 0) {
            $this->x += $w;
        }
    }

    public function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false, $ln = 1, $x = '', $y = '', $reseth = true, $stretch = 0, $ishtml = false, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false)
    {
        $lines = explode("\n", wordwrap($txt, intval($w / 2), "\n", true));
        foreach ($lines as $line) {
            $this->Cell($w, $h, $line, 0, 1, $align);
        }
    }

    public function Line($x1, $y1, $x2, $y2)
    {
        $this->pages[$this->currentPage] .= "<!-- line from ($x1,$y1) to ($x2,$y2) -->";
    }

    public function RoundedRect($x, $y, $w, $h, $r, $round_corner = '1111', $style = '')
    {
        $this->pages[$this->currentPage] .= "<!-- rounded rect at ($x,$y) size ($w,$h) -->";
    }

    protected function formatText($txt, $w, $h, $align)
    {
        return htmlspecialchars($txt) . " ";
    }

    public function Output($name = 'doc.pdf', $dest = 'I')
    {
        // Generate a proper PDF
        $pdf = $this->generatePDF();

        switch ($dest) {
            case 'F':
                file_put_contents($name, $pdf);
                break;
            case 'D':
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . basename($name) . '"');
                echo $pdf;
                break;
            case 'I':
            default:
                header('Content-Type: application/pdf');
                echo $pdf;
                break;
        }
    }

    protected function generatePDF()
    {
        // Collect all text content
        $textContent = '';
        foreach ($this->pages as $page) {
            $textContent .= strip_tags($page);
        }

        // Create a minimal PDF structure
        $objects = [];
        $objectId = 0;

        // PDF Header
        $pdf = "%PDF-1.4\n";
        $pdf .= "%âãÏÓ\n";

        // Object 1: Catalog
        $objectId++;
        $objects[$objectId] = strlen($pdf);
        $pdf .= "$objectId 0 obj\n";
        $pdf .= "<< /Type /Catalog /Pages 2 0 R >>\n";
        $pdf .= "endobj\n";

        // Object 2: Pages
        $objectId++;
        $objects[$objectId] = strlen($pdf);
        $pdf .= "$objectId 0 obj\n";
        $pdf .= "<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n";
        $pdf .= "endobj\n";

        // Object 3: Page
        $objectId++;
        $objects[$objectId] = strlen($pdf);
        $pdf .= "$objectId 0 obj\n";
        $pdf .= "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\n";
        $pdf .= "endobj\n";

        // Object 4: Content Stream
        $contentStream = $this->generateContentStream();
        $objectId++;
        $objects[$objectId] = strlen($pdf);
        $pdf .= "$objectId 0 obj\n";
        $pdf .= "<< /Length " . strlen($contentStream) . " >>\n";
        $pdf .= "stream\n";
        $pdf .= $contentStream;
        $pdf .= "\nendstream\n";
        $pdf .= "endobj\n";

        // Object 5: Font
        $objectId++;
        $objects[$objectId] = strlen($pdf);
        $pdf .= "$objectId 0 obj\n";
        $pdf .= "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\n";
        $pdf .= "endobj\n";

        // Object 6: Info
        $objectId++;
        $objects[$objectId] = strlen($pdf);
        $pdf .= "$objectId 0 obj\n";
        $pdf .= "<< /Title (" . ($this->info['title'] ?? 'Consenso Privacy') . ") ";
        $pdf .= "/Author (" . ($this->info['author'] ?? 'Novazione') . ") ";
        $pdf .= "/Creator (" . ($this->info['creator'] ?? 'Novazione Form') . ") ";
        $pdf .= "/CreationDate (D:" . date('YmdHis') . ") >>\n";
        $pdf .= "endobj\n";

        // XRef table
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 " . ($objectId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $objectId; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $objects[$i]);
        }

        // Trailer
        $pdf .= "trailer\n";
        $pdf .= "<< /Size " . ($objectId + 1) . " /Root 1 0 R /Info 6 0 R >>\n";
        $pdf .= "startxref\n";
        $pdf .= "$xrefOffset\n";
        $pdf .= "%%EOF";

        return $pdf;
    }

    protected function generateContentStream()
    {
        $content = "BT\n";
        $content .= "/F1 12 Tf\n";
        $content .= "50 800 Td\n";

        // Title
        $content .= "/F1 18 Tf\n";
        $content .= "0 0 0 rg\n";
        $content .= "(CERTIFICATO DI CONSENSO PRIVACY) Tj\n";
        $content .= "0 -30 Td\n";

        // Subtitle
        $content .= "/F1 10 Tf\n";
        $content .= "0.5 0.5 0.5 rg\n";
        $content .= "(Documento generato ai sensi del Regolamento UE 2016/679 - GDPR) Tj\n";
        $content .= "0 -40 Td\n";

        // Main content
        $content .= "0 0 0 rg\n";
        $content .= "/F1 10 Tf\n";

        $y = 0;
        foreach ($this->pages as $pageContent) {
            $text = strip_tags($pageContent);
            $text = preg_replace('/\s+/', ' ', $text);
            $words = explode(' ', $text);

            $line = '';
            foreach ($words as $word) {
                if (strlen($line . ' ' . $word) > 80) {
                    $cleanLine = $this->cleanPdfString($line);
                    $content .= "($cleanLine) Tj\n";
                    $content .= "0 -14 Td\n";
                    $line = $word;
                    $y += 14;
                } else {
                    $line .= ($line ? ' ' : '') . $word;
                }
            }
            if ($line) {
                $cleanLine = $this->cleanPdfString($line);
                $content .= "($cleanLine) Tj\n";
                $content .= "0 -14 Td\n";
            }
        }

        $content .= "ET";
        return $content;
    }

    protected function cleanPdfString($str)
    {
        // Escape special PDF characters
        $str = str_replace('\\', '\\\\', $str);
        $str = str_replace('(', '\\(', $str);
        $str = str_replace(')', '\\)', $str);
        // Remove non-ASCII characters for basic PDF
        $str = preg_replace('/[^\x20-\x7E]/', '', $str);
        return $str;
    }
}
