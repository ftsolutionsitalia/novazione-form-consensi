import { useState } from "react";
import { settingsStore } from "../lib/storage";
import type { Settings } from "../types";

interface SettingsPanelProps {
  settings: Settings;
  onSaved: (settings: Settings) => void;
}

export default function SettingsPanel({ settings, onSaved }: SettingsPanelProps) {
  const [form, setForm] = useState<Settings>(settings);
  const [savedMsg, setSavedMsg] = useState(false);

  function handleSave() {
    settingsStore.save(form);
    onSaved(form);
    setSavedMsg(true);
    setTimeout(() => setSavedMsg(false), 2000);
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
          className="input"
          inputMode="decimal"
        />
      </label>

      <label className="block">
        <span className="block text-xs text-white/50 mb-1">Sconto di acquisto (%)</span>
        <input
          value={form.buyDiscountPercent}
          onChange={(e) => setForm({ ...form, buyDiscountPercent: Number(e.target.value) || 0 })}
          className="input"
          inputMode="decimal"
        />
        <span className="block text-xs text-white/30 mt-1">
          Margine applicato rispetto al valore pieno quando si acquista dal cliente.
        </span>
      </label>

      <button
        onClick={handleSave}
        className="rounded-lg bg-gold-400 text-stone-950 font-medium px-4 py-2 text-sm hover:bg-gold-300"
      >
        Salva impostazioni
      </button>
      {savedMsg && <p className="text-sm text-green-400">Impostazioni salvate.</p>}
    </div>
  );
}
