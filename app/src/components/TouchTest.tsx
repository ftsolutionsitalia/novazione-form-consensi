import { useState } from "react";
import {
  ACID_REACTION_LABELS,
  ACID_REAGENTS,
  KARAT_REFERENCE,
  type AcidReaction,
  type KaratRef,
} from "../data/goldReference";
import { newId, touchTestStore } from "../lib/storage";
import type { TouchTestRecord } from "../types";

interface TouchTestProps {
  onConfirmed: (millesimi: number, karat: number, recordId: string) => void;
}

export default function TouchTest({ onConfirmed }: TouchTestProps) {
  const [selected, setSelected] = useState<KaratRef | null>(null);
  const [reagentId, setReagentId] = useState<string | null>(null);
  const [reaction, setReaction] = useState<AcidReaction | null>(null);
  const [notes, setNotes] = useState("");
  const [saved, setSaved] = useState<TouchTestRecord | null>(null);

  const suggestedReagent = selected
    ? ACID_REAGENTS.find((r) => r.thresholdMillesimi >= selected.millesimi) ?? ACID_REAGENTS[ACID_REAGENTS.length - 1]
    : null;

  let verdict: string | null = null;
  if (selected && reagentId && reaction) {
    const reagent = ACID_REAGENTS.find((r) => r.id === reagentId)!;
    if (reaction === "verde") {
      verdict = "Probabile lega di rame o metallo non prezioso: non oro, oppure oro placcato/basso titolo. Ripetere la prova su un punto più profondo del pezzo.";
    } else if (reaction === "forte") {
      verdict = `Il titolo è inferiore alla soglia dell'${reagent.label}. Ripetere la prova con un reagente più leggero.`;
    } else if (reaction === "lieve") {
      verdict = `Reazione lieve: il titolo è vicino alla soglia dell'${reagent.label}, verificare con il reagente immediatamente inferiore.`;
    } else {
      verdict = `Nessuna reazione: il titolo stimato di ${selected.label} (${selected.millesimi}‰) è confermato.`;
    }
  }

  function handleSave() {
    if (!selected) return;
    const record: TouchTestRecord = {
      id: newId(),
      createdAt: new Date().toISOString(),
      estimatedKarat: selected.karat,
      estimatedMillesimi: selected.millesimi,
      reagentUsed: reagentId,
      reaction,
      notes,
    };
    touchTestStore.add(record);
    setSaved(record);
  }

  return (
    <div className="space-y-6">
      <section className="rounded-xl border border-white/10 bg-white/5 p-4">
        <h2 className="font-serif-display text-gold-200 mb-1">1. Confronto visivo sulla pietra di paragone</h2>
        <p className="text-sm text-white/50 mb-4">
          Strofinare il pezzo sulla pietra di paragone (basanite) e confrontare lo striscio ottenuto con gli aghi di
          riferimento. Selezionare la tonalità più simile.
        </p>
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
          {KARAT_REFERENCE.map((ref) => (
            <button
              key={ref.karat}
              onClick={() => {
                setSelected(ref);
                setReagentId(null);
                setReaction(null);
                setSaved(null);
              }}
              className={`rounded-lg border p-3 text-left transition-colors ${
                selected?.karat === ref.karat
                  ? "border-gold-400 bg-gold-400/10"
                  : "border-white/10 hover:border-white/30"
              }`}
            >
              <div
                className="touchstone-streak h-3 rounded-full mb-2"
                style={{ backgroundColor: ref.streakColor }}
              />
              <p className="text-sm font-medium">{ref.label}</p>
              <p className="text-xs text-white/40">{ref.millesimi}‰</p>
            </button>
          ))}
        </div>
      </section>

      {selected && (
        <section className="rounded-xl border border-white/10 bg-white/5 p-4">
          <h2 className="font-serif-display text-gold-200 mb-1">2. Conferma con acido</h2>
          <p className="text-sm text-white/50 mb-4">
            Reagente suggerito in base alla stima visiva: <strong>{suggestedReagent?.label}</strong>. Applicare una
            goccia sullo striscio e osservare la reazione.
          </p>
          <div className="flex flex-wrap gap-2 mb-4">
            {ACID_REAGENTS.map((reagent) => (
              <button
                key={reagent.id}
                onClick={() => setReagentId(reagent.id)}
                className={`rounded-full px-3 py-1.5 text-sm border transition-colors ${
                  reagentId === reagent.id
                    ? "border-gold-400 bg-gold-400/10 text-gold-200"
                    : "border-white/10 text-white/60 hover:border-white/30"
                }`}
              >
                {reagent.label}
              </button>
            ))}
          </div>

          {reagentId && (
            <div className="space-y-2 mb-4">
              <p className="text-sm text-white/70">Reazione osservata:</p>
              <div className="flex flex-wrap gap-2">
                {(Object.keys(ACID_REACTION_LABELS) as AcidReaction[]).map((key) => (
                  <button
                    key={key}
                    onClick={() => setReaction(key)}
                    className={`rounded-lg px-3 py-1.5 text-sm border text-left transition-colors ${
                      reaction === key
                        ? "border-gold-400 bg-gold-400/10 text-gold-200"
                        : "border-white/10 text-white/60 hover:border-white/30"
                    }`}
                  >
                    {ACID_REACTION_LABELS[key]}
                  </button>
                ))}
              </div>
            </div>
          )}

          {verdict && (
            <div className="rounded-lg bg-gold-400/10 border border-gold-400/30 p-3 text-sm text-gold-100 mb-4">
              {verdict}
            </div>
          )}

          <textarea
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            placeholder="Note aggiuntive (es. punzoni, colore lega, condizioni del pezzo)..."
            className="w-full rounded-lg bg-black/30 border border-white/10 p-2 text-sm text-white placeholder:text-white/30 mb-4"
            rows={2}
          />

          <div className="flex flex-wrap gap-2">
            <button
              onClick={handleSave}
              className="rounded-lg bg-gold-400 text-stone-950 font-medium px-4 py-2 text-sm hover:bg-gold-300"
            >
              Salva prova
            </button>
            {saved && (
              <button
                onClick={() => onConfirmed(saved.estimatedMillesimi, saved.estimatedKarat, saved.id)}
                className="rounded-lg border border-gold-400/50 text-gold-200 px-4 py-2 text-sm hover:bg-gold-400/10"
              >
                Usa questo risultato in Valutazione →
              </button>
            )}
          </div>
        </section>
      )}

      <p className="text-xs text-white/30 italic">
        Nota: la prova alla tocca è uno strumento di stima orientativa impiegato tradizionalmente dagli orafi. Per
        transazioni di valore rilevante affiancare sempre un saggio più preciso (spettrometria XRF, saggio al fuoco)
        o l'analisi di un laboratorio abilitato.
      </p>
    </div>
  );
}
