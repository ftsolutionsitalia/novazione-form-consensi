<?php
/**
 * Verifica pubblica di un'attestazione: per ID (stato del consenso)
 * o caricando il PDF (confronto dell'impronta SHA-256 col registro).
 * Non mostra dati personali: solo stato, data e impronte.
 */
require __DIR__ . '/includes/bootstrap.php';

$result = null;   // ['kind' => 'match'|'nomatch'|'notfound'|'error', ...]
$record = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::checkCsrf($_POST['csrf_token'] ?? null)) {
        $result = ['kind' => 'error', 'msg' => 'Sessione scaduta: ricarica la pagina.'];
    } elseif (!Security::rateLimit('verify', Security::clientIp(), 30, 3600)) {
        $result = ['kind' => 'error', 'msg' => 'Troppe verifiche. Riprova più tardi.'];
    } elseif (empty($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['pdf']['tmp_name'])) {
        $result = ['kind' => 'error', 'msg' => 'Seleziona un file PDF (max 10 MB).'];
    } elseif ($_FILES['pdf']['size'] > 10 * 1024 * 1024) {
        $result = ['kind' => 'error', 'msg' => 'Il file supera i 10 MB.'];
    } else {
        $sha = hash_file('sha256', $_FILES['pdf']['tmp_name']);
        $record = ConsentLedger::findByPdfHash($sha);
        $result = $record ? ['kind' => 'match', 'sha' => $sha] : ['kind' => 'nomatch', 'sha' => $sha];
    }
} elseif (isset($_GET['id'])) {
    $id = (string) $_GET['id'];
    $record = preg_match('/^CNS-\d{8}-[A-Z0-9]{8}$/', $id) ? ConsentLedger::findConsent($id) : null;
    $result = $record ? ['kind' => 'found'] : ['kind' => 'notfound'];
}

$status = $record ? $record['status'] : null;

function fmt_dt($iso)
{
    return (new DateTime($iso))->format('d/m/Y H:i:s T');
}

page_header('Verifica attestazione – ' . cfg('mail_from_name'));
?>
<main id="main" class="page">
    <div class="container">
        <div class="card">
            <h1>Verifica attestazione di consenso</h1>
            <p>Controlla che un'attestazione PDF sia autentica e non sia stata modificata, oppure lo stato attuale di un consenso.</p>

            <?php if ($result): ?>
                <?php if ($result['kind'] === 'match'): ?>
                    <div class="notice ok" role="status"><strong>&#10004; Documento autentico e integro.</strong> L'impronta del file corrisponde a quella registrata per la pratica <?= e($record['id']) ?>.</div>
                <?php elseif ($result['kind'] === 'nomatch'): ?>
                    <div class="notice ko" role="alert"><strong>&#10008; Documento non riconosciuto.</strong> L'impronta del file non corrisponde ad alcuna attestazione registrata: il file è stato modificato oppure non è stato emesso da questo sito.</div>
                <?php elseif ($result['kind'] === 'found'): ?>
                    <div class="notice ok" role="status">Pratica trovata nel registro dei consensi.</div>
                <?php elseif ($result['kind'] === 'notfound'): ?>
                    <div class="notice ko" role="alert">Nessuna pratica con questo codice.</div>
                <?php else: ?>
                    <div class="notice ko" role="alert"><?= e($result['msg']) ?></div>
                <?php endif; ?>

                <?php if ($record): ?>
                    <table class="kv">
                        <tr><td>ID pratica</td><td><b><?= e($record['id']) ?></b></td></tr>
                        <tr><td>Registrata il</td><td><?= e(fmt_dt($record['timestamp'])) ?></td></tr>
                        <tr><td>Consenso privacy</td><td><?= $record['privacy'] ? 'Prestato' : 'Non prestato' ?></td></tr>
                        <tr><td>Consenso marketing</td><td>
                            <?php if (!$record['marketing']): ?>Non prestato
                            <?php elseif ($status['confirmed']): ?>Prestato e confermato<?= is_string($status['confirmed']) ? ' il ' . e(fmt_dt($status['confirmed'])) : '' ?>
                            <?php else: ?>Prestato, in attesa di conferma email<?php endif; ?>
                        </td></tr>
                        <tr><td>Stato attuale</td><td><?php if ($status['withdrawn']): ?><span class="pill ko">Revocato il <?= e(fmt_dt($status['withdrawn']['at'])) ?> (<?= $status['withdrawn']['scope'] === 'all' ? 'tutti i consensi' : 'solo marketing' ?>)</span><?php else: ?><span class="pill ok">Valido</span><?php endif; ?></td></tr>
                        <tr><td>Versione testi</td><td><?= e($record['consent_version']) ?></td></tr>
                        <tr><td>SHA-256 del PDF originale</td><td class="mono"><?= e($record['pdf_sha256']) ?></td></tr>
                        <tr><td>Firma digitale</td><td><?= !empty($record['pdf_signed']) ? 'Sì (certificazione, nessuna modifica consentita)' : 'No' ?></td></tr>
                    </table>
                <?php elseif (!empty($result['sha'])): ?>
                    <p class="mono">SHA-256 del file caricato: <?= e($result['sha']) ?></p>
                <?php endif; ?>
            <?php endif; ?>

            <h2>Verifica un file PDF</h2>
            <form method="post" enctype="multipart/form-data" class="simple-form">
                <label for="pdf">Attestazione PDF</label>
                <input type="file" id="pdf" name="pdf" accept="application/pdf,.pdf" required>
                <input type="hidden" name="csrf_token" value="<?= e(Security::csrfToken()) ?>">
                <div><button class="btn btn-primary" type="submit"><?= icon('search') ?> Verifica file</button></div>
            </form>
            <p style="font-size:.85rem;color:var(--gray-500);margin-top:12px">Il file viene usato solo per calcolarne l'impronta e non viene conservato.</p>

            <h2>Verifica per codice pratica</h2>
            <form method="get" class="simple-form">
                <label for="id">Codice (es. CNS-20261117-ABCD2345)</label>
                <input type="text" id="id" name="id" pattern="CNS-\d{8}-[A-Z0-9]{8}" required value="<?= e($_GET['id'] ?? '') ?>">
                <div><button class="btn btn-outline" type="submit">Cerca</button></div>
            </form>
        </div>
    </div>
</main>
<?php page_footer(); ?>
