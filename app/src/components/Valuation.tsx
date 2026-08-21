import { useEffect, useState } from "react";
import { KARAT_REFERENCE } from "../data/goldReference";
import { newId, ledgerStore } from "../lib/storage";
import { computeGoldValue, formatEUR } from "../lib/valuation";
import type { LedgerEntry, Settings } from "../types";

interface ValuationProps {
  settings: Settings;
  prefill: { millesimi: number; karat: number; touchTestId: string } | null;
  onSaved: () => void;
}

export default function Valuation({ settings, prefill, onSaved }: ValuationProps) {
  const [clientName, setClientName] = useState("");
  const [itemDescription, setItemDescription] = useState("");
  const [weightGrams, setWeightGrams] = useState<string>("");
  const [millesimi, setMillesimi] = useState<number>(prefill?.millesimi ?? 750);
  const [savedMsg, setSavedMsg] = useState(false);

  useEffect(() => {
    if (prefill) setMillesimi(prefill.millesimi);
  }, [prefill]);

  const weight = parseFloat(weightGrams.replace(",", ".")) || 0;
  const { valueBeforeDiscount, valuePaid } = computeGoldValue(
    weight,
    millesimi,
    settings.pricePerGram24k,
    settings.buyDiscountPercent,
  );

  function handleSave() {
    if (weight <= 0) return;
    const karatRef = KARAT_REFERENCE.find((k) => k.millesimi === millesimi);
    const entry: LedgerEntry = {
      id: newId(),
      createdAt: new Date().toISOString(),
      clientName: clientName || "Cliente non specificato",
      itemDescription: itemDescription || "Oggetto in oro",
      weightGrams: weight,
      karat: karatRef?.karat ?? 0,
      millesimi,
      pricePerGram24kAtSale: settings.pricePerGram24k,
      buyDiscountPercent: settings.buyDiscountPercent,
      valueBeforeDiscount,
      valuePaid,
      touchTestId: prefill?.touchTestId,
    };
    ledgerStore.add(entry);
    setSavedMsg(true);
    setClientName("");
    setItemDescription("");
    setWeightGrams("");
    onSaved();
    setTimeout(() => setSavedMsg(false), 2500);
  }

  return (
    <div className="space-y-6 max-w-xl">
      <section className="rounded-xl border border-white/10 bg-white/5 p-4 space-y-4">
        <h2 className="font-serif-display text-gold-200">Valutazione oggetto</h2>

        <Field label="Nome cliente">
          <input
            value={clientName}
            onChange={(e) => setClientName(e.target.value)}
            className="input"
            placeholder="Mario Rossi"
          />
        </Field>

        <Field label="Descrizione oggetto">
          <input
            value={itemDescription}
            onChange={(e) => setItemDescription(e.target.value)}
            className="input"
            placeholder="Anello, catena, moneta..."
          />
        </Field>

        <Field label="Peso (grammi)">
          <input
            value={weightGrams}
            onChange={(e) => setWeightGrams(e.target.value)}
            className="input"
            inputMode="decimal"
            placeholder="0.00"
          />
        </Field>

        <Field label="Titolo (carati)">
          <select
            value={millesimi}
            onChange={(e) => setMillesimi(Number(e.target.value))}
            className="input"
          >
            {KARAT_REFERENCE.map((ref) => (
              <option key={ref.karat} value={ref.millesimi}>
                {ref.label} — {ref.millesimi}‰
              </option>
            ))}
          </select>
        </Field>

        {prefill && (
          <p className="text-xs text-gold-300">
            Titolo precompilato dalla prova alla tocca #{prefill.touchTestId.slice(0, 8)}.
          </p>
        )}

        <div className="rounded-lg bg-black/30 border border-white/10 p-3 space-y-1">
          <Row label="Valore pieno" value={formatEUR(valueBeforeDiscount)} />
          <Row label={`Sconto acquisto (${settings.buyDiscountPercent}%)`} value={`- ${formatEUR(valueBeforeDiscount - valuePaid)}`} />
          <Row label="Da corrispondere al cliente" value={formatEUR(valuePaid)} strong />
        </div>

        <button
          onClick={handleSave}
          disabled={weight <= 0}
          className="w-full rounded-lg bg-gold-400 text-stone-950 font-medium px-4 py-2 text-sm hover:bg-gold-300 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          Registra operazione
        </button>
        {savedMsg && <p className="text-sm text-green-400">Operazione salvata nel registro.</p>}
      </section>
    </div>
  );
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <label className="block">
      <span className="block text-xs text-white/50 mb-1">{label}</span>
      {children}
    </label>
  );
}

function Row({ label, value, strong }: { label: string; value: string; strong?: boolean }) {
  return (
    <div className={`flex justify-between text-sm ${strong ? "text-gold-200 font-medium text-base" : "text-white/60"}`}>
      <span>{label}</span>
      <span>{value}</span>
    </div>
  );
}
