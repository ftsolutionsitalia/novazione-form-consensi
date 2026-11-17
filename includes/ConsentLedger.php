<?php
/**
 * Registro consensi a prova di manomissione (append-only, a catena di hash).
 *
 * Ogni riga è un evento JSON che contiene l'hash dell'evento precedente;
 * l'hash è un HMAC-SHA256 con chiave segreta del server. Modificare,
 * cancellare o riordinare una riga rompe la catena ed è rilevato da verify().
 * Inoltre l'hash di ogni evento viene inviato per email all'amministratore:
 * una copia esterna al server che fa da "ancora" indipendente.
 */

if (!defined('NOVAZIONE_APP')) {
    http_response_code(403);
    exit;
}

class ConsentLedger
{
    const GENESIS = '0000000000000000000000000000000000000000000000000000000000000000';

    private static function file()
    {
        return Storage::path('ledger') . '/consents.jsonl';
    }

    public static function newId()
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $s = '';
        for ($i = 0; $i < 8; $i++) {
            $s .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return 'CNS-' . date('Ymd') . '-' . $s;
    }

    /** json_encode che non fallisce mai in silenzio (UTF-8 non valido sostituito). */
    public static function json($data, $flags = 0)
    {
        $out = json_encode($data, $flags | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($out === false) {
            throw new RuntimeException('Serializzazione JSON fallita: ' . json_last_error_msg());
        }
        return $out;
    }

    /** JSON canonico (chiavi ordinate) per un hash stabile. */
    public static function canonical(array $data)
    {
        $sort = function (&$a) use (&$sort) {
            if (is_array($a)) {
                ksort($a);
                foreach ($a as &$v) {
                    $sort($v);
                }
            }
        };
        $sort($data);
        return self::json($data);
    }

    private static function sign(array $entry)
    {
        unset($entry['hash']);
        return hash_hmac('sha256', self::canonical($entry), Storage::secretKey());
    }

    /** Ultima riga non vuota del file, leggendo a blocchi dalla fine. */
    private static function lastLine($fp)
    {
        fseek($fp, 0, SEEK_END);
        $pos = ftell($fp);
        $buf = '';
        while ($pos > 0) {
            $chunk = min(8192, $pos);
            $pos -= $chunk;
            fseek($fp, $pos);
            $buf = fread($fp, $chunk) . $buf;
            $trimmed = rtrim($buf, "\r\n");
            $nl = strrpos($trimmed, "\n");
            if ($nl !== false) {
                return substr($trimmed, $nl + 1);
            }
        }
        $trimmed = rtrim($buf, "\r\n");
        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Aggiunge un evento in modo atomico rispetto ad altri invii concorrenti.
     * @return array l'evento salvato, con seq, prev e hash
     */
    public static function append($type, array $payload)
    {
        $fp = fopen(self::file(), 'c+');
        if (!$fp || !flock($fp, LOCK_EX)) {
            throw new RuntimeException('Registro consensi non accessibile.');
        }
        try {
            // Legge solo l'ultima riga (dalla fine del file), non l'intero registro
            $seq = 0;
            $prev = self::GENESIS;
            $last = self::lastLine($fp);
            if ($last !== null) {
                $decoded = json_decode($last, true);
                if (!is_array($decoded) || !isset($decoded['seq'], $decoded['hash'])) {
                    throw new RuntimeException('Ultima riga del registro illeggibile: verificare il file.');
                }
                $seq = (int) $decoded['seq'];
                $prev = $decoded['hash'];
            }

            $entry = [
                'seq'  => $seq + 1,
                'type' => $type,
                'at'   => date('c'),
                'prev' => $prev,
            ] + $payload;
            $entry['hash'] = self::sign($entry);

            fseek($fp, 0, SEEK_END);
            fwrite($fp, self::json($entry) . "\n");
            fflush($fp);
            @chmod(self::file(), 0640);
            return $entry;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    // ------------------------------------------------ Dati personali separati
    // Il registro contiene solo ID, stati e impronte. I dati personali stanno in
    // records/<ID>.json: si possono cancellare (art. 17 GDPR) senza rompere la
    // catena, e l'HMAC nel registro prova che il file non è stato alterato.

    public static function emailKey($email)
    {
        return hash_hmac('sha256', 'email|' . mb_strtolower(trim($email), 'UTF-8'), Storage::secretKey());
    }

    private static function recordFile($id)
    {
        if (!preg_match('/^CNS-\d{8}-[A-Z0-9]{8}$/', $id)) {
            throw new InvalidArgumentException('ID non valido');
        }
        return Storage::path('records') . '/' . $id . '.json';
    }

    public static function saveRecord(array $record)
    {
        Storage::writeAtomic(self::recordFile($record['id']), self::json($record, JSON_PRETTY_PRINT));
    }

    public static function loadRecord($id)
    {
        $f = self::recordFile($id);
        if (!is_file($f)) {
            return null;
        }
        $r = json_decode((string) file_get_contents($f), true);
        return is_array($r) ? $r : null;
    }

    /** Cancella dati personali e PDF di una pratica, lasciando nel registro solo le impronte. */
    public static function erase($id, $reason)
    {
        $f = self::recordFile($id);
        $pdf = Storage::path('pdfs') . '/' . $id . '.pdf';
        $had = is_file($f) || is_file($pdf);
        @unlink($f);
        @unlink($pdf);
        return self::append('erasure', ['ref' => $id, 'reason' => $reason, 'had_data' => $had]);
    }

    /** Tutti gli eventi, dal più vecchio. */
    public static function all()
    {
        $file = self::file();
        if (!is_file($file)) {
            return [];
        }
        $out = [];
        $fp = fopen($file, 'r');
        flock($fp, LOCK_SH);
        while (($line = fgets($fp)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $row = json_decode($line, true);
            $out[] = is_array($row) ? $row : ['_corrupt' => $line];
        }
        flock($fp, LOCK_UN);
        fclose($fp);
        return $out;
    }

    /**
     * Pratica completa: evento del registro + stato derivato dagli eventi successivi
     * (+ dati personali se $withPersonal e non cancellati).
     */
    public static function findConsent($id, $withPersonal = false, $all = null)
    {
        $all = $all ?? self::all();
        $entry = null;
        foreach ($all as $row) {
            if (($row['type'] ?? '') === 'consent' && ($row['id'] ?? '') === $id) {
                $entry = $row;
                break;
            }
        }
        if (!$entry) {
            return null;
        }
        return self::withStatus($entry, self::eventIndex($all), $withPersonal);
    }

    /** Indice ID pratica => eventi successivi, costruito in un solo passaggio. */
    private static function eventIndex(array $all)
    {
        $index = [];
        foreach ($all as $ev) {
            if (($ev['type'] ?? '') === 'consent') {
                continue;
            }
            $ids = (array) ($ev['refs'] ?? []);
            if (isset($ev['ref'])) {
                $ids[] = $ev['ref'];
            }
            foreach (array_unique($ids) as $id) {
                $index[$id][] = $ev;
            }
        }
        return $index;
    }

    private static function withStatus(array $entry, array $index, $withPersonal)
    {
        $id = $entry['id'];
        $entry['status'] = ['confirmed' => !empty($entry['marketing_confirmed']) ? $entry['at'] : null, 'withdrawn' => null, 'erased' => null, 'delivery' => null];
        foreach ($index[$id] ?? [] as $ev) {
            $t = $ev['type'] ?? '';
            if ($t === 'marketing_confirmed') {
                $entry['status']['confirmed'] = $ev['at'];
            } elseif ($t === 'withdrawal') {
                $entry['status']['withdrawn'] = $ev;
            } elseif ($t === 'erasure') {
                $entry['status']['erased'] = $ev['at'];
            } elseif ($t === 'delivery') {
                $entry['status']['delivery'] = $ev;
            }
        }
        if ($withPersonal) {
            $rec = $entry['status']['erased'] ? null : self::loadRecord($id);
            $entry['record'] = $rec;
            $entry['record_ok'] = $rec ? hash_equals($entry['data_hmac'], PDFGenerator::dataHash($rec)) : null;
        }
        return $entry;
    }

    /** Tutte le pratiche (più recenti prima) con stato e dati personali. */
    public static function consents()
    {
        $all = self::all();
        $index = self::eventIndex($all);
        $out = [];
        foreach ($all as $row) {
            if (($row['type'] ?? '') === 'consent') {
                $out[] = self::withStatus($row, $index, true);
            }
        }
        return array_reverse($out);
    }

    /** ID delle pratiche collegate a un indirizzo email. */
    public static function idsByEmail($email)
    {
        $key = self::emailKey($email);
        $ids = [];
        foreach (self::all() as $row) {
            if (($row['type'] ?? '') === 'consent' && hash_equals((string) ($row['email_key'] ?? ''), $key)) {
                $ids[] = $row['id'];
            }
        }
        return $ids;
    }

    /** Cerca un consenso dall'impronta SHA-256 del suo file PDF. */
    public static function findByPdfHash($sha256)
    {
        $sha256 = strtolower($sha256);
        $all = self::all();
        foreach ($all as $row) {
            if (($row['type'] ?? '') === 'consent' && hash_equals((string) ($row['pdf_sha256'] ?? ''), $sha256)) {
                return self::withStatus($row, self::eventIndex($all), false);
            }
        }
        return null;
    }

    /**
     * Verifica l'intera catena.
     * @return array ['ok' => bool, 'count' => int, 'error' => string|null]
     */
    public static function verify()
    {
        $prev = self::GENESIS;
        $n = 0;
        foreach (self::all() as $row) {
            $n++;
            if (isset($row['_corrupt'])) {
                return ['ok' => false, 'count' => $n, 'error' => "Riga $n non leggibile"];
            }
            if (($row['seq'] ?? null) !== $n) {
                return ['ok' => false, 'count' => $n, 'error' => "Numerazione interrotta alla riga $n (righe cancellate o riordinate)"];
            }
            if (($row['prev'] ?? '') !== $prev) {
                return ['ok' => false, 'count' => $n, 'error' => "Collegamento rotto alla riga $n"];
            }
            if (!hash_equals(self::sign($row), (string) ($row['hash'] ?? ''))) {
                return ['ok' => false, 'count' => $n, 'error' => "Contenuto alterato alla riga $n"];
            }
            $prev = $row['hash'];
        }
        return ['ok' => true, 'count' => $n, 'error' => null];
    }
}
