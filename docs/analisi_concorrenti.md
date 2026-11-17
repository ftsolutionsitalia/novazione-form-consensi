# Analisi concorrenti – Form di raccolta e prova del consenso GDPR

*Data: 17/11/2026. Fonti: ricerche web (snippet di pagine ufficiali e recensioni 2025-2026). Nota metodologica: in questa sessione WebFetch era bloccato dalla rete (iubenda.com, complianz.io, cookieyes.com non raggiungibili), quindi i fatti provengono dai risultati di ricerca; i prezzi discordanti tra fonti sono indicati come "non verificato" o con la fonte. Verificare sempre sulle pagine prezzi ufficiali prima di citarli.*

## 1. Tabella dei 20 concorrenti

Legenda dati di prova: IP, TS = timestamp, UA = user agent, TXT = testo/versione informativa, SNAP = snapshot del form, DOI = double opt-in, EXP = export/PDF, HASH = evidenza anti-manomissione, REV = gestione revoca.

### (a) Consent database / CMP

| # | Nome / URL | Dati di prova | Residenza dati | Prezzo di partenza | Punti di forza | Punti deboli |
|---|---|---|---|---|---|---|
| 1 | **iubenda Consent Database** – iubenda.com/it/consent-database | Soggetto (ID, email, nome), preferenze multiple, *legal_notices* con identificativo + **versione**, *proofs* (form mostrato + contenuto compilato), flag DOI, eventi **append-only** (non modificabili), REV via API/JS/plugin WP | UE (azienda italiana; dettaglio datacenter non verificato) | Form consent: ~$5-6/mese ogni 1.000 consensi, sopra un piano base (cifra discordante, non verificata) | Modello dati di riferimento (soggetto/preferenze/informative/prove); log immutabile | Costo a consumo + abbonamento; dipendenza da JS/API esterni |
| 2 | **OneTrust Consent & Preferences** – onetrust.com | *Consent receipt* con ID ricevuta, scopi, punto di raccolta, storico modifiche; integrazione "External Web Form" con mapping campi; API bulk | Multi-regione (dettaglio non verificato) | Su preventivo; fonti terze ~10k $/anno (non verificato) | Standard enterprise, ricevuta ricercabile per identificativo | Costoso e complesso, sproporzionato per PMI |
| 3 | **Usercentrics** – usercentrics.com | Consent ID, numero, TS, opt-in/opt-out, lingua banner, **versione template**, UA/referrer, IP; log scaricabile 12 mesi | Server in Germania e Belgio (GCP), trattamento UE/SEE da DPA | Da ~7 €/mese (Capterra, non verificato) | Versionamento del testo mostrato; hosting UE | Orientato ai cookie, non ai lead form; prezzi a sessioni con auto-upgrade |
| 4 | **Didomi** – didomi.io | "Preference Proofs", API consenso/preferenze; UA, TS, metodo di consenso; retention default 5 anni (ROPA Didomi) | UE (AWS EMEA; dati raw su Snowflake Francoforte) | Su preventivo (non verificato) | Preference center completo, API | Enterprise, nessun prezzo pubblico |
| 5 | **Osano** – osano.com | Registro consensi cookie (contenuto dettagliato non verificato); piano free forse senza registri (claim di concorrente) | USA/UE non verificato | Free; Plus ~199 $/mese (non verificato) | Piano gratuito, interfaccia semplice | Focus USA; registri consensi limitati nei piani bassi |
| 6 | **Termly** – termly.io | Consent log esportabile (secondo recensioni); conservazione sicura nei piani Pro+ | USA (non verificato UE) | Free; Starter ~10-14 $/mese (non verificato) | Economico, genera anche le policy | Forte sui cookie, debole sui form lead |
| 7 | **CookieYes** – cookieyes.com | IP **anonimizzato**, paese, stato consenso, TS; export CSV | Non verificato | Free; da ~10 $/mese/dominio | Minimizzazione IP, export CSV | Solo cookie; segnalazioni di log non sempre popolati |
| 8 | **Complianz** (plugin WP) – complianz.io | Records of Consent con **snapshot PDF** scaricabile per utente (salvato in uploads/complianz/snapshots), utile per richieste dell'interessato | Self-hosted (sul sito WP) | Premium: prezzo non verificato (Agency 25 siti ~399 $) | Self-hosted come noi; PDF per l'interessato | Solo cookie; PDF sul filesystem senza integrità dimostrata |
| 9 | **consentmanager** – consentmanager.net | Registro di ogni decisione, conservato 13-36 mesi secondo piano | UE | Free; da ~19-23 €/mese (fonti discordanti) | Retention esplicita per piano | Solo cookie/CMP |
| 10 | **Axeptio** – axeptio.eu | Archiviazione delle prove di consenso (dettaglio non verificato) | Azienda francese; hosting UE dichiarato da alcune fonti, non verificato | Free ≤200 visitatori; ~29 €/mese (Capterra) | UX del banner molto curata, mercato francofono | Residenza dati contrattuale non chiara |

