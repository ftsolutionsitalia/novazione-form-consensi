# Todo — Oro alla Tocca

## MVP (v1) — completato

- [x] Setup progetto Vite + React + TypeScript + Tailwind CSS v4 (`app/`)
- [x] Dati di riferimento: titoli standard (24→8 K) con millesimi e colore streak (`src/data/goldReference.ts`)
- [x] Dati reagenti acidi (8K → acqua regia) e mappa reazioni possibili
- [x] Libreria di calcolo valore oro (`src/lib/valuation.ts`) + formattazione EUR
- [x] Persistenza locale (`localStorage`) per impostazioni, registro operazioni, prove alla tocca (`src/lib/storage.ts`)
- [x] Logo SVG (icona pietra di paragone + goccia di reagente) + componente `Logo.tsx` + favicon
- [x] Header con navigazione a tab (Dashboard / Prova alla Tocca / Valutazione / Registro / Impostazioni)
- [x] Dashboard: quotazione corrente, statistiche rapide, tabella prezzi per carato
- [x] Modulo Prova alla Tocca: step 1 confronto visivo, step 2 conferma con acido, verdetto, note, salvataggio
- [x] Passaggio automatico del titolo confermato dalla Prova alla Tocca al modulo Valutazione
- [x] Modulo Valutazione: form cliente/oggetto/peso/titolo, calcolo live, registrazione operazione
- [x] Modulo Registro: elenco operazioni, eliminazione voce, esportazione CSV
- [x] Modulo Impostazioni: nome/indirizzo attività, quotazione oro 24K, percentuale sconto acquisto
- [x] Disclaimer sui limiti della prova alla tocca (non sostituisce analisi di laboratorio)
- [x] Verifica build (`tsc --noEmit`, `npm run build`) e test end-to-end manuale del flusso completo (prova → valutazione → registro) via browser

## Miglioramenti a breve termine — completati

- [x] Test unitari (Vitest) per `computeGoldValue`, `round2`, `formatEUR`, `nearestKarat` e il parsing dei backup (`src/lib/*.test.ts`, `src/data/goldReference.test.ts`) — eseguibili con `npm run test`
- [x] Ricevuta stampabile per operazione (`src/components/Receipt.tsx`), apribile dal Registro, con vista di stampa dedicata (`@media print`)
- [x] Collegamento del `touchTestId` salvato alla voce di registro, con dettaglio espandibile della prova associata (reagente, reazione, note)
- [x] Validazione input più robusta: peso non numerico/negativo in Valutazione, quotazione ≤ 0 e sconto fuori 0–100 in Impostazioni, con messaggi di errore inline e salvataggio bloccato finché non validi
- [x] Backup/import completo dei dati (export/import JSON di impostazioni + registro + prove) in Impostazioni, con validazione del file e conferma prima di sovrascrivere

## Da fare — miglioramenti a breve termine

- [ ] Modalità chiaro/scuro (attualmente solo tema scuro)
- [ ] Test automatizzati end-to-end (oggi la copertura e2e è solo manuale via browser)

## Roadmap — funzionalità future

- [ ] Integrazione quotazione oro in tempo reale via API di mercato (con fallback su inserimento manuale)
- [ ] Registro conforme agli obblighi normativi italiani per compro oro (dati identificativi cliente, comunicazioni antiriciclaggio)
- [ ] Multi-utente / multi-postazione con sincronizzazione su backend condiviso (oggi i dati sono locali al browser)
- [ ] App installabile come PWA (uso offline su tablet da banco vendita)
- [ ] Filtri e ricerca nel Registro (per cliente, intervallo di date, titolo)
- [ ] Statistiche avanzate in Dashboard (andamento acquisti nel tempo, grafico quotazione storica)
- [ ] Riconoscimento assistito da foto del colore dello striscio (solo come suggerimento, mai come sostituto della prova fisica)

## Note tecniche

- Nessun backend: tutti i dati vivono in `localStorage` del browser che esegue l'app. Cancellare i dati del sito equivale a perdere registro e prove salvate — esporta un backup JSON periodicamente da Impostazioni.
- Stack: React 19 + TypeScript + Vite + Tailwind CSS v4 + Vitest. Nessuna dipendenza da servizi esterni in questa versione.
