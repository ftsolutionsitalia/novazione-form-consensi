import type { LedgerEntry, Settings, TouchTestRecord } from "../types";

const KEYS = {
  settings: "oat.settings.v1",
  ledger: "oat.ledger.v1",
  touchTests: "oat.touchTests.v1",
} as const;

export const DEFAULT_SETTINGS: Settings = {
  shopName: "Compro Oro",
  shopAddress: "",
  pricePerGram24k: 75,
  buyDiscountPercent: 15,
};

function read<T>(key: string, fallback: T): T {
  try {
    const raw = localStorage.getItem(key);
    return raw ? (JSON.parse(raw) as T) : fallback;
  } catch {
    return fallback;
  }
}

function write<T>(key: string, value: T): void {
  localStorage.setItem(key, JSON.stringify(value));
}

export const settingsStore = {
  load: (): Settings => read(KEYS.settings, DEFAULT_SETTINGS),
  save: (settings: Settings): void => write(KEYS.settings, settings),
};

export const ledgerStore = {
  load: (): LedgerEntry[] => read(KEYS.ledger, [] as LedgerEntry[]),
  save: (entries: LedgerEntry[]): void => write(KEYS.ledger, entries),
  add: (entry: LedgerEntry): LedgerEntry[] => {
    const entries = [entry, ...ledgerStore.load()];
    ledgerStore.save(entries);
    return entries;
  },
  remove: (id: string): LedgerEntry[] => {
    const entries = ledgerStore.load().filter((e) => e.id !== id);
    ledgerStore.save(entries);
    return entries;
  },
};

export const touchTestStore = {
  load: (): TouchTestRecord[] => read(KEYS.touchTests, [] as TouchTestRecord[]),
  save: (records: TouchTestRecord[]): void => write(KEYS.touchTests, records),
  add: (record: TouchTestRecord): TouchTestRecord[] => {
    const records = [record, ...touchTestStore.load()];
    touchTestStore.save(records);
    return records;
  },
};

export function newId(): string {
  return crypto.randomUUID();
}