### (b) Form builder / lead capture

| # | Nome / URL | Dati di prova | Residenza dati | Prezzo di partenza | Punti di forza | Punti deboli |
|---|---|---|---|---|---|---|
| 11 | **Jotform** – jotform.com | Campi consenso, IP/TS di invio (dettagli IP non verificati), export, PDF di invio | Opzione **EU (Francoforte)**, DPA pre-firmato | Free 5 form/100 invii; Bronze ~39 $/mese | Opzione EU self-service, DPA | Prova di consenso non strutturata (no versione informativa) |
| 12 | **Typeform** – typeform.com | Checkbox legali; nessun log di consenso dedicato | USA (AWS) di default; UE solo Enterprise | Basic ~25-29 $/mese (non verificato) | UX conversazionale, alto tasso di completamento | Dati in USA salvo Enterprise; costo alto |
| 13 | **Tally** – tally.so | Campi consenso; IP non raccolto di default (servono hidden field/codice); partial submissions (Pro) | Belgio/UE (Google Cloud Belgio secondo terzi) | Free; Pro ~20-29 €/$ al mese | Europeo, piano free generoso, minimizzazione | Nessun registro consensi né versione informativa |
| 14 | **HubSpot Forms** – hubspot.com | Consenso per **tipo di iscrizione** con TS, base giuridica (consenso vs legittimo interesse), checkbox separate trattamento/comunicazioni, storico sul contatto | Datacenter UE disponibile (condizioni non verificate) | Free (Forms/CRM) | Consenso granulare per finalità legato al CRM, gestione disiscrizioni | Lock-in, script terzi pesanti, dati sensibili in SaaS USA |
| 15 | **WPForms** (plugin WP) – wpforms.com | Campo "GDPR Agreement" obbligatorio; opzioni **disattiva cookie utente** e **disattiva IP/UA** | Self-hosted | Da ~49,50 $/anno (non verificato) | Privacy by default configurabile | Nessuna prova strutturata (testo/versione), nessuna integrità |
| 16 | **Gravity Forms** (plugin WP) – gravityforms.com | Campo Consent con testo descrittivo salvato con l'entry; blocco IP; **retention automatica** (conserva/cestina/elimina dopo N giorni); integrazione export/cancellazione dati WP | Self-hosted | Basic ~59 $/anno | Retention per form, testo del consenso salvato | Nessun hash/log immutabile |
| 17 | **Contact Form 7 + Flamingo** – contactform7.com | Acceptance obbligatoria; Flamingo salva invio + TS, IP, UA; plugin aggiuntivi (es. *Terms & Conditions Consent Log*) salvano **testo completo, versione documento, URL sorgente** | Self-hosted | Gratuito | Gratis, diffusissimo in Italia | Consenso personalizzato non sempre esportabile per richieste interessato; niente DOI nativo |
| 18 | **123FormBuilder** – 123formbuilder.com | Campi GDPR, piattaforma EU separata | AWS USA/UE; residenza UE su piattaforma EU / piani alti | Free; piani a pagamento non verificati | Piattaforma EU dedicata | Prova di consenso generica |
| 19 | **Brevo** (ex Sendinblue) – brevo.com | Profilo contatto con **momento esatto dell'iscrizione + ID del form**, esportabile; **DOI** | UE (datacenter esatti discordanti) | Free; piani a pagamento non verificati | Azienda europea, DOI nativo, ottimo per marketing | Serve per newsletter, non per prova di consenso privacy completa |
| 20 | **MailerLite** – mailerlite.com | IP, località, data/ora, **sorgente del form**, TS conferma DOI; consiglia di conservare screenshot del form | UE dichiarata da terzi (non verificato) | Free; ~10-12 $/mese (fonti discordanti) | DOI con timestamp di conferma | Prova legata solo al consenso marketing |

