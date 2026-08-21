export interface Settings {
  shopName: string;
  shopAddress: string;
  pricePerGram24k: number; // EUR, spot price for 999‰ gold
  buyDiscountPercent: number; // spread applied when the shop buys from a client
}

export interface TouchTestRecord {
  id: string;
  createdAt: string;
  estimatedKarat: number;
  estimatedMillesimi: number;
  reagentUsed: string | null;
  reaction: string | null;
  notes: string;
}

export interface LedgerEntry {
  id: string;
  createdAt: string;
  clientName: string;
  itemDescription: string;
  weightGrams: number;
  karat: number;
  millesimi: number;
  pricePerGram24kAtSale: number;
  buyDiscountPercent: number;
  valueBeforeDiscount: number;
  valuePaid: number;
  touchTestId?: string;
}
