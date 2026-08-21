# Oro alla Tocca — codice sorgente

App React + TypeScript + Vite + Tailwind CSS v4. Vedi il [README del repository](../README.md) e il [PRD](../PRD.md) per il contesto del prodotto.

## Script disponibili

```bash
npm install       # installa le dipendenze
npm run dev       # avvia il server di sviluppo
npm run build     # typecheck + build di produzione in dist/
npm run preview   # serve la build di produzione in locale
```

## Struttura

```
src/
  components/     Componenti UI (Header, Dashboard, TouchTest, Valuation, Ledger, SettingsPanel, Logo)
  data/           Dati di riferimento: titoli in carati, reagenti acidi, reazioni
  lib/            Logica di calcolo (valuation.ts) e persistenza locale (storage.ts)
  types.ts        Tipi condivisi (Settings, LedgerEntry, TouchTestRecord)
```

Tutti i dati (impostazioni, registro operazioni, prove salvate) sono persistiti in `localStorage`: non c'è alcun backend.