*Fuori tabella ma rilevanti: **Moduli Google** (gratis, nessuna prova di consenso strutturata, possibili trasferimenti extra-UE), **Cognito Forms** (DPF USA, nessuna residenza UE pubblicata), **Paperform** (USA di default, altre regioni su richiesta/Enterprise).*

## 2. SWOT del nostro prodotto rispetto al mercato

**Punti di forza (S)**
- **Self-hosted e senza terze parti**: nessun dato verso SaaS USA (a differenza di Typeform, Cognito, Paperform di default); controllo totale della residenza dati.
- **Prova "portabile"**: PDF con hash SHA-256 per ogni consenso; pochi form builder producono una prova autonoma (solo Complianz offre uno snapshot PDF, ma solo per i cookie).
- **Consensi separati** privacy (obbligatorio) e marketing (facoltativo), coerenti con art. 7(2) GDPR e con l'approccio granulare di HubSpot/iubenda.
- **Costo zero di licenza** e nessun prezzo a consumo (iubenda ~5-6 $/1.000 consensi; OneTrust/Didomi su preventivo).
- Pensato per il contesto italiano (lingua, Garante, cellulare).

**Punti deboli (W)**
- **L'hash nel PDF da solo non prova l'integrità**: chi può modificare il PDF può ricalcolare l'hash. Manca un ancoraggio esterno (log append-only, catena di hash, marca temporale).
- **Manca la versione dell'informativa** e il testo esatto mostrato (iubenda *legal_notices* + *proofs*, Usercentrics *template version*).
- **Nessun double opt-in** → il consenso marketing è dimostrabile male (Brevo, MailerLite lo hanno nativo).
- **Nessuna revoca self-service** né storico eventi (art. 7(3)); nessun pannello admin/ricerca/export.
- **Email come archivio**: il PDF inviato per email è una copia di dati personali in una casella di posta, senza retention controllata; se il DB locale non esiste, la prova vive solo nell'email.
- Nessuna protezione anti-spam dichiarata, nessuna verifica accessibilità.

**Opportunità (O)**
- Nicchia PMI/professionisti italiani che vogliono **prova "pronta per il Garante" senza abbonamento**: i CMP (Usercentrics, CookieYes, consentmanager, Axeptio) coprono i cookie, non i lead form.
- **Marca temporale qualificata eIDAS** (TSP italiani) o RFC 3161 sull'hash: differenziante che nessun concorrente analizzato offre di serie.
- Integrazione opzionale con iubenda Consent Database (API HTTP) per chi già la usa.
- Accessibilità (European Accessibility Act in vigore dal 28/06/2025; Legge Stanca) come argomento commerciale.
- Riuso come componente per landing di agenzie (multi-sito, white label).

**Minacce (T)**
- Plugin WordPress gratuiti o economici (CF7+Flamingo, WPForms, Gravity Forms) già installati da gran parte dei siti italiani.
- iubenda: marchio italiano molto noto, modello dati più ricco, integrazione con le proprie informative.
- Rischio sicurezza/manutenzione del codice PHP self-hosted (aggiornamenti, patch, backup) a carico del cliente.
- Evoluzioni normative/interpretative (Garante, EDPB) che richiedano campi o flussi aggiuntivi.
- Spam/bot che riempiono il registro di consensi falsi, rendendo inaffidabili le prove.

## 3. Funzionalità da adottare (in ordine di priorità)

Effort: S = poche ore, M = 1-3 giorni, L = > 3 giorni.

