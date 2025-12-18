<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novazione - Contattaci</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav-container">
            <div class="logo">
                <span class="logo-icon"><i class="fas fa-cube"></i></span>
                <span class="logo-text">Novazione</span>
            </div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#services">Servizi</a></li>
                <li><a href="#contact">Contatti</a></li>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-background">
            <div class="gradient-overlay"></div>
            <div class="animated-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
        </div>
        <div class="hero-content">
            <h1 class="hero-title">
                Trasformiamo le tue
                <span class="highlight">idee</span> in
                <span class="highlight">realtà</span>
            </h1>
            <p class="hero-subtitle">
                Soluzioni innovative per il tuo business. Contattaci per scoprire come possiamo aiutarti a crescere.
            </p>
            <a href="#contact" class="cta-button">
                <span>Contattaci Ora</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="scroll-indicator">
            <div class="mouse">
                <div class="wheel"></div>
            </div>
            <span>Scorri per scoprire di più</span>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">I Nostri Servizi</span>
                <h2 class="section-title">Cosa Possiamo Fare Per Te</h2>
                <p class="section-description">
                    Offriamo una gamma completa di servizi per soddisfare ogni tua esigenza
                </p>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3>Sviluppo Web</h3>
                    <p>Creiamo siti web moderni, responsive e ottimizzati per i motori di ricerca.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>App Mobile</h3>
                    <p>Sviluppiamo applicazioni mobile native e cross-platform per iOS e Android.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-cloud"></i>
                    </div>
                    <h3>Cloud Solutions</h3>
                    <p>Soluzioni cloud scalabili e sicure per la tua infrastruttura aziendale.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Cyber Security</h3>
                    <p>Proteggiamo i tuoi dati e la tua azienda dalle minacce informatiche.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="contact-wrapper">
                <div class="contact-info">
                    <span class="section-tag">Contattaci</span>
                    <h2 class="section-title">Iniziamo a Collaborare</h2>
                    <p class="contact-description">
                        Compila il modulo e ti ricontatteremo entro 24 ore.
                        I tuoi dati saranno trattati nel rispetto della normativa GDPR.
                    </p>
                    <div class="contact-details">
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-text">
                                <span class="label">Email</span>
                                <span class="value">info@novazione.it</span>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-text">
                                <span class="label">Telefono</span>
                                <span class="value">+39 02 1234567</span>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-text">
                                <span class="label">Indirizzo</span>
                                <span class="value">Via Roma 123, Milano</span>
                            </div>
                        </div>
                    </div>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="contact-form-container">
                    <form id="contactForm" action="process.php" method="POST" class="contact-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nome">Nome <span class="required">*</span></label>
                                <input type="text" id="nome" name="nome" required placeholder="Il tuo nome">
                                <span class="input-icon"><i class="fas fa-user"></i></span>
                            </div>
                            <div class="form-group">
                                <label for="cognome">Cognome <span class="required">*</span></label>
                                <input type="text" id="cognome" name="cognome" required placeholder="Il tuo cognome">
                                <span class="input-icon"><i class="fas fa-user"></i></span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email <span class="required">*</span></label>
                                <input type="email" id="email" name="email" required placeholder="La tua email">
                                <span class="input-icon"><i class="fas fa-envelope"></i></span>
                            </div>
                            <div class="form-group">
                                <label for="telefono">Cellulare <span class="required">*</span></label>
                                <input type="tel" id="telefono" name="telefono" required placeholder="+39 333 1234567" pattern="[\+]?[0-9\s]{10,15}">
                                <span class="input-icon"><i class="fas fa-phone"></i></span>
                            </div>
                        </div>
                        <div class="form-group full-width">
                            <label for="messaggio">Messaggio</label>
                            <textarea id="messaggio" name="messaggio" rows="4" placeholder="Scrivi il tuo messaggio..."></textarea>
                            <span class="input-icon textarea-icon"><i class="fas fa-comment"></i></span>
                        </div>

                        <div class="consent-section">
                            <h4><i class="fas fa-shield-alt"></i> Consensi Privacy</h4>

                            <div class="checkbox-group">
                                <input type="checkbox" id="privacy" name="privacy" required>
                                <label for="privacy">
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-text">
                                        Ho letto e accetto l'<a href="#" target="_blank">Informativa sulla Privacy</a> ai sensi del Regolamento UE 2016/679 (GDPR). <span class="required">*</span>
                                    </span>
                                </label>
                            </div>

                            <div class="checkbox-group">
                                <input type="checkbox" id="marketing" name="marketing">
                                <label for="marketing">
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-text">
                                        Acconsento al trattamento dei miei dati per finalità di marketing e comunicazioni commerciali relative ai servizi di Novazione.
                                    </span>
                                </label>
                            </div>
                        </div>

                        <input type="hidden" name="csrf_token" value="<?php echo bin2hex(random_bytes(32)); ?>">

                        <button type="submit" class="submit-button">
                            <span>Invia Richiesta</span>
                            <i class="fas fa-paper-plane"></i>
                        </button>

                        <p class="form-note">
                            <i class="fas fa-lock"></i>
                            I tuoi dati sono al sicuro. Riceverai una copia del consenso via email.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="logo">
                        <span class="logo-icon"><i class="fas fa-cube"></i></span>
                        <span class="logo-text">Novazione</span>
                    </div>
                    <p>Soluzioni innovative per il tuo business digitale.</p>
                </div>
                <div class="footer-links">
                    <h4>Link Utili</h4>
                    <ul>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                        <li><a href="#">Termini e Condizioni</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Novazione. Tutti i diritti riservati.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
