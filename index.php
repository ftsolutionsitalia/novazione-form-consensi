<?php
require __DIR__ . '/includes/bootstrap.php';

$errors = flash('errors') ?: [];
$old = flash('old') ?: [];
$c = cfg('company');

/** Attributi di accessibilità per un campo con eventuale errore. */
function field_attrs($name, array $errors)
{
    return isset($errors[$name])
        ? ' aria-invalid="true" aria-describedby="err-' . $name . '"'
        : ' aria-describedby="err-' . $name . '"';
}
function field_error($name, array $errors)
{
    return '<span class="error-message" id="err-' . $name . '" role="alert">' . e($errors[$name] ?? '') . '</span>';
}

page_header(cfg('mail_from_name') . ' – Trasformiamo le tue idee in realtà', 'Soluzioni digitali su misura. Compila il modulo: ti ricontattiamo entro 24 ore.');
?>
<main id="main">
    <!-- Hero -->
    <section class="hero" aria-labelledby="hero-title">
        <div class="hero-background" aria-hidden="true">
            <div class="gradient-overlay"></div>
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
        <div class="hero-content">
            <p class="hero-eyebrow"><?= icon('shieldcheck') ?> Dati protetti · Conforme GDPR</p>
            <h1 class="hero-title" id="hero-title">
                Trasformiamo le tue <span class="highlight">idee</span> in <span class="highlight">realtà</span>
            </h1>
            <p class="hero-subtitle">
                Soluzioni innovative per il tuo business. Raccontaci il tuo progetto: ti ricontattiamo entro 24 ore lavorative.
            </p>
            <div class="hero-actions">
                <a href="#contact" class="cta-button">Richiedi una consulenza <?= icon('arrow') ?></a>
                <a href="#services" class="cta-ghost">Scopri i servizi</a>
            </div>
            <ul class="trust-row">
                <li><?= icon('clock') ?> Risposta entro 24h</li>
                <li><?= icon('lock') ?> Nessun dato a terzi</li>
                <li><?= icon('file') ?> Ricevi copia firmata del consenso</li>
            </ul>
        </div>
    </section>

    <!-- Servizi -->
    <section id="services" class="services" aria-labelledby="services-title">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">I nostri servizi</span>
                <h2 class="section-title" id="services-title">Cosa possiamo fare per te</h2>
                <p class="section-description">Una gamma completa di servizi per accompagnarti dall'idea al risultato.</p>
            </div>
            <div class="services-grid">
                <article class="service-card">
                    <div class="service-icon"><?= icon('code') ?></div>
                    <h3>Sviluppo Web</h3>
                    <p>Siti web moderni, veloci, accessibili e ottimizzati per i motori di ricerca.</p>
                </article>
                <article class="service-card">
                    <div class="service-icon"><?= icon('mobile') ?></div>
                    <h3>App Mobile</h3>
                    <p>Applicazioni native e cross-platform per iOS e Android.</p>
                </article>
                <article class="service-card">
                    <div class="service-icon"><?= icon('cloud') ?></div>
                    <h3>Cloud Solutions</h3>
                    <p>Infrastrutture cloud scalabili, sicure e con dati ospitati in UE.</p>
                </article>
                <article class="service-card">
                    <div class="service-icon"><?= icon('shield') ?></div>
                    <h3>Cyber Security</h3>
                    <p>Protezione di dati e sistemi aziendali dalle minacce informatiche.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- Contatti -->
    <section id="contact" class="contact" aria-labelledby="contact-title">
        <div class="container">
            <div class="contact-wrapper">
                <div class="contact-info">
                    <span class="section-tag">Contattaci</span>
                    <h2 class="section-title" id="contact-title">Iniziamo a collaborare</h2>
                    <p class="contact-description">
                        Compila il modulo e ti ricontatteremo entro 24 ore lavorative.
                        Riceverai via email un'attestazione PDF firmata dei consensi che hai espresso.
                    </p>
                    <ul class="contact-details">
                        <li class="contact-item">
                            <span class="contact-icon"><?= icon('mail') ?></span>
                            <span class="contact-text"><span class="label">Email</span>
                                <a class="value" href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></span>
                        </li>
                        <li class="contact-item">
                            <span class="contact-icon"><?= icon('phone') ?></span>
                            <span class="contact-text"><span class="label">Telefono</span>
                                <a class="value" href="tel:<?= e(preg_replace('/[^+\d]/', '', $c['phone'])) ?>"><?= e($c['phone']) ?></a></span>
                        </li>
                        <li class="contact-item">
                            <span class="contact-icon"><?= icon('pin') ?></span>
                            <span class="contact-text"><span class="label">Indirizzo</span>
                                <span class="value"><?= e($c['address']) ?></span></span>
                        </li>
                    </ul>
                    <div class="guarantee">
                        <?= icon('shieldcheck') ?>
                        <p><strong>Il tuo consenso è certificato.</strong> Data, ora, IP e testo esatto dei consensi vengono registrati in un archivio a prova di manomissione. Puoi <a href="revoca.php">revocarli</a> quando vuoi.</p>
                    </div>
                </div>

                <div class="contact-form-container">
                    <div class="form-alert" id="form-alert" role="alert" aria-live="assertive" <?= $errors ? '' : 'hidden' ?>>
                        <?= icon('alert') ?> <span><?= $errors ? 'Controlla i campi evidenziati.' : '' ?></span>
                    </div>

                    <?php if ($GLOBALS['STORAGE_ERROR']): ?>
                    <div class="notice ko" role="alert">
                        Il modulo è temporaneamente non disponibile. Contattaci al
                        <a href="tel:<?= e(preg_replace('/[^+\d]/', '', $c['phone'])) ?>"><?= e($c['phone']) ?></a>
                        o scrivi a <a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a>.
                    </div>
                    <?php else: ?>
                    <form id="contactForm" action="process.php" method="POST" class="contact-form" novalidate>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nome">Nome <span class="required" aria-hidden="true">*</span></label>
                                <div class="input-wrap">
                                    <span class="input-icon"><?= icon('user') ?></span>
                                    <input type="text" id="nome" name="nome" required minlength="2" maxlength="60" autocomplete="given-name" placeholder="Mario" value="<?= e($old['nome'] ?? '') ?>"<?= field_attrs('nome', $errors) ?>>
                                </div>
                                <?= field_error('nome', $errors) ?>
                            </div>
                            <div class="form-group">
                                <label for="cognome">Cognome <span class="required" aria-hidden="true">*</span></label>
                                <div class="input-wrap">
                                    <span class="input-icon"><?= icon('user') ?></span>
                                    <input type="text" id="cognome" name="cognome" required minlength="2" maxlength="60" autocomplete="family-name" placeholder="Rossi" value="<?= e($old['cognome'] ?? '') ?>"<?= field_attrs('cognome', $errors) ?>>
                                </div>
                                <?= field_error('cognome', $errors) ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email <span class="required" aria-hidden="true">*</span></label>
                                <div class="input-wrap">
                                    <span class="input-icon"><?= icon('mail') ?></span>
                                    <input type="email" id="email" name="email" required maxlength="254" autocomplete="email" inputmode="email" placeholder="mario.rossi@email.it" value="<?= e($old['email'] ?? '') ?>"<?= field_attrs('email', $errors) ?>>
                                </div>
                                <?= field_error('email', $errors) ?>
                            </div>
                            <div class="form-group">
                                <label for="telefono">Cellulare <span class="required" aria-hidden="true">*</span></label>
                                <div class="input-wrap">
                                    <span class="input-icon"><?= icon('phone') ?></span>
                                    <input type="tel" id="telefono" name="telefono" required maxlength="30" autocomplete="tel" inputmode="tel" placeholder="333 123 4567" value="<?= e($old['telefono'] ?? '') ?>"<?= field_attrs('telefono', $errors) ?>>
                                </div>
                                <?= field_error('telefono', $errors) ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="messaggio">Messaggio <span class="optional">(facoltativo)</span></label>
                            <div class="input-wrap">
                                <span class="input-icon textarea-icon"><?= icon('chat') ?></span>
                                <textarea id="messaggio" name="messaggio" rows="4" maxlength="2000" placeholder="Raccontaci brevemente di cosa hai bisogno..."><?= e($old['messaggio'] ?? '') ?></textarea>
                            </div>
                            <span class="char-count" id="messaggio-count" aria-live="polite"></span>
                        </div>

                        <!-- Trappola anti-bot: invisibile alle persone -->
                        <div class="hp-field" aria-hidden="true">
                            <label for="website">Lascia vuoto questo campo</label>
                            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <fieldset class="consent-section">
                            <legend><?= icon('shieldcheck') ?> Consensi privacy</legend>
                            <p class="consent-intro">
                                Titolare: <?= e($c['name']) ?> — finalità: rispondere alla tua richiesta e, solo se acconsenti, inviarti comunicazioni commerciali.
                                Dettagli nell'<a href="<?= e(cfg('consents.policy_url')) ?>" target="_blank" rel="noopener">informativa completa</a>.
                            </p>

                            <div class="checkbox-group">
                                <input type="checkbox" id="privacy" name="privacy" value="1" required<?= field_attrs('privacy', $errors) ?>>
                                <label for="privacy">
                                    <span class="checkbox-custom" aria-hidden="true"><?= icon('check') ?></span>
                                    <span class="checkbox-text"><?= e(cfg('consents.privacy')) ?> <span class="required">*</span></span>
                                </label>
                            </div>
                            <?= field_error('privacy', $errors) ?>

                            <div class="checkbox-group">
                                <input type="checkbox" id="marketing" name="marketing" value="1"<?= !empty($old['marketing']) ? ' checked' : '' ?>>
                                <label for="marketing">
                                    <span class="checkbox-custom" aria-hidden="true"><?= icon('check') ?></span>
                                    <span class="checkbox-text"><?= e(cfg('consents.marketing')) ?></span>
                                </label>
                            </div>
                            <p class="consent-version">Versione testi: <?= e(cfg('consents.version')) ?></p>
                        </fieldset>

                        <input type="hidden" name="csrf_token" value="<?= e(Security::csrfToken()) ?>">
                        <input type="hidden" name="form_ts" value="<?= e(Security::formIssuedToken()) ?>">

                        <button type="submit" class="submit-button">
                            <span class="btn-label">Invia richiesta</span> <?= icon('send') ?>
                            <span class="spinner" aria-hidden="true"></span>
                        </button>

                        <p class="form-note"><?= icon('lock') ?> Connessione protetta. Riceverai una copia firmata del consenso via email.</p>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>
<?php page_footer(); ?>
