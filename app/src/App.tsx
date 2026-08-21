import { useState } from "react";
import Header, { type Tab } from "./components/Header";
import Dashboard from "./components/Dashboard";
import TouchTest from "./components/TouchTest";
import Valuation from "./components/Valuation";
import Ledger from "./components/Ledger";
import SettingsPanel from "./components/SettingsPanel";
import { ledgerStore, settingsStore } from "./lib/storage";
import type { LedgerEntry, Settings } from "./types";

export default function App() {
  const [tab, setTab] = useState<Tab>("dashboard");
  const [settings, setSettings] = useState<Settings>(() => settingsStore.load());
  const [ledger, setLedger] = useState<LedgerEntry[]>(() => ledgerStore.load());
  const [prefill, setPrefill] = useState<{ millesimi: number; karat: number; touchTestId: string } | null>(null);

  function refreshLedger() {
    setLedger(ledgerStore.load());
  }

  return (
    <div className="min-h-screen">
      <Header active={tab} onChange={setTab} />
      <main className="mx-auto max-w-5xl px-4 py-6">
        {tab === "dashboard" && <Dashboard settings={settings} ledger={ledger} />}
        {tab === "tocca" && (
          <TouchTest
            onConfirmed={(millesimi, karat, touchTestId) => {
              setPrefill({ millesimi, karat, touchTestId });
              setTab("valutazione");
            }}
          />
        )}
        {tab === "valutazione" && (
          <Valuation
            settings={settings}
            prefill={prefill}
            onSaved={() => {
              refreshLedger();
              setPrefill(null);
            }}
          />
        )}
        {tab === "registro" && <Ledger entries={ledger} onChange={refreshLedger} />}
        {tab === "impostazioni" && <SettingsPanel settings={settings} onSaved={setSettings} />}
      </main>
      <footer className="mx-auto max-w-5xl px-4 py-8 text-xs text-white/30">
        Oro alla Tocca — strumento di supporto alla stima del titolo dell'oro. Non sostituisce un saggio di
        laboratorio abilitato.
      </footer>
    </div>
  );
}
