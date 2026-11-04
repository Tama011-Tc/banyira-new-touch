// Formatting helpers. Amounts arrive from the server as decimal strings and are
// only formatted here, never used for arithmetic that affects money.

export const mk = (v: string | number | null | undefined, decimals = 0) =>
  'MK' + Number(v ?? 0).toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })

export const mkShort = (v: string | number) => {
  const n = Number(v)
  if (n >= 1_000_000) return 'MK' + (n / 1_000_000).toFixed(n >= 10_000_000 ? 0 : 1).replace(/\.0$/, '') + 'm'
  if (n >= 1_000) return 'MK' + Math.round(n / 1_000) + 'k'
  return mk(n)
}

export const pct = (v: string | number, decimals = 0) => Number(v).toFixed(decimals) + '%'

export const date = (v: string | null | undefined) =>
  v ? new Date(v).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'

export const dateTime = (v: string | null | undefined) =>
  v ? new Date(v).toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—'

export const months = (n: number) => `${n} month${n === 1 ? '' : 's'}`

export const label = (s: string) => s.replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase())
