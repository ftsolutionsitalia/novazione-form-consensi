import { KARAT_REFERENCE } from "../data/goldReference";
import { formatEUR } from "../lib/valuation";
import type { LedgerEntry, Settings } from "../types";

interface ReceiptProps {
  entry: LedgerEntry;
  settings: Settings;
  onClose: () => void;
}

export default function Receipt({ entry, settings, onClose }: ReceiptProps) {
  const karatRef = KARAT_REFERENCE.find((k) => k.millesimi === entry.millesimi);

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-label="Ricevuta d'acquisto oro"
      className="fixed inset-0 z-20 flex items-start justify-center overflow-y-auto bg-black/70 p-4 print:static print:bg-transparent print:p-0"
    >
      <div className="receipt-print w-full max-w-md rounded-xl bg-[#17140f] border border-white/10 p-6 my-8 print:my-0 print:border-0 print:rounded-none print:bg-white print:text-black">
        <div className="flex items-start justify-between mb-4 print:mb-6">
          <div>
            <h2 className="font-serif-display text-lg text-gold-200 print:text-black">
              {settings.shopName || "Compro Oro"}
            </h2>
            {settings.shopAddress && (
              <p className="text-xs text-white/50 print:text-black">{settings.shopAddress}</p>
            )}
          </div>
          <p className="text-xs text-white/40 print:text-black text-right">
            {new Date(entry.createdAt).toLocaleString("it-IT")}
            <br />
            Ricevuta #{entry.id.slice(0, 8)}
          </p>
        </div>

        <h3 className="text-sm font-medium text-gold-200 print:text-black mb-2">Ricevuta d'acquisto oro</h3>

        <dl className="text-sm space-y-1 mb-4">
          <Row label="Cliente" value={entry.clientName} />
          <Row label="Oggetto" value={entry.itemDescription} />
          <Row label="Peso" value={`${entry.weightGrams.toFixed(2)} g`} />
          <Row label="Titolo" value={`${karatRef?.label ?? entry.karat + " K"} (${entry.millesimi}‰)`} />
          <Row label="Quotazione 24K applicata" value={`${formatEUR(entry.pricePerGram24kAtSale)} / g`} />
          <Row label="Sconto d'acquisto" value={`${entry.buyDiscountPercent}%`} />
        </dl>

        <div className="rounded-lg bg-black/30 border border-white/10 p-3 space-y-1 mb-4 print:border-black/20 print:bg-transparent">
          <Row label="Valore pieno" value={formatEUR(entry.valueBeforeDiscount)} />
          <Row
            label="Importo corrisposto al cliente"
            value={formatEUR(entry.valuePaid)}
            strong
          />
        </div>

        <p className="text-xs text-white/30 print:text-black/60 mb-6">
          Il titolo indicato è stato stimato tramite prova alla tocca, uno strumento di supporto operativo e non
          sostituisce un saggio di laboratorio abilitato.
        </p>

        <div className="flex gap-2 print:hidden">
          <button
            onClick={() => window.print()}
            className="rounded-lg bg-gold-400 text-stone-950 font-medium px-4 py-2 text-sm hover:bg-gold-300"
          >
            Stampa ricevuta
          </button>
          <button
            onClick={onClose}
            className="rounded-lg border border-white/10 px-4 py-2 text-sm text-white/70 hover:border-white/30"
          >
            Chiudi
          </button>
        </div>
      </div>
    </div>
  );
}

function Row({ label, value, strong }: { label: string; value: string; strong?: boolean }) {
  return (
    <div
      className={`flex justify-between gap-4 ${
        strong ? "text-gold-200 print:text-black font-medium text-base" : "text-white/70 print:text-black/80"
      }`}
    >
      <dt>{label}</dt>
      <dd className="text-right">{value}</dd>
    </div>
  );
}
