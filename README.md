# Oro alla Tocca

Web app per compro oro, orafi e banchi metalli preziosi: digitalizza la tradizionale **prova alla tocca** (pietra di paragone + reagenti acidi) e la collega direttamente al calcolo del valore di acquisto, alla registrazione dell'operazione e a un registro esportabile.

- 📄 [`PRD.md`](./PRD.md) — Product Requirements Document
- 🎨 [`logo.md`](./logo.md) — concetto, sorgente SVG e linee guida del logo
- ✅ [`todo.md`](./todo.md) — backlog e roadmap
- 💻 [`app/`](./app) — codice sorgente (React + TypeScript + Vite + Tailwind CSS)

## Avvio rapido

```bash
cd app
npm install
npm run dev
```

L'app è completamente offline-first: nessun backend, dati salvati in `localStorage` del browser.

## Funzionalità principali

1. **Dashboard** — quotazione oro corrente e tabella prezzi per carato.
2. **Prova alla Tocca** — modulo guidato in due passaggi: confronto visivo sulla pietra di paragone e conferma con reagente acido.
3. **Valutazione** — calcolo automatico del valore (pieno e di acquisto) in base a peso, titolo e quotazione.
4. **Registro** — storico delle operazioni, esportabile in CSV.
5. **Impostazioni** — quotazione oro, percentuale di sconto d'acquisto, dati attività.

> ⚠️ Lo strumento è un supporto operativo e didattico: non sostituisce un'analisi di laboratorio abilitata (XRF, saggio al fuoco).
