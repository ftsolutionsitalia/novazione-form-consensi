export interface KaratRef {
  karat: number;
  millesimi: number; // fineness per mille
  label: string;
  streakColor: string; // approximate hex for the touchstone streak of a yellow-gold alloy at this fineness
}

// Standard "aghi di paragone" (comparison needles) found in a goldsmith's touchstone kit.
export const KARAT_REFERENCE: KaratRef[] = [
  { karat: 24, millesimi: 999, label: "24 K – oro fino", streakColor: "#e7b53c" },
  { karat: 22, millesimi: 916, label: "22 K", streakColor: "#d9ac3f" },
  { karat: 18, millesimi: 750, label: "18 K", streakColor: "#c89a3f" },
  { karat: 14, millesimi: 585, label: "14 K", streakColor: "#b78c46" },
  { karat: 12, millesimi: 500, label: "12 K", streakColor: "#a9814a" },
  { karat: 10, millesimi: 417, label: "10 K", streakColor: "#9c7a52" },
  { karat: 9, millesimi: 375, label: "9 K", streakColor: "#93755a" },
  { karat: 8, millesimi: 333, label: "8 K", streakColor: "#8a7060" },
];

export interface AcidReagent {
  id: string;
  label: string;
  thresholdMillesimi: number; // reagent formulated to attack alloys below this fineness
  description: string;
}

// Typical reagent tiers sold in touchstone assay kits, used in ascending order.
export const ACID_REAGENTS: AcidReagent[] = [
  { id: "acid-8k", label: "Acido 8 K", thresholdMillesimi: 333, description: "Attacca leghe sotto gli 8 K." },
  { id: "acid-10k", label: "Acido 10 K", thresholdMillesimi: 417, description: "Attacca leghe sotto i 10 K." },
  { id: "acid-14k", label: "Acido 14 K", thresholdMillesimi: 585, description: "Attacca leghe sotto i 14 K." },
  { id: "acid-18k", label: "Acido 18 K", thresholdMillesimi: 750, description: "Attacca leghe sotto i 18 K." },
  { id: "acid-22k", label: "Acido 22 K", thresholdMillesimi: 916, description: "Attacca leghe sotto i 22 K." },
  {
    id: "aqua-regia",
    label: "Acqua regia",
    thresholdMillesimi: 999,
    description: "Scioglie qualsiasi oro; usata per confermare l'oro fino (24 K) e smascherare i falsi.",
  },
];

export type AcidReaction = "nessuna" | "lieve" | "forte" | "verde";

export const ACID_REACTION_LABELS: Record<AcidReaction, string> = {
  nessuna: "Nessuna reazione (lo striscio resta invariato)",
  lieve: "Leggera effervescenza / lieve schiarimento",
  forte: "Effervescenza forte / lo striscio scompare rapidamente",
  verde: "Macchia verde (probabile lega di rame/metallo base, no oro)",
};

export function nearestKarat(millesimi: number): KaratRef {
  return KARAT_REFERENCE.reduce((closest, ref) =>
    Math.abs(ref.millesimi - millesimi) < Math.abs(closest.millesimi - millesimi) ? ref : closest,
  );
}
