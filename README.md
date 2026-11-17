# Novazione – Landing page con form contatti e prova dei consensi GDPR

Landing page blu, moderna e responsive in PHP. Quando un visitatore compila il modulo:

1. vengono registrati **nome, cognome, email, cellulare, consenso privacy, consenso marketing, IP e timestamp**;
2. viene generata un'**attestazione PDF** cifrata (AES‑256, modifica disabilitata) e **firmata digitalmente**: qualsiasi modifica invalida la firma;
3. l'impronta **SHA‑256** del PDF e un **HMAC dei dati** vengono scritti in un **registro a catena di hash** (a prova di manomissione);
4. il PDF viene inviato a **73.leonardo.v@gmail.com** (e in copia all'utente), con le impronte nel testo dell'email.

## Funzionalità

| Area | Cosa fa |
|---|---|
| Prova del consenso (art. 7.1 GDPR) | Testo esatto delle caselle + versione, IP, user agent, pagina, timestamp ISO 8601 con fuso orario |
| Integrità | PDF firmato (DocMDP) + cifrato, HMAC dei dati, SHA‑256 del file, registro a catena di hash verificato in automatico |
| Verifica pubblica | `verify.php`: carica un PDF o inserisci il codice pratica → autentico/alterato, stato attuale (valido/revocato) |
| Double opt‑in marketing | Link di conferma nell'email; la conferma richiede un click (gli antivirus che aprono i link non confermano da soli) |
| Revoca (art. 7.3) | `revoca.php`: revoca marketing o totale + richiesta di cancellazione, notifica al titolare e all'interessato |
| Area admin | `admin.php`: elenco, ricerca, export CSV (protetto da formula‑injection), download PDF, cancellazione dati (art. 17) senza rompere la catena |
| Privacy by design | Nessun font/CDN/script di terze parti, un solo cookie tecnico → niente banner cookie. Il registro non contiene dati personali in chiaro |
| Anti‑spam | CSRF, honeypot, tempo minimo di compilazione firmato, rate limit per IP (5/ora) |
| Accessibilità | Etichette, `aria-invalid`, messaggi d'errore annunciati, navigazione da tastiera, funziona anche senza JavaScript |

## Installazione (hosting con cPanel / FTP)

1. **Configura** – copia `includes/config.local.example.php` in `includes/config.local.php` e compila:
   - `company`: ragione sociale, indirizzo, P.IVA, email, telefono (finiscono nel PDF e nell'informativa);
   - `mail_from`: un indirizzo **del tuo dominio** (es. `noreply@studioortopedicopremium.it`);
   - `smtp`: i dati della casella email creata in cPanel (fortemente consigliato: senza SMTP Gmail può mettere le email in spam);
   - `admin_password_hash`: genera con `php -r "echo password_hash('LaTuaPassword', PASSWORD_DEFAULT);"`.
2. **Carica i file** nella cartella del sito (es. `/public_html/sviluppo`):
   - con FileZilla: trascina tutto **tranne** `.git`, `tests`, `deploy`;
   - oppure da terminale: `php deploy/deploy_ftp.php` (chiede la password, non la salva).
3. **Verifica** aprendo `https://tuodominio/sviluppo/`, invia un modulo di prova e controlla che:
   - arrivi l'email con il PDF;
   - `https://tuodominio/sviluppo/storage/` risponda **403 Forbidden** (i dati non devono essere pubblici).
4. Consigliato: attiva HTTPS e decommenta il redirect in `.htaccess`.

Requisiti: PHP 7.4 – 8.4 con estensioni `openssl`, `mbstring`, `zlib` (presenti su tutti gli hosting comuni). Le librerie (TCPDF, PHPMailer) sono già incluse in `vendor/`.

## Dove sono i dati

Tutto in `storage/` (creata al primo invio, protetta da `.htaccess`):

- `ledger/consents.jsonl` – registro a catena di hash (solo impronte e stati);
- `records/<ID>.json` – dati personali della pratica;
- `pdfs/<ID>.pdf` – attestazioni;
- `keys/` – chiave segreta HMAC e certificato di firma (**fai un backup**: senza la chiave non si possono più verificare le impronte);
- `logs/` – log applicativo (senza dati personali).

Per maggiore sicurezza puoi spostare `storage` fuori da `public_html` impostando `storage_dir` in `config.local.php`.

## Test

`tests/run_tests.sh` esegue 107 controlli end‑to‑end (invio, email, PDF, firma, manomissioni, attacchi, rate limit, revoca, admin, concorrenza) contro un server Apache di sviluppo. Vedi l'intestazione dello script.

## Limiti noti

- Il certificato di firma è **autofirmato**: Acrobat mostra la firma come valida ma "identità non verificata". Per una firma riconosciuta legalmente sostituisci `storage/keys/sign.crt`/`sign.key` con un certificato qualificato (es. firma remota Aruba/InfoCert/Namirial).
- Il timestamp è quello del server, non una marca temporale qualificata eIDAS.
- L'informativa in `privacy.php` è un modello: va rivista dal titolare/consulente privacy.
