# PRD — Oro alla Tocca

## 1. Sommario

**Oro alla Tocca** è una web app pensata per compro oro, banchi metalli preziosi e orafi, che digitalizza il tradizionale procedimento di **saggio alla pietra di paragone** (touchstone/basanite + aghi di riferimento + reagenti acidi) e lo collega direttamente al calcolo del valore di acquisto dell'oggetto in oro, alla registrazione dell'operazione e alla tenuta di un registro consultabile ed esportabile.

L'app non sostituisce un'analisi di laboratorio (XRF, saggio al fuoco): è uno strumento di **supporto operativo e didattico** che struttura una procedura oggi spesso fatta a mente o su carta, riducendo errori di calcolo e migliorando la tracciabilità delle operazioni.

## 2. Problema

Chi acquista oro usato (compro oro, banchi dei pegni, orafi) normalmente:

1. Esegue la prova alla tocca a occhio, confrontando lo striscio del pezzo con aghi di riferimento e osservando la reazione a diversi acidi.
2. Calcola a mente o con una calcolatrice il valore in base a peso, titolo stimato e quotazione del giorno.
3. Applica uno sconto/margine di acquisto non sempre documentato in modo coerente.
4. Annota (o non annota) l'operazione su un registro cartaceo, con rischio di errori, perdita di dati e difficoltà di rendicontazione.

Non esiste uno strumento leggero, offline-first, che guidi passo passo la prova e colleghi automaticamente stima del titolo → valutazione economica → registrazione.

## 3. Obiettivi

- Guidare l'operatore nei due passaggi classici della prova alla tocca: confronto visivo sulla pietra + conferma con reagente acido.
- Calcolare automaticamente il valore dell'oggetto (valore pieno e valore di acquisto scontato) in base a peso, titolo e quotazione corrente.
- Tenere un registro locale delle operazioni, esportabile in CSV.
- Essere utilizzabile **senza connessione e senza backend**: tutti i dati restano sul dispositivo (localStorage).
- Essere chiaro sui limiti dello strumento (non è un certificato di analisi).

## 4. Non-obiettivi (fuori scope per la v1)

- Connessione a quotazioni oro in tempo reale via API esterne (la quotazione è inserita manualmente in Impostazioni).
- Autenticazione multi-utente, ruoli, sincronizzazione cloud multi-dispositivo.
- Emissione di documenti fiscali (ricevute, F23, registro USURA/antiriciclaggio conformi alla normativa italiana per compro oro) — solo esportazione dati grezzi.
- Riconoscimento automatico via fotocamera/AI del colore dello striscio.
- App nativa iOS/Android (v1 è una web app responsive).

## 5. Utenti target

- Titolari e operatori di negozi "compro oro".
- Orafi e gioiellerie che valutano oggetti usati.
- Studenti/apprendisti orafi che vogliono un supporto didattico alla procedura.

## 6. User story principali

1. *Come operatore*, voglio selezionare la tonalità dello striscio più simile a quella del mio pezzo confrontandola con gli aghi di riferimento, per ottenere una stima iniziale del titolo.
2. *Come operatore*, voglio applicare il reagente acido suggerito e registrare la reazione osservata, per confermare o correggere la stima iniziale.
3. *Come operatore*, voglio che il titolo confermato passi automaticamente al modulo di valutazione, per non dover reinserire i dati.
4. *Come operatore*, voglio inserire peso e dati del cliente e vedere subito il valore pieno e il valore di acquisto (scontato del margine impostato).
5. *Come titolare*, voglio impostare quotazione oro e percentuale di sconto d'acquisto una volta sola, e vederle applicate automaticamente in dashboard e valutazioni.
6. *Come titolare*, voglio consultare ed esportare in CSV lo storico delle operazioni registrate.

## 7. Funzionalità (v1 — implementate)

### 7.1 Dashboard
- Quotazione corrente dell'oro fino (24K) impostata dall'utente.
- Tabella prezzo al grammo per ogni titolo standard (24, 22, 18, 14, 12, 10, 9, 8 K), sia a valore pieno sia a prezzo di acquisto scontato.
- Statistiche rapide: numero operazioni registrate, totale pagato ai clienti, peso complessivo trattato.

### 7.2 Prova alla Tocca (modulo guidato)
- **Step 1 — Confronto visivo**: selezione del titolo la cui tonalità di striscio è più simile a quella osservata sulla pietra di paragone, tra gli 8 titoli standard (aghi di riferimento).
- **Step 2 — Conferma con acido**: selezione del reagente (8K, 10K, 14K, 18K, 22K, acqua regia — con suggerimento automatico in base alla stima visiva) e della reazione osservata (nessuna reazione, lieve, forte, macchia verde), con verdetto testuale che conferma o suggerisce di ripetere la prova con un reagente diverso.
- Campo note libere (punzoni, colore lega, condizioni del pezzo).
- Salvataggio della prova nello storico locale e passaggio diretto del titolo confermato al modulo Valutazione.
- Disclaimer visibile sui limiti della prova.

### 7.3 Valutazione
- Form con nome cliente, descrizione oggetto, peso (grammi), titolo (precompilabile dalla prova alla tocca o selezionabile manualmente).
- Calcolo in tempo reale di: valore pieno, sconto di acquisto applicato, importo da corrispondere al cliente.
- Registrazione dell'operazione nel registro con un click.

### 7.4 Registro
- Elenco delle operazioni registrate (data, cliente, oggetto, peso, titolo, importo pagato).
- Eliminazione singola voce.
- Esportazione dell'intero registro in CSV.

### 7.5 Impostazioni
- Nome e indirizzo dell'attività (per uso futuro in ricevute).
- Quotazione oro fino 24K (€/grammo).
- Percentuale di sconto/margine applicato in acquisto.

## 8. Requisiti non funzionali

- **Offline-first**: nessuna dipendenza da backend; dati salvati in `localStorage` del browser.
- **Responsive**: utilizzabile su tablet/desktop nel punto vendita.
- **Performance**: SPA leggera (bundle < 300 KB gzip), caricamento istantaneo.
- **Localizzazione**: interfaccia in italiano, valute e date formattate secondo locale `it-IT`.
- **Accessibilità di base**: contrasto adeguato su sfondo scuro, elementi interattivi navigabili da tastiera.

## 9. Metriche di successo

- Tempo medio per completare il ciclo prova → valutazione → registrazione < 90 secondi.
- Zero errori di calcolo rispetto alla formula di riferimento (verificato con test manuali/unitari sulla libreria di valutazione).
- Adozione: registro popolato con operazioni reali entro la prima settimana di utilizzo in un punto vendita pilota.

## 10. Rischi e mitigazioni

| Rischio | Mitigazione |
|---|---|
| L'utente considera il risultato della prova come un'analisi certificata | Disclaimer esplicito nel modulo Prova alla Tocca e in footer |
| Perdita dati per pulizia cache/localStorage del browser | Esportazione CSV disponibile in ogni momento; roadmap: backup/export completo |
| Errori di battitura nella quotazione oro portano a valutazioni errate | Valore mostrato in tempo reale prima del salvataggio, per permettere un controllo visivo |

## 11. Roadmap futura

Vedi `todo.md` per il backlog dettagliato. In sintesi: integrazione quotazione oro live, generazione ricevuta PDF stampabile, registro conforme alla normativa antiriciclaggio per compro oro, multi-negozio con sincronizzazione cloud opzionale, riconoscimento foto dello striscio.
