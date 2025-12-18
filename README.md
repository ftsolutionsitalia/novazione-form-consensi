# Novazione Form Consensi

Landing page moderna ed elegante con form contatti e gestione dei consensi privacy GDPR-compliant.

## Caratteristiche

- **Design moderno** con tema blu elegante
- **Form contatti completo** con validazione client e server-side
- **Gestione consensi GDPR**:
  - Checkbox accettazione privacy (obbligatorio)
  - Checkbox consenso marketing (opzionale)
- **Registrazione automatica**:
  - IP dell'utente
  - Timestamp della sottoscrizione
- **Generazione PDF** con:
  - Tutti i dati del consenso
  - Hash SHA-256 per garantire l'integrità del documento
  - Protezione da modifiche
- **Invio email automatico**:
  - Notifica all'amministratore con tutti i dettagli
  - Conferma all'utente con copia del PDF

## Requisiti

- PHP 7.4 o superiore
- Estensione PHP `mbstring`
- Estensione PHP `mail` configurata
- Server web Apache o Nginx

## Installazione

1. Clona il repository:
```bash
git clone https://github.com/ftsolutionsitalia/novazione-form-consensi.git
cd novazione-form-consensi
```

2. Installa le dipendenze con Composer (raccomandato per produzione):
```bash
composer install
```

3. Configura il file `includes/config.php`:
   - Modifica `EMAIL_DESTINATARIO` con la tua email
   - Aggiorna i dati aziendali (nome, indirizzo, P.IVA)
   - Cambia `SECRET_KEY` con una chiave sicura

4. Assicurati che le seguenti cartelle siano scrivibili:
```bash
chmod 755 pdfs/ data/ logs/
```

5. Configura il tuo server web per puntare alla directory del progetto.

## Struttura del progetto

```
novazione-form-consensi/
├── index.php              # Landing page principale
├── process.php            # Elaborazione form
├── success.php            # Pagina di successo
├── error.php              # Pagina di errore
├── assets/
│   ├── css/
│   │   └── style.css      # Stili CSS
│   └── js/
│       └── main.js        # JavaScript
├── includes/
│   ├── config.php         # Configurazione
│   ├── PDFGenerator.php   # Generatore PDF
│   └── EmailSender.php    # Invio email
├── vendor/                # Dipendenze
├── pdfs/                  # PDF generati
├── data/                  # Backup JSON consensi
└── logs/                  # Log applicazione
```

## Configurazione

### Email destinatario
Modifica in `includes/config.php`:
```php
define('EMAIL_DESTINATARIO', 'tua@email.com');
```

### Dati aziendali
```php
define('AZIENDA_NOME', 'La Tua Azienda S.r.l.');
define('AZIENDA_INDIRIZZO', 'Via Roma 123, Milano');
define('AZIENDA_PIVA', 'IT12345678901');
```

## Sicurezza

- I PDF sono protetti da modifiche con password proprietario
- Hash SHA-256 garantisce l'integrità dei documenti
- Sanitizzazione di tutti gli input utente
- Protezione CSRF
- File sensibili protetti da .htaccess

## Licenza

Proprietario - Tutti i diritti riservati.
