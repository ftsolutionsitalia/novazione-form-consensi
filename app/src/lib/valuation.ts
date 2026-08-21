export function computeGoldValue(
  weightGrams: number,
  millesimi: number,
  pricePerGram24k: number,
  buyDiscountPercent: number,
): { valueBeforeDiscount: number; valuePaid: number } {
  const pricePerGramAtFineness = pricePerGram24k * (millesimi / 999);
  const valueBeforeDiscount = weightGrams * pricePerGramAtFineness;
  const valuePaid = valueBeforeDiscount * (1 - buyDiscountPercent / 100);
  return {
    valueBeforeDiscount: round2(valueBeforeDiscount),
    valuePaid: round2(valuePaid),
  };
}

export function round2(n: number): number {
  return Math.round(n * 100) / 100;
}

export function formatEUR(n: number): string {
  return new Intl.NumberFormat("it-IT", { style: "currency", currency: "EUR" }).format(n);
}
