import { describe, expect, it } from "vitest";
import { InvalidBackupError, parseBackup } from "./backup";

const validBackup = {
  version: 1,
  exportedAt: "2026-01-01T00:00:00.000Z",
  settings: { shopName: "Test", shopAddress: "", pricePerGram24k: 75, buyDiscountPercent: 15 },
  ledger: [{ id: "a", weightGrams: 5 }],
  touchTests: [{ id: "b", estimatedMillesimi: 750 }],
};

describe("parseBackup", () => {
  it("parses a well-formed backup", () => {
    const parsed = parseBackup(JSON.stringify(validBackup));
    expect(parsed.settings.shopName).toBe("Test");
    expect(parsed.ledger).toHaveLength(1);
    expect(parsed.touchTests).toHaveLength(1);
  });

  it("rejects invalid JSON", () => {
    expect(() => parseBackup("{not valid json")).toThrow(InvalidBackupError);
  });

  it("rejects a JSON value that isn't an object", () => {
    expect(() => parseBackup("42")).toThrow(InvalidBackupError);
  });

  it("rejects a backup missing settings", () => {
    const { settings, ...rest } = validBackup;
    void settings;
    expect(() => parseBackup(JSON.stringify(rest))).toThrow(InvalidBackupError);
  });

  it("rejects a backup with a malformed ledger", () => {
    const broken = { ...validBackup, ledger: "not-an-array" };
    expect(() => parseBackup(JSON.stringify(broken))).toThrow(InvalidBackupError);
  });
});
