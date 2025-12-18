<?php
/**
 * FPDF-based PDF Generator
 * Libreria leggera per generazione PDF
 */

define('PDF_PAGE_ORIENTATION', 'P');
define('PDF_UNIT', 'mm');
define('PDF_PAGE_FORMAT', 'A4');

class TCPDF
{
    protected $page = 0;
    protected $buffer = '';
    protected $pages = [];
    protected $state = 0;
    protected $x = 0;
    protected $y = 0;
    protected $lasth = 0;
    protected $fontFamily = '';
    protected $fontStyle = '';
    protected $fontSize = 12;
    protected $fontSizePt = 12;
    protected $drawColor = '0 G';
    protected $fillColor = '0 g';
    protected $textColor = '0 g';
    protected $ws = 0;
    protected $images = [];
    protected $pageLinks = [];
    protected $links = [];
    protected $fontFiles = [];
    protected $diffs = [];
    protected $fonts = [];
    protected $currentFont;
    protected $n = 2;
    protected $offsets = [];
    protected $info = [];
    protected $autoPageBreak = true;
    protected $pageBreakTrigger;
    protected $lMargin = 20;
    protected $tMargin = 20;
    protected $rMargin = 20;
    protected $bMargin = 20;
    protected $cMargin = 1;
    protected $w = 210;
    protected $h = 297;
    protected $k = 2.83465; // scale factor (points per mm)
    protected $protection = [];

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false)
    {
        $this->w = 210;
        $this->h = 297;
        $this->pageBreakTrigger = $this->h - $this->bMargin;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;

        // Initialize core fonts
        $this->fonts['helvetica'] = ['name' => 'Helvetica', 'up' => -100, 'ut' => 50];
        $this->fonts['helveticab'] = ['name' => 'Helvetica-Bold', 'up' => -100, 'ut' => 50];
        $this->fonts['helveticai'] = ['name' => 'Helvetica-Oblique', 'up' => -100, 'ut' => 50];
        $this->fonts['courier'] = ['name' => 'Courier', 'up' => -100, 'ut' => 50];
    }

    public function SetCreator($creator) { $this->info['Creator'] = $creator; }
    public function SetAuthor($author) { $this->info['Author'] = $author; }
    public function SetTitle($title) { $this->info['Title'] = $title; }
    public function SetSubject($subject) { $this->info['Subject'] = $subject; }
    public function setPrintHeader($val) {}
    public function setPrintFooter($val) {}

    public function SetMargins($left, $top, $right = -1)
    {
        $this->lMargin = $left;
        $this->tMargin = $top;
        $this->rMargin = ($right == -1) ? $left : $right;
    }

    public function SetAutoPageBreak($auto, $margin = 0)
    {
        $this->autoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->pageBreakTrigger = $this->h - $margin;
    }

    public function SetFont($family, $style = '', $size = 0)
    {
        $family = strtolower($family);
        if ($family == 'arial') $family = 'helvetica';

        $this->fontFamily = $family;
        $this->fontStyle = strtoupper($style);
        if ($size > 0) {
            $this->fontSizePt = $size;
            $this->fontSize = $size / $this->k;
        }
    }

    public function SetProtection($permissions = [], $userPass = '', $ownerPass = null, $mode = 0, $pubkeys = null)
    {
        $this->protection = ['enabled' => true];
    }

    public function AddPage($orientation = '', $format = '')
    {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->lasth = 0;
    }

    public function SetTextColor($r, $g = -1, $b = -1)
    {
        if ($g == -1) {
            $this->textColor = sprintf('%.3F g', $r / 255);
        } else {
            $this->textColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
        }
    }

    public function SetDrawColor($r, $g = -1, $b = -1)
    {
        if ($g == -1) {
            $this->drawColor = sprintf('%.3F G', $r / 255);
        } else {
            $this->drawColor = sprintf('%.3F %.3F %.3F RG', $r / 255, $g / 255, $b / 255);
        }
    }

    public function SetFillColor($r, $g = -1, $b = -1)
    {
        if ($g == -1) {
            $this->fillColor = sprintf('%.3F g', $r / 255);
        } else {
            $this->fillColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
        }
    }

    public function GetX() { return $this->x; }
    public function GetY() { return $this->y; }
    public function SetX($x) { $this->x = ($x >= 0) ? $x : $this->w + $x; }
    public function SetY($y, $resetX = true) {
        if ($resetX) $this->x = $this->lMargin;
        $this->y = ($y >= 0) ? $y : $this->h + $y;
    }
    public function SetXY($x, $y) { $this->SetX($x); $this->SetY($y, false); }

    public function Ln($h = null)
    {
        $this->x = $this->lMargin;
        $this->y += ($h === null) ? $this->lasth : $h;
    }

    public function GetStringWidth($s)
    {
        return strlen($s) * $this->fontSize * 0.5;
    }

    public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
    {
        if ($h == 0) $h = $this->fontSize;

        $k = $this->k;
        $x = $this->x;
        $y = $this->y;

        $s = '';

        // Fill
        if ($fill) {
            $s .= sprintf('%.2F %.2F %.2F %.2F re f ', $x * $k, ($this->h - $y) * $k, $w * $k, -$h * $k);
        }

        // Border
        if ($border) {
            $s .= sprintf('%.2F %.2F %.2F %.2F re S ', $x * $k, ($this->h - $y) * $k, $w * $k, -$h * $k);
        }

        // Text
        if ($txt !== '') {
            $txt = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $txt);
            $txt = $this->convertToASCII($txt);

            // Alignment
            if ($align == 'R') {
                $dx = $w - $this->cMargin - $this->GetStringWidth($txt);
            } elseif ($align == 'C') {
                $dx = ($w - $this->GetStringWidth($txt)) / 2;
            } else {
                $dx = $this->cMargin;
            }

            $s .= sprintf('BT %.2F %.2F Td (%s) Tj ET ',
                ($x + $dx) * $k,
                ($this->h - $y - $this->fontSize * 0.85) * $k,
                $txt
            );
        }

        if ($s) {
            $this->pages[$this->page] .= $s;
        }

        $this->lasth = $h;

        if ($ln == 1) {
            $this->y += $h;
            $this->x = $this->lMargin;
        } elseif ($ln == 0) {
            $this->x += $w;
        }
    }

    public function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false, $ln = 1, $x = '', $y = '', $reseth = true, $stretch = 0, $ishtml = false, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false)
    {
        if ($w == 0) $w = $this->w - $this->rMargin - $this->x;

        $txt = str_replace("\r", '', $txt);
        $lines = explode("\n", $txt);

        foreach ($lines as $line) {
            // Word wrap
            $words = explode(' ', $line);
            $currentLine = '';

            foreach ($words as $word) {
                $testLine = $currentLine . ($currentLine ? ' ' : '') . $word;
                if ($this->GetStringWidth($testLine) > $w - 2 * $this->cMargin && $currentLine) {
                    $this->Cell($w, $h, $currentLine, 0, 1, $align, $fill);
                    $currentLine = $word;
                } else {
                    $currentLine = $testLine;
                }
            }
            if ($currentLine) {
                $this->Cell($w, $h, $currentLine, 0, 1, $align, $fill);
            }
        }
    }

    public function Line($x1, $y1, $x2, $y2)
    {
        $k = $this->k;
        $this->pages[$this->page] .= sprintf('%.2F %.2F m %.2F %.2F l S ',
            $x1 * $k, ($this->h - $y1) * $k,
            $x2 * $k, ($this->h - $y2) * $k
        );
    }

    public function RoundedRect($x, $y, $w, $h, $r, $corners = '1111', $style = '')
    {
        $k = $this->k;
        $op = ($style == 'F') ? 'f' : (($style == 'FD' || $style == 'DF') ? 'B' : 'S');

        // Simplified: draw a regular rectangle
        $this->pages[$this->page] .= sprintf('%.2F %.2F %.2F %.2F re %s ',
            $x * $k, ($this->h - $y - $h) * $k, $w * $k, $h * $k, $op
        );
    }

    protected function convertToASCII($txt)
    {
        $trans = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ñ' => 'n', 'ç' => 'c',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ñ' => 'N', 'Ç' => 'C',
            ''' => "'", '"' => '"', '"' => '"', '–' => '-', '—' => '-',
            '€' => 'EUR', '£' => 'GBP', '©' => '(c)', '®' => '(R)', '™' => '(TM)'
        ];
        return strtr($txt, $trans);
    }

    public function Output($name = 'doc.pdf', $dest = 'I')
    {
        $this->Close();

        switch ($dest) {
            case 'F':
                $f = fopen($name, 'wb');
                fwrite($f, $this->buffer);
                fclose($f);
                break;
            case 'D':
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . basename($name) . '"');
                header('Content-Length: ' . strlen($this->buffer));
                echo $this->buffer;
                break;
            case 'I':
            default:
                header('Content-Type: application/pdf');
                header('Content-Length: ' . strlen($this->buffer));
                echo $this->buffer;
                break;
        }

        return $name;
    }

    protected function Close()
    {
        if ($this->state == 3) return;
        if ($this->page == 0) $this->AddPage();
        $this->state = 3;
        $this->generatePDF();
    }

    protected function generatePDF()
    {
        $this->buffer = '';
        $this->n = 2;
        $this->offsets = [];

        $this->putHeader();
        $this->putPages();
        $this->putResources();
        $this->putInfo();
        $this->putCatalog();
        $this->putTrailer();
    }

    protected function putHeader()
    {
        $this->buffer .= "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n";
    }

    protected function newObj($n = null)
    {
        if ($n === null) $n = ++$this->n;
        $this->offsets[$n] = strlen($this->buffer);
        $this->buffer .= $n . " 0 obj\n";
        return $n;
    }

    protected function putStream($data)
    {
        $this->buffer .= "stream\n" . $data . "\nendstream\n";
    }

    protected function putPages()
    {
        $nb = $this->page;
        $wPt = $this->w * $this->k;
        $hPt = $this->h * $this->k;

        $kids = [];
        for ($i = 1; $i <= $nb; $i++) {
            $this->newObj();
            $content = $this->pages[$i];

            // Add font and color settings
            $pageContent = "2 J\n"; // Line cap
            $pageContent .= "0.57 w\n"; // Line width
            $pageContent .= $this->drawColor . "\n";
            $pageContent .= $this->fillColor . "\n";
            $pageContent .= $this->textColor . "\n";
            $pageContent .= "BT /F1 " . $this->fontSizePt . " Tf ET\n";
            $pageContent .= $content;

            $this->buffer .= "<</Type /Page /Parent 1 0 R /MediaBox [0 0 " . sprintf('%.2F %.2F', $wPt, $hPt) . "] ";
            $this->buffer .= "/Contents " . ($this->n + 1) . " 0 R /Resources 2 0 R>>\n";
            $this->buffer .= "endobj\n";
            $kids[] = $this->n . " 0 R";

            // Content stream
            $this->newObj();
            $this->buffer .= "<</Length " . strlen($pageContent) . ">>\n";
            $this->putStream($pageContent);
            $this->buffer .= "endobj\n";
        }

        // Pages object
        $this->offsets[1] = strlen($this->buffer);
        $this->buffer .= "1 0 obj\n";
        $this->buffer .= "<</Type /Pages /Kids [" . implode(' ', $kids) . "] /Count " . $nb . ">>\n";
        $this->buffer .= "endobj\n";
    }

    protected function putResources()
    {
        // Resources object
        $this->offsets[2] = strlen($this->buffer);
        $this->buffer .= "2 0 obj\n";
        $this->buffer .= "<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]\n";
        $this->buffer .= "/Font <</F1 " . ($this->n + 1) . " 0 R>>>>\n";
        $this->buffer .= "endobj\n";

        // Font object
        $this->newObj();
        $this->buffer .= "<</Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding>>\n";
        $this->buffer .= "endobj\n";
    }

    protected function putInfo()
    {
        $this->newObj();
        $this->buffer .= "<<\n";
        if (isset($this->info['Title'])) $this->buffer .= "/Title (" . $this->textString($this->info['Title']) . ")\n";
        if (isset($this->info['Author'])) $this->buffer .= "/Author (" . $this->textString($this->info['Author']) . ")\n";
        if (isset($this->info['Subject'])) $this->buffer .= "/Subject (" . $this->textString($this->info['Subject']) . ")\n";
        if (isset($this->info['Creator'])) $this->buffer .= "/Creator (" . $this->textString($this->info['Creator']) . ")\n";
        $this->buffer .= "/CreationDate (D:" . date('YmdHis') . ")\n";
        $this->buffer .= "/Producer (Novazione PDF Generator)\n";
        $this->buffer .= ">>\n";
        $this->buffer .= "endobj\n";
        $this->infoObj = $this->n;
    }

    protected function putCatalog()
    {
        $this->newObj();
        $this->buffer .= "<</Type /Catalog /Pages 1 0 R>>\n";
        $this->buffer .= "endobj\n";
        $this->catalogObj = $this->n;
    }

    protected function putTrailer()
    {
        $o = strlen($this->buffer);
        $this->buffer .= "xref\n";
        $this->buffer .= "0 " . ($this->n + 1) . "\n";
        $this->buffer .= "0000000000 65535 f \n";

        for ($i = 1; $i <= $this->n; $i++) {
            $offset = isset($this->offsets[$i]) ? $this->offsets[$i] : 0;
            $this->buffer .= sprintf("%010d 00000 n \n", $offset);
        }

        $this->buffer .= "trailer\n";
        $this->buffer .= "<</Size " . ($this->n + 1) . " /Root " . $this->catalogObj . " 0 R /Info " . $this->infoObj . " 0 R>>\n";
        $this->buffer .= "startxref\n";
        $this->buffer .= $o . "\n";
        $this->buffer .= "%%EOF";
    }

    protected function textString($s)
    {
        $s = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
        return $this->convertToASCII($s);
    }

    protected $infoObj;
    protected $catalogObj;
}
