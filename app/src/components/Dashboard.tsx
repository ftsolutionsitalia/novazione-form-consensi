import { KARAT_REFERENCE } from "../data/goldReference";
import { formatEUR } from "../lib/valuation";
import type { LedgerEntry, Settings } from "../types";

interface DashboardProps {
  settings: Settings;
  ledger: LedgerEntry[];
}

export default function Dashboard({ settings, ledger }: DashboardProps) {
  const totalPaid = ledger.reduce((sum, e) => sum + e.valuePaid, 0);
  const totalWeight = ledger.reduce((sum, e) => sum + e.weightGrams, 0);

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <StatCard label="Quotazione oro fino (24K)" value={`${formatEUR(settings.pricePerGram24k)} / g`} />
        <StatCard label="Operazioni registrate" value={String(ledger.length)} />
        <StatCard label="Totale pagato ai clienti" value={formatEUR(totalPaid)} sub={`${totalWeight.toFixed(2)} g complessivi`} />
      </div>

      <div className="rounded-xl border border-white/10 bg-white/5 p-4">
        <h2 className="font-serif-display text-gold-200 mb-3">Tabella prezzi per carato</h2>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-white/50 border-b border-white/10">
                <th className="py-2 pr-4">Carati</th>
                <th className="py-2 pr-4">Millesimi</th>
                <th className="py-2 pr-4">€/g (valore pieno)</th>
                <th className="py-2 pr-4">€/g (prezzo d'acquisto)</th>
              </tr>
            </thead>
            <tbody>
              {KARAT_REFERENCE.map((ref) => {
                const fullPrice = settings.pricePerGram24k * (ref.millesimi / 999);
                const buyPrice = fullPrice * (1 - settings.buyDiscountPercent / 100);
                return (
                  <tr key={ref.karat} className="border-b border-white/5">
                    <td className="py-2 pr-4">{ref.label}</td>
                    <td className="py-2 pr-4 text-white/60">{ref.millesimi}‰</td>
                    <td className="py-2 pr-4">{formatEUR(fullPrice)}</td>
                    <td className="py-2 pr-4 text-gold-300">{formatEUR(buyPrice)}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

function StatCard({ label, value, sub }: { label: string; value: string; sub?: string }) {
  return (
    <div className="rounded-xl border border-white/10 bg-white/5 p-4">
      <p className="text-xs text-white/50">{label}</p>
      <p className="text-xl font-medium text-gold-200 mt-1">{value}</p>
      {sub && <p className="text-xs text-white/40 mt-0.5">{sub}</p>}
    </div>
  );
}