| # | Funzionalità / pratica | Perché conta (articolo GDPR o concorrente) | Effort |
|---|---|---|---|
| 1 | **Salvare testo esatto delle checkbox + versione e hash dell'informativa privacy** (es. `privacy_v3_2026-10-01`, SHA-256 del file della policy) | Art. 7(1) "dimostrare il consenso"; EDPB Linee guida 05/2020 §5.1 (conservare cosa è stato mostrato); iubenda *legal_notices* (id + versione), Usercentrics *template version*, plugin *T&C Consent Log* | S |
| 2 | **Registro su database append-only** (tabella eventi: dai/revoca, mai UPDATE/DELETE applicativo), oltre all'email | Art. 5(2) accountability; iubenda "consent events non modificabili"; l'email non è un archivio | M |
| 3 | **Integrità reale: catena di hash** (ogni record include l'hash del precedente) + hash salvato sia nel DB sia nel PDF; opzionale **marca temporale RFC 3161/eIDAS** | Art. 5(1)(f) integrità, art. 32; nessun concorrente lo offre di serie → differenziante | M (catena) / L (TSA) |
| 4 | **Snapshot del form**: HTML/testi visualizzati + valori inviati + URL pagina + UA, nel record e nel PDF | iubenda *proofs* (form mostrato + contenuto); MailerLite consiglia lo screenshot del form; Flamingo salva UA | S |
| 5 | **Double opt-in per il consenso marketing** (email con token firmato, scadenza, TS e IP di conferma) | Art. 7(1); Brevo e MailerLite (DOI non obbligatorio ma "prova aggiuntiva"); utile anche contro email false | M |
| 6 | **Link di revoca / gestione preferenze** in ogni email e pagina dedicata, con registrazione evento di revoca | Art. 7(3) revoca facile quanto il consenso, art. 21; HubSpot (tipi di iscrizione), iubenda (revoke via API) | M |
| 7 | **Checkbox non preselezionate, consenso marketing separato e non vincolante** (validazione lato server) | Art. 7(2) e 7(4), Considerando 32, sentenza CGUE Planet49 (C-673/17); HubSpot checkbox separate | S |
| 8 | **Zero richieste a terzi**: font, CSS, JS e CAPTCHA serviti localmente; nessun Google Fonts/CDN | Art. 44 ss. (trasferimenti), art. 25 privacy by design; sentenza LG München 20/01/2022 su Google Fonts; WPForms "disattiva cookie utente" | S |
| 9 | **Anti-spam privacy-friendly**: honeypot, token CSRF, tempo minimo di compilazione, rate-limit per IP; CAPTCHA self-hosted (es. ALTCHA, proof-of-work) | Art. 32 sicurezza; evita prove false nel registro; Tally/WPForms offrono protezioni analoghe | S-M |
| 10 | **Policy di retention automatica** (cron che elimina/anonimizza dopo N mesi dalla revoca o fine finalità; IP pseudonimizzato dopo X) | Art. 5(1)(c)(e) minimizzazione e limitazione conservazione; Gravity Forms (conserva/cestina/elimina dopo N giorni), consentmanager (13-36 mesi), Didomi (default 5 anni), CookieYes (IP anonimizzato) | M |
| 11 | **Pannello admin protetto**: ricerca per email, dettaglio evento, **export CSV/JSON**, ri-download del PDF | Art. 15 (accesso) e 20 (portabilità); CookieYes export CSV, Usercentrics log scaricabile, Complianz PDF per utente | M |
| 12 | **Gestione diritti interessato**: export ed erasure per email (cancellando i dati del form ma conservando prova minimale della revoca) | Artt. 15-17; Gravity Forms integra gli strumenti export/erase di WordPress; CF7 mostra i limiti di non farlo | M |
| 13 | **Email al titolare minimizzata**: notifica senza dati in chiaro o PDF cifrato con password non inviata nello stesso canale; TLS SMTP obbligatorio | Art. 32 (cifratura), art. 5(1)(f); riduce copie non controllate | S |
| 14 | **Accessibilità WCAG 2.1 AA**: label esplicite, errori annunciati (aria-live), focus visibile, contrasto, testo informativa raggiungibile da tastiera | EAA (Dir. 2019/882, dal 28/06/2025), Legge Stanca; consenso "informato" art. 4(11) implica informativa fruibile | M |
| 15 | **Informativa a strati e link all'informativa completa accanto alla checkbox**, con data di aggiornamento visibile | Art. 12-13 (informazione chiara, accessibile); Tally e HubSpot "notice and consent" sopra il bottone | S |

**Note di implementazione rapide**
- Hash: calcolare SHA-256 su una serializzazione canonica (JSON con chiavi ordinate) del record, non sul PDF; il PDF è una "vista" della prova.
- Pseudonimizzare l'IP nel log a lungo termine (es. troncare l'ultimo ottetto) mantenendo l'IP completo solo per il periodo antifrode dichiarato nell'informativa.
- Registrare base giuridica e finalità per ciascuna checkbox (come HubSpot), utile per il registro dei trattamenti (art. 30).

