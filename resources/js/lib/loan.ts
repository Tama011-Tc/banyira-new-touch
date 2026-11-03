/** Illustrations for the public site. Same reducing-balance formula as the loan schedules; amounts in minor units, rounded to whole kwacha. */
export type FeeTier = { min: number; max: number | null; bp: number };

const kwacha = (minor: number) => Math.round(minor / 100) * 100;

export function feeFor(principal: number, tiers: FeeTier[]): { bp: number; amount: number } | null {
    const tier = tiers.find((t) => principal >= t.min && (t.max === null || principal < t.max));
    return tier ? { bp: tier.bp, amount: Math.round((principal * tier.bp) / 10000) } : null;
}

export function repayments(principal: number, months: number, ratePct: number, weekly = false) {
    const n = weekly ? Math.floor((months * 52) / 12) : months;
    const r = ratePct / 100 / (weekly ? 52 : 12);
    const pay = r === 0 ? principal / n : (principal * r) / (1 - Math.pow(1 + r, -n));
    return { n, pay: kwacha(pay), total: kwacha(pay * n), interest: kwacha(pay * n - principal) };
}

/** The business fee range from the live schedule, e.g. "4–15%"; null when no schedule is approved. */
export function feeRange(tiers: FeeTier[]): string | null {
    if (!tiers.length) return null;
    const bps = tiers.map((t) => t.bp);
    const fmt = (bp: number) => (bp / 100).toFixed(bp % 100 === 0 ? 0 : 2);
    const lo = Math.min(...bps);
    const hi = Math.max(...bps);
    return lo === hi ? `${fmt(lo)}%` : `${fmt(lo)}–${fmt(hi)}%`;
}
