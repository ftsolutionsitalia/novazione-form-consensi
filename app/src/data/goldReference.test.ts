import { describe, expect, it } from "vitest";
import { KARAT_REFERENCE, nearestKarat } from "./goldReference";

describe("KARAT_REFERENCE", () => {
  it("is sorted from highest to lowest fineness", () => {
    const millesimi = KARAT_REFERENCE.map((r) => r.millesimi);
    const sorted = [...millesimi].sort((a, b) => b - a);
    expect(millesimi).toEqual(sorted);
  });

  it("has no duplicate karat values", () => {
    const karats = KARAT_REFERENCE.map((r) => r.karat);
    expect(new Set(karats).size).toBe(karats.length);
  });
});

describe("nearestKarat", () => {
  it("returns an exact match when the fineness matches a reference", () => {
    expect(nearestKarat(750).karat).toBe(18);
    expect(nearestKarat(999).karat).toBe(24);
  });

  it("returns the closest reference for an in-between value", () => {
    // 600 is closer to 585 (14K) than to 500 (12K)
    expect(nearestKarat(600).karat).toBe(14);
  });

  it("clamps to the lowest reference for very low fineness", () => {
    expect(nearestKarat(50).karat).toBe(8);
  });

  it("clamps to the highest reference for fineness above 999", () => {
    expect(nearestKarat(1000).karat).toBe(24);
  });
});
