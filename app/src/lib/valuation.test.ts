import { describe, expect, it } from "vitest";
import { computeGoldValue, formatEUR, round2 } from "./valuation";

describe("round2", () => {
  it("rounds to two decimals", () => {
    expect(round2(1.236)).toBe(1.24);
    expect(round2(1.234)).toBe(1.23);
    expect(round2(10)).toBe(10);
  });
});

describe("computeGoldValue", () => {
  it("computes full value at 999‰ with no discount", () => {
    const { valueBeforeDiscount, valuePaid } = computeGoldValue(10, 999, 75, 0);
    expect(valueBeforeDiscount).toBe(750);
    expect(valuePaid).toBe(750);
  });

  it("scales value proportionally to fineness", () => {
    const { valueBeforeDiscount } = computeGoldValue(10, 500, 75, 0);
    // 10g * 75€/g * (500/999) ≈ 375.38
    expect(valueBeforeDiscount).toBeCloseTo(375.38, 1);
  });

  it("applies the buy discount percentage", () => {
    const { valueBeforeDiscount, valuePaid } = computeGoldValue(5, 999, 60, 20);
    expect(valueBeforeDiscount).toBe(300);
    expect(valuePaid).toBe(240);
  });

  it("returns zero value for zero weight", () => {
    const { valueBeforeDiscount, valuePaid } = computeGoldValue(0, 750, 75, 15);
    expect(valueBeforeDiscount).toBe(0);
    expect(valuePaid).toBe(0);
  });

  it("never produces a negative paid value for a discount within 0-100", () => {
    const { valuePaid } = computeGoldValue(3, 750, 80, 100);
    expect(valuePaid).toBe(0);
  });
});

describe("formatEUR", () => {
  it("formats using Italian locale currency style", () => {
    const formatted = formatEUR(1234.5);
    expect(formatted).toContain("234,50");
    expect(formatted).toContain("€");
  });
});
