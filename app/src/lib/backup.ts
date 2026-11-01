import { ledgerStore, settingsStore, touchTestStore } from "./storage";
import type { BackupData, LedgerEntry, Settings, TouchTestRecord } from "../types";

export function buildBackup(): BackupData {
  return {
    version: 1,
    exportedAt: new Date().toISOString(),
    settings: settingsStore.load(),
    ledger: ledgerStore.load(),
    touchTests: touchTestStore.load(),
  };
}

export function downloadBackup(): void {
  const backup = buildBackup();
  const blob = new Blob([JSON.stringify(backup, null, 2)], { type: "application/json" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `oro-alla-tocca-backup-${new Date().toISOString().slice(0, 10)}.json`;
  a.click();
  URL.revokeObjectURL(url);
}

export class InvalidBackupError extends Error {}

function isSettings(value: unknown): value is Settings {
  if (typeof value !== "object" || value === null) return false;
  const s = value as Record<string, unknown>;
  return (
    typeof s.shopName === "string" &&
    typeof s.shopAddress === "string" &&
    typeof s.pricePerGram24k === "number" &&
    typeof s.buyDiscountPercent === "number"
  );
}

function isLedgerEntryArray(value: unknown): value is LedgerEntry[] {
  return Array.isArray(value) && value.every((e) => typeof e === "object" && e !== null && "id" in e && "weightGrams" in e);
}

function isTouchTestArray(value: unknown): value is TouchTestRecord[] {
  return Array.isArray(value) && value.every((e) => typeof e === "object" && e !== null && "id" in e && "estimatedMillesimi" in e);
}

export function parseBackup(raw: string): BackupData {
  let data: unknown;
  try {
    data = JSON.parse(raw);
  } catch {
    throw new InvalidBackupError("Il file non è un JSON valido.");
  }
  if (typeof data !== "object" || data === null) {
    throw new InvalidBackupError("Formato di backup non riconosciuto.");
  }
  const d = data as Record<string, unknown>;
  if (!isSettings(d.settings) || !isLedgerEntryArray(d.ledger) || !isTouchTestArray(d.touchTests)) {
    throw new InvalidBackupError("Il file non contiene i dati attesi (impostazioni, registro, prove).");
  }
  return {
    version: 1,
    exportedAt: typeof d.exportedAt === "string" ? d.exportedAt : new Date().toISOString(),
    settings: d.settings,
    ledger: d.ledger,
    touchTests: d.touchTests,
  };
}

export function applyBackup(backup: BackupData): void {
  settingsStore.save(backup.settings);
  ledgerStore.save(backup.ledger);
  touchTestStore.save(backup.touchTests);
}
