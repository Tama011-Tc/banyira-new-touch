/** Money arrives as integer minor units (tambala) and is only divided for display. */
export function mk(minor: number | null | undefined): string {
    if (minor === null || minor === undefined) return '-';
    const whole = minor % 100 === 0;
    const text = new Intl.NumberFormat('en', { minimumFractionDigits: whole ? 0 : 2, maximumFractionDigits: whole ? 0 : 2 }).format(Math.abs(minor) / 100);
    return `${minor < 0 ? '-' : ''}MK ${text}`;
}

export const pct = (bp: number): string => `${(bp / 100).toFixed(bp % 100 === 0 ? 0 : 2)}%`;

export function date(value: string | null | undefined): string {
    if (!value) return '-';
    return new Date(value.length === 10 ? value + 'T00:00:00' : value).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

/** Whole days from today until a YYYY-MM-DD date; negative once it has passed. */
export function daysUntil(value: string): number {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return Math.round((new Date(value + 'T00:00:00').getTime() - today.getTime()) / 86400000);
}

export function closes(value: string): string {
    const d = daysUntil(value);
    return d < 0 ? 'Closed' : d === 0 ? 'Closes today' : d === 1 ? 'Closes tomorrow' : d <= 14 ? `${d} days left` : `Closes ${date(value)}`;
}

export const label =(s: string | null | undefined): string => (s ? s.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase()) : '');

type Tone = 'neutral' | 'good' | 'warn' | 'bad' | 'demo';
const tones: Record<string, Tone> = {
    approved: 'good', published: 'good', funded: 'good', repayment_active: 'good', completed: 'good', active: 'good', paid: 'good', verified: 'good', succeeded: 'good', delivered: 'good', released: 'good', matched: 'good', simulated: 'demo',
    submitted: 'warn', screening: 'warn', assessment: 'warn', info_requested: 'warn', listing_prepared: 'warn', funding: 'warn', pending_funding: 'warn', pending_approval: 'warn', pending: 'warn', partial: 'warn', pending_review: 'warn', initiated: 'warn', open: 'warn', investigating: 'warn', draft: 'neutral', reviewed: 'warn', restructured: 'warn',
    rejected: 'bad', overdue: 'bad', defaulted: 'bad', failed: 'bad', blocked: 'bad', mismatch: 'bad', suspended: 'bad', cancelled: 'neutral', withdrawn: 'neutral', expired: 'neutral', reversed: 'bad', upheld: 'bad', declined: 'bad', superseded: 'neutral',
};
export const tone = (s: string): Tone => tones[s] ?? 'neutral';

export const freq = (f: string): string => (f === 'weekly' ? 'weekly' : 'monthly');
export const method = (m: string): string => (m === 'flat' ? 'Flat rate on the original amount' : 'Reducing balance');
