<?php
require __DIR__ . '/includes/bootstrap.php';
$c = cfg('company');
page_header('Informativa privacy – ' . cfg('mail_from_name'));
?>
<main id="main" class="page">
    <div class="container">
        <article class="card">
            <h1>Informativa sul trattamento dei dati personali</h1>
            <p>ai sensi degli artt. 13 e 14 del Regolamento (UE) 2016/679 («GDPR») — versione <?= e(cfg('consents.version')) ?></p>

            <h2>1. Titolare del trattamento</h2>
            <p><?= e($c['name']) ?>, <?= e($c['address']) ?>, P.IVA <?= e($c['vat']) ?>.
            Email: <a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a><?= $c['pec'] ? ' — PEC: ' . e($c['pec']) : '' ?> — Tel: <?= e($c['phone']) ?>.
            <?php if ($c['dpo_email']): ?><br>Responsabile della protezione dei dati (DPO): <a href="mailto:<?= e($c['dpo_email']) ?>"><?= e($c['dpo_email']) ?></a>.<?php endif; ?></p>

            <h2>2. Dati trattati</h2>
            <p>Nome, cognome, email, numero di cellulare, contenuto del messaggio, scelte sui consensi; inoltre, per documentare il consenso: data e ora, indirizzo IP, tipo di browser (user agent) e pagina del modulo.</p>

            <h2>3. Finalità e base giuridica</h2>
            <ul>
                <li><strong>Rispondere alla tua richiesta</strong> e ricontattarti — misure precontrattuali e consenso (art. 6.1.b e 6.1.a). Conferimento necessario: senza questi dati non possiamo risponderti.</li>
                <li><strong>Comunicazioni commerciali</strong> (email, telefono, SMS, WhatsApp) — solo con il tuo consenso facoltativo e confermato via email (art. 6.1.a e art. 130 D.Lgs. 196/2003). Il rifiuto non ha alcuna conseguenza sulla tua richiesta.</li>
                <li><strong>Prova del consenso</strong> e difesa dei diritti — obbligo di dimostrare il consenso (art. 7.1) e legittimo interesse (art. 6.1.f).</li>
            </ul>

            <h2>4. Modalità e sicurezza</h2>
            <p>I dati sono trattati con strumenti elettronici sul server del Titolare. Le prove di consenso sono conservate in un registro a prova di manomissione e in attestazioni PDF cifrate e firmate digitalmente. Il sito non usa servizi di terze parti (font, statistiche, pubblicità) e non trasferisce dati fuori dall'UE.</p>

            <h2>5. Destinatari</h2>
            <p>Personale autorizzato del Titolare e fornitori che agiscono come responsabili del trattamento (hosting, posta elettronica). I dati non sono diffusi né venduti.</p>

            <h2>6. Conservazione</h2>
            <p>Dati della richiesta: per il tempo necessario a gestirla e comunque non oltre 24 mesi dall'ultimo contatto. Dati per marketing: fino alla revoca del consenso, e comunque per non più di 24 mesi senza interazioni. Prove del consenso: per 10 anni dalla revoca o dalla scadenza, a tutela dei diritti del Titolare.</p>

            <h2>7. I tuoi diritti</h2>
            <p>Puoi chiedere accesso, rettifica, cancellazione, limitazione, portabilità, opporti al trattamento (artt. 15–22) e <a href="revoca.php">revocare il consenso</a> in ogni momento, senza pregiudicare la liceità del trattamento precedente. Puoi proporre reclamo al Garante per la protezione dei dati personali (<a href="https://www.garanteprivacy.it" rel="noopener">www.garanteprivacy.it</a>).</p>

            <h2 id="cookie">8. Cookie</h2>
            <p>Questo sito usa un solo cookie tecnico di sessione (<code>nvz_sid</code>), necessario alla sicurezza del modulo (protezione CSRF), che si cancella alla chiusura del browser. Non usa cookie di profilazione né di terze parti, quindi non è richiesto un banner di consenso (art. 122 D.Lgs. 196/2003 e Linee guida Garante 10/06/2021).</p>

            <div class="notice info" style="margin-top:24px">Testo modello: va verificato e adattato dal Titolare (o dal suo consulente privacy) prima della pubblicazione.</div>
        </article>
    </div>
</main>
<?php page_footer(); ?>