## 4. Fonti principali

- iubenda Consent Database: https://www.iubenda.com/it/consent-database/ · API: https://www.iubenda.com/en/help/6469-consent-database-http-api · prezzi: https://www.iubenda.com/app/en/pricing · CF7: https://www.iubenda.com/en/help/13083/
- OneTrust: https://developer.onetrust.com/onetrust/reference/consent-receipts · https://www.onetrust.com/content/onetrust/us/en/solutions/consent-and-preferences · https://passiro.com/compare/onetrust/
- Usercentrics: https://usercentrics.com/wp-content/uploads/2022/01/UC-AVV-EN.pdf · https://usercentrics.com/privacy-policy/ · https://capterra.com/p/179357/Usercentrics/
- Didomi: https://www.didomi.io/offers · https://www.didomi.io/sub-processors · https://business.didomi.io/hubfs/2025-02-14%20-%20ROPA%20RGPD%20-%20SDK%20(EN).pdf
- Osano: https://toolradar.com/tools/osano/pricing · https://www.consentstack.io/blog/osano-pricing
- Termly: https://www.capterra.com/p/165919/Termly/pricing/ · https://consently.net/blog/termly-review
- CookieYes: https://www.cookieyes.com/pricing.md · https://consently.net/blog/cookieyes-review
- Complianz: https://complianz.io/records-of-consent/ · https://complianz.io/pricing/ecommerce/
- consentmanager: https://www.consentmanager.net/en/pricing/ · https://www.consentmanager.net/en/features/
- Axeptio: https://www.capterra.com/p/173892/Axeptio/pricing/ · https://europeanpurpose.com/tool/axeptio
- Jotform: https://www.jotform.com/de/help/wie-sie-ihre-daten-auf-eu-servern-speichern-konnen-datenresidenz-in-der-eu/ · https://www.jotform.com/de/gdpr-compliance/ · https://toolradar.com/tools/jotform/pricing
- Typeform: https://capterra.com/p/137289/Typeform/pricing/ · https://donneespersonnelles.fr/typeform-rgpd
- Tally: https://tally.so/help/gdpr · https://developers.tally.so/widgets/examples/collect-ip-address.md · https://tally.so/pricing
- HubSpot: https://www.hubspot.com/data-privacy/gdpr/hubspot-product-playbook · https://www.babelquest.co.uk/blog/gdpr-settings-in-hubspot-whats-the-latest-and-what-do-they-mean
- WPForms: https://wpforms.com/docs/how-to-create-gdpr-compliant-forms/
- Gravity Forms: https://docs.gravityforms.com/personal-data-settings/ · https://docs.gravityforms.com/wordpress-gravity-forms-and-gdpr-compliance/
- Contact Form 7 / Flamingo: https://contactform7.com/?p=24814 · https://strategiq.co/hub/gdpr-compliance-contact-form-7-flamingo/ · Terms & Conditions Consent Log: https://wordpress.org/plugins/?p=314214
- 123FormBuilder: https://eu.123contactform.com/gdpr-compliance/ · https://123contactform.com/enterprise/pricing
- Brevo: https://help.brevo.com/hc/articles/360000454204 · https://help.brevo.com/hc/en-us/articles/360001258744-How-does-Brevo-comply-with-the-GDPR
- MailerLite: https://www.mailerlite.com/help/how-to-use-double-opt-in-when-collecting-subscribers · https://www.mailerlite.com/blog/new-mailerlite-gdpr-features-part-2-of-3-opt-in-forms
- Cognito Forms: https://www.cognitoforms.com/product/data-protection-privacy · Paperform: https://paperform.co/help/articles/where-is-data-hosted/
- EDPB Linee guida 05/2020 sul consenso: https://edpb.europa.eu/our-work-tools/our-documents/guidelines/guidelines-052020-consent-under-regulation-2016679_en
