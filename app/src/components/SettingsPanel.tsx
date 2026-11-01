import { useRef, useState } from "react";
import { applyBackup, downloadBackup, InvalidBackupError, parseBackup } from "../lib/backup";
import { ledgerStore, settingsStore, touchTestStore } from "../lib/storage";
import type { Settings } from "../types";

interface SettingsPanelProps {
  settings: Settings;
  onSaved: (settings: Settings) => void;
  onDataImported: () => void;
}

export default function SettingsPanel({ settings, onSaved, onDataImported }: SettingsPanelProps) {
  const [form, setForm] = useState<Settings>(settings);
  const [savedMsg, setSavedMsg] = useState(false);
  const [importMsg, setImportMsg] = useState<{ kind: "ok" | "error"; text: string } | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const priceError = form.pricePerGram24k <= 0 ? "La quotazione deve essere maggiore di zero." : null;
  const discountError =
    form.buyDiscountPercent < 0 || form.buyDiscountPercent > 100 ? "Il valore deve essere tra 0 e 100." : null;
  const isValid = !priceError && !discountError;

  function handleSave() {
    if (!isValid) return;
    settingsStore.save(form);
    onSaved(form);
    setSavedMsg(true);
    setTimeout(() => setSavedMsg(false), 2000);
  }

  function handleImportClick() {
    fileInputRef.current?.click();
  }

  async function handleFileSelected(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    e.target.value = "";
    if (!file) return;
    try {
      const text = await file.text();
      const backup = parseBackup(text);
      const confirmed = window.confirm(
        `Importare questo backup sovrascriverà i dati attuali (${ledgerStore.load().length} operazioni, ` +
          `${touchTestStore.load().length} prove salvate) con quelli del file (${backup.ledger.length} operazioni, ` +
          `${backup.touchTests.length} prove). Continuare?`,
      );
      if (!confirmed) return;
      applyBackup(backup);
      setForm(backup.settings);
      onSaved(backup.settings);
      onDataImported();
      setImportMsg({ kind: "ok", text: "Backup importato correttamente." });
    } catch (err) {
      const text = err instanceof InvalidBackupError ? err.message : "Impossibile leggere il file selezionato.";
      setImportMsg({ kind: "error", text });
    }
  }

  return (
    <div className="max-w-md space-y-4">
      <h2 className="font-serif-display text-gold-200">Impostazioni</h2>

      <label className="block">
        <span className="block text-xs text-white/50 mb-1">Nome attività</span>
        <input
          value={form.shopName}
          onChange={(e) => setForm({ ...form, shopName: e.target.value })}
          className="input"
        />
      </label>

      <label className="block">
        <span className="block text-xs text-white/50 mb-1">Indirizzo</span>
        <input
          value={form.shopAddress}
          onChange={(e) => setForm({ ...form, shopAddress: e.target.value })}
          className="input"
        />
      </label>

      <label className="block">
        <span className="block text-xs text-white/50 mb-1">Quotazione oro fino 24K (€/grammo)</span>
        <input
          value={form.pricePerGram24k}
          onChange={(e) => setForm({ ...form, pricePerGram24k: Number(e.target.value) || 0 })}
          className={`input ${priceError ? "input-error" : ""}`}
          inputMode="decimal"
          aria-invalid={priceError ? true : undefined}
        />
        {priceError && <span className="block text-xs text-red-400 mt-1">{priceError}</span>}
      </label>

      <label className="block">
        <span className="block text-xs text-white/50 mb-1">Sconto di acquisto (%)</span>
        <input
          value={form.buyDiscountPercent}
          onChange={(e) => setForm({ ...form, buyDiscountPercent: Number(e.target.value) || 0 })}
          className={`input ${discountError ? "input-error" : ""}`}
          inputMode="decimal"
          aria-invalid={discountError ? true : undefined}
        />
        {discountError ? (
          <span className="block text-xs text-red-400 mt-1">{discountError}</span>
        ) : (
          <span className="block text-xs text-white/30 mt-1">
            Margine applicato rispetto al valore pieno quando si acquista dal cliente.
          </span>
        )}
      </label>

      <button
        onClick={handleSave}
        disabled={!isValid}
        className="rounded-lg bg-gold-400 text-stone-950 font-medium px-4 py-2 text-sm hover:bg-gold-300 disabled:opacity-40 disabled:cursor-not-allowed"
      >
        Salva impostazioni
      </button>
      {savedMsg && <p className="text-sm text-green-400">Impostazioni salvate.</p>}

      <div className="rounded-xl border border-white/10 bg-white/5 p-4 space-y-3">
        <h3 className="font-serif-display text-gold-200 text-sm">Backup dati</h3>
        <p className="text-xs text-white/50">
          Tutti i dati (impostazioni, registro operazioni, prove alla tocca) sono salvati solo in questo browser.
          Esporta un backup periodicamente per non perderli.
        </p>
        <div className="flex flex-wrap gap-2">
          <button
            onClick={downloadBackup}
            className="rounded-lg border border-white/10 px-3 py-1.5 text-sm text-white/70 hover:border-white/30"
          >
            Esporta backup (JSON)
          </button>
          <button
            onClick={handleImportClick}
            className="rounded-lg border border-white/10 px-3 py-1.5 text-sm text-white/70 hover:border-white/30"
          >
            Importa backup (JSON)
          </button>
          <input
            ref={fileInputRef}
            type="file"
            accept="application/json"
            onChange={handleFileSelected}
            className="hidden"
          />
        </div>
        {importMsg && (
          <p className={`text-sm ${importMsg.kind === "ok" ? "text-green-400" : "text-red-400"}`}>{importMsg.text}</p>
        )}
      </div>
    </div>
  );
}
