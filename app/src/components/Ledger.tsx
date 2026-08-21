import { ledgerStore } from "../lib/storage";
import { formatEUR } from "../lib/valuation";
import type { LedgerEntry } from "../types";

interface LedgerProps {
  entries: LedgerEntry[];
  onChange: () => void;
}

export default function Ledger({ entries, onChange }: LedgerProps) {
  function handleDelete(id: string) {
    ledgerStore.remove(id);
    onChange();
  }

  function exportCSV() {
    const header = ["Data", "Cliente", "Oggetto", "Peso (g)", "Carati", "Millesimi", "Valore pieno", "Pagato"];
    const rows = entries.map((e) => [
      new Date(e.createdAt).toLocaleString("it-IT"),
      e.clientName,
      e.itemDescription,
      e.weightGrams.toString(),
      e.karat.toString(),
      e.millesimi.toString(),
      e.valueBeforeDiscount.toFixed(2),
      e.valuePaid.toFixed(2),
    ]);
    const csv = [header, ...rows].map((r) => r.map((c) => `"${c}"`).join(",")).join("\n");
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `registro-oro-alla-tocca-${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(url);
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="font-serif-display text-gold-200">Registro operazioni</h2>
        <button
          onClick={exportCSV}
          disabled={entries.length === 0}
          className="rounded-lg border border-white/10 px-3 py-1.5 text-sm text-white/70 hover:border-white/30 disabled:opacity-30"
        >
          Esporta CSV
        </button>
      </div>

      {entries.length === 0 ? (
        <p className="text-sm text-white/40">Nessuna operazione registrata.</p>
      ) : (
        <div className="overflow-x-auto rounded-xl border border-white/10">
          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-white/50 bg-white/5">
                <th className="py-2 px-3">Data</th>
                <th className="py-2 px-3">Cliente</th>
                <th className="py-2 px-3">Oggetto</th>
                <th className="py-2 px-3">Peso</th>
                <th className="py-2 px-3">Titolo</th>
                <th className="py-2 px-3">Pagato</th>
                <th className="py-2 px-3"></th>
              </tr>
            </thead>
            <tbody>
              {entries.map((e) => (
                <tr key={e.id} className="border-t border-white/5">
                  <td className="py-2 px-3 text-white/60">{new Date(e.createdAt).toLocaleDateString("it-IT")}</td>
                  <td className="py-2 px-3">{e.clientName}</td>
                  <td className="py-2 px-3 text-white/60">{e.itemDescription}</td>
                  <td className="py-2 px-3">{e.weightGrams.toFixed(2)} g</td>
                  <td className="py-2 px-3">{e.karat} K</td>
                  <td className="py-2 px-3 text-gold-300">{formatEUR(e.valuePaid)}</td>
                  <td className="py-2 px-3">
                    <button
                      onClick={() => handleDelete(e.id)}
                      className="text-xs text-white/30 hover:text-red-400"
                    >
                      Elimina
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
