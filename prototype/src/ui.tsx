import { useEffect, useId, useMemo, useRef, useState, type ReactNode } from 'react'
import { createStore, RISK_INFO, type Risk } from './store'

export const cn = (...c: (string | false | null | undefined)[]) => c.filter(Boolean).join(' ')

const P: Record<string, string> = {
  home: 'M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10',
  chart: 'M4 20V10M10 20V4M16 20v-8M22 20H2',
  store: 'M3 9l1-5h16l1 5M4 9v11h16V9M9 20v-6h6v6',
  wallet: 'M3 7h16a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2h12M16 14h2',
  mail: 'M3 6h18v12H3zM3 7l9 7 9-7',
  doc: 'M7 3h7l5 5v13H7zM14 3v5h5M10 13h6M10 17h6',
  bell: 'M6 16V11a6 6 0 1112 0v5l2 2H4zM10 21h4',
  user: 'M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0',
  cog: 'M12 15a3 3 0 100-6 3 3 0 000 6zM19 12l2-1-2-4-2 1-2-1-1-2h-4l-1 2-2 1-2-1-2 4 2 1v2l-2 1 2 4 2-1 2 1 1 2h4l1-2 2-1 2 1 2-4-2-1z',
  help: 'M12 21a9 9 0 100-18 9 9 0 000 18zM9.5 9.5a2.5 2.5 0 115 0c0 1.5-2.5 2-2.5 3.5M12 17h.01',
  check: 'M5 12l5 5 9-10',
  x: 'M6 6l12 12M18 6L6 18',
  menu: 'M4 7h16M4 12h16M4 17h16',
  arrow: 'M5 12h14M13 6l6 6-6 6',
  shield: 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z',
  lock: 'M6 11h12v9H6zM8 11V8a4 4 0 118 0v3',
  search: 'M11 18a7 7 0 100-14 7 7 0 000 14zM21 21l-5-5',
  alert: 'M12 3l10 18H2zM12 10v5M12 18h.01',
  info: 'M12 21a9 9 0 100-18 9 9 0 000 18zM12 11v6M12 7.5h.01',
  down: 'M12 4v12M6 11l6 6 6-6M5 21h14',
  plus: 'M12 5v14M5 12h14',
  users: 'M9 11a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM2 20a7 7 0 0114 0M17 4.5a3.5 3.5 0 010 6.5M19 20a6 6 0 00-3-5',
  list: 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01',
  clock: 'M12 21a9 9 0 100-18 9 9 0 000 18zM12 7v5l3 2',
  out: 'M10 4H4v16h6M16 8l4 4-4 4M20 12H9',
  chev: 'M9 6l6 6-6 6',
  chevdown: 'M6 9l6 6 6-6',
}
export function Icon({ n, size = 18, className }: { n: string; size?: number; className?: string }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" className={cn('shrink-0', className)}>
      <path d={P[n] || P.info} />
    </svg>
  )
}

export function Logo({ light = false, size = 22 }: { light?: boolean; size?: number }) {
  return (
    <span className="inline-flex items-center gap-2">
      <svg width={size} height={size} viewBox="0 0 32 32" aria-hidden="true">
        <rect width="32" height="32" rx="6" fill={light ? '#fff' : '#1d4a37'} />
        <path d="M10 24V8h6.5a4.25 4.25 0 010 8.5H10m6.5 0a3.75 3.75 0 010 7.5H10" stroke={light ? '#1d4a37' : '#fff'} strokeWidth="2.4" fill="none" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
      <span className={cn('text-[17px] font-semibold tracking-tight', light ? 'text-white' : 'text-ink')}>Banyira</span>
    </span>
  )
}

type BtnProps = { children: ReactNode; variant?: 'primary' | 'accent' | 'outline' | 'ghost' | 'danger'; href?: string; onClick?: () => void; type?: 'button' | 'submit'; className?: string; disabled?: boolean; size?: 'sm' | 'md' | 'lg'; full?: boolean; label?: string }
export function Btn({ children, variant = 'primary', href, onClick, type = 'button', className, disabled, size = 'md', full, label }: BtnProps) {
  const cls = cn(
    'inline-flex items-center justify-center gap-1.5 rounded-md font-medium whitespace-nowrap select-none transition-colors',
    size === 'sm' && 'h-8 px-3 text-[13px]',
    size === 'md' && 'h-10 px-4 text-sm',
    size === 'lg' && 'h-11 px-5 text-[15px]',
    full && 'w-full',
    variant === 'primary' && 'bg-forest text-white hover:bg-forest-2',
    variant === 'accent' && 'bg-ink text-white hover:bg-black',
    variant === 'outline' && 'border border-line-2 bg-white text-ink hover:border-ink',
    variant === 'ghost' && 'text-ink hover:bg-mint',
    variant === 'danger' && 'bg-danger text-white hover:bg-[#912018]',
    disabled && 'opacity-40 pointer-events-none',
    className,
  )
  if (href) return <a href={href} className={cls} aria-label={label}>{children}</a>
  return <button type={type} onClick={onClick} disabled={disabled} className={cls} aria-label={label}>{children}</button>
}

const tones = {
  gray: 'text-muted bg-[#f1f1ee]', green: 'text-ok bg-[#eef5f0]', amber: 'text-maize bg-maize-soft',
  red: 'text-danger bg-danger-soft', blue: 'text-skyink bg-sky', clay: 'text-clay bg-clay-soft',
}
export function Badge({ children, tone = 'gray' }: { children: ReactNode; tone?: keyof typeof tones }) {
  return <span className={cn('inline-flex items-center rounded px-1.5 py-px text-xs font-medium whitespace-nowrap', tones[tone])}>{children}</span>
}
export function statusTone(s: string): keyof typeof tones {
  const v = s.toLowerCase()
  if (/(active|approved|accepted|complete|paid|verified|healthy|open|success|published|listed|ok|on track|reconciled|sent|funded|running|posted|signed|collected|disbursed|replied|available)/.test(v) && !/(partially|almost|awaiting)/.test(v)) return 'green'
  if (/(pending|review|draft|progress|awaiting|scheduled|almost|partially|due|monitor|queued|warning|proposed|submitted|required|held)/.test(v)) return 'amber'
  if (/(reject|fail|overdue|default|locked|declined|critical|error|blocked|withdrawn|degraded|unmatched|reversed|lost)/.test(v)) return 'red'
  return 'gray'
}
const dot: Record<keyof typeof tones, string> = { gray: 'bg-[#9a9e9f]', green: 'bg-ok', amber: 'bg-maize', red: 'bg-danger', blue: 'bg-skyink', clay: 'bg-clay' }
export const Status = ({ s }: { s: string }) => {
  const t = statusTone(s)
  return <span className="inline-flex items-center gap-1.5 text-[13px] whitespace-nowrap"><span className={cn('h-1.5 w-1.5 rounded-full', dot[t])} aria-hidden="true" />{s}</span>
}

const riskCls: Record<Risk, string> = { 'A+': 'text-ok border-ok/40', A: 'text-ok border-ok/40', B: 'text-maize border-maize/40', C: 'text-clay border-clay/40' }
export function RiskBadge({ r, long = false }: { r: Risk; long?: boolean }) {
  return (
    <span className="inline-flex items-center gap-2" title={`${RISK_INFO[r].label}. A risk rating is an assessment, not a guarantee of repayment.`}>
      <span className={cn('inline-flex min-w-8 justify-center rounded border px-1 font-mono text-xs font-semibold leading-5', riskCls[r])}>{r}</span>
      {long && <span className="text-[13px] text-muted">{RISK_INFO[r].label}</span>}
    </span>
  )
}

export const Card = ({ children, className, title, action }: { children: ReactNode; className?: string; title?: string; action?: ReactNode }) => (
  <section className={cn('rounded-lg border border-line bg-white p-5', className)}>
    {(title || action) && (
      <div className="mb-4 flex items-center justify-between gap-3">
        {title && <h3 className="text-[15px] font-semibold">{title}</h3>}
        {action}
      </div>
    )}
    {children}
  </section>
)
export const Stat = ({ label, value, note, tone }: { label: string; value: string; note?: string; tone?: 'warn' }) => (
  <div className="border-t border-ink/80 pt-2.5">
    <p className="text-[13px] text-muted">{label}</p>
    <p className={cn('mt-0.5 text-[22px] font-semibold tracking-tight tabular-nums', tone === 'warn' && 'text-clay')}>{value}</p>
    {note && <p className="text-xs text-muted">{note}</p>}
  </div>
)
export const Progress = ({ value, label }: { value: number; label?: string }) => (
  <div role="progressbar" aria-valuenow={Math.round(value)} aria-valuemin={0} aria-valuemax={100} aria-label={label || 'Progress'} className="h-1 w-full overflow-hidden rounded-full bg-line">
    <div className="h-full bg-forest" style={{ width: `${Math.min(100, value)}%` }} />
  </div>
)

// ---- Toasts ----
const toasts = createStore<{ id: number; msg: string; tone: 'ok' | 'err' }[]>([])
export function toast(msg: string, tone: 'ok' | 'err' = 'ok') {
  const id = Date.now() + Math.random()
  toasts.set((t) => [...t, { id, msg, tone }])
  setTimeout(() => toasts.set((t) => t.filter((x) => x.id !== id)), 4500)
}
export function Toaster() {
  const t = toasts.use()
  return (
    <div className="fixed bottom-4 left-4 right-4 z-[70] flex flex-col items-end gap-2 sm:left-auto" role="status" aria-live="polite">
      {t.map((x) => (
        <div key={x.id} className={cn('flex max-w-sm items-start gap-2 rounded-md px-3.5 py-2.5 text-sm text-white', x.tone === 'ok' ? 'bg-ink' : 'bg-danger')}>
          <Icon n={x.tone === 'ok' ? 'check' : 'alert'} size={16} className="mt-0.5" />
          {x.msg}
        </div>
      ))}
    </div>
  )
}

export function Modal({ open, onClose, title, children, actions }: { open: boolean; onClose: () => void; title: string; children: ReactNode; actions?: ReactNode }) {
  const ref = useRef<HTMLDivElement>(null)
  useEffect(() => {
    if (!open) return
    const prev = document.activeElement as HTMLElement | null
    const k = (e: KeyboardEvent) => e.key === 'Escape' && onClose()
    window.addEventListener('keydown', k)
    ref.current?.focus()
    return () => { window.removeEventListener('keydown', k); prev?.focus() }
  }, [open, onClose])
  if (!open) return null
  return (
    <div className="fixed inset-0 z-[60] flex items-end justify-center bg-ink/40 sm:items-center sm:p-4" onClick={onClose}>
      <div ref={ref} tabIndex={-1} role="dialog" aria-modal="true" aria-label={title} onClick={(e) => e.stopPropagation()} className="max-h-[90vh] w-full max-w-md overflow-auto rounded-t-lg bg-white p-6 outline-none sm:rounded-lg">
        <h2 className="text-lg">{title}</h2>
        <div className="mt-2 text-sm text-muted">{children}</div>
        <div className="mt-6 flex flex-wrap justify-end gap-2">{actions ?? <Btn variant="outline" onClick={onClose}>Close</Btn>}</div>
      </div>
    </div>
  )
}
export function Confirm({ open, title, body, confirm, danger, onConfirm, onClose }: { open: boolean; title: string; body: ReactNode; confirm: string; danger?: boolean; onConfirm: () => void; onClose: () => void }) {
  return (
    <Modal open={open} onClose={onClose} title={title} actions={<><Btn variant="outline" onClick={onClose}>Cancel</Btn><Btn variant={danger ? 'danger' : 'primary'} onClick={() => { onConfirm(); onClose() }}>{confirm}</Btn></>}>
      {body}
    </Modal>
  )
}

// ---- State panels ----
export const Empty = ({ title, body, action }: { title: string; body: string; action?: ReactNode }) => (
  <div className="rounded-lg border border-dashed border-line-2 px-6 py-10 text-center">
    <h3 className="text-[15px]">{title}</h3>
    <p className="mx-auto mt-1 max-w-sm text-sm text-muted">{body}</p>
    {action && <div className="mt-4">{action}</div>}
  </div>
)
export const ErrorState = ({ onRetry, msg = 'We could not load this information. Your data is safe. Please try again.' }: { onRetry: () => void; msg?: string }) => (
  <div role="alert" className="rounded-lg border border-danger/25 bg-danger-soft px-6 py-8 text-center">
    <h3 className="text-[15px] text-danger">Something went wrong</h3>
    <p className="mx-auto mt-1 max-w-sm text-sm">{msg}</p>
    <div className="mt-4"><Btn variant="outline" onClick={onRetry}>Try again</Btn></div>
  </div>
)
export const Skeleton = ({ rows = 4 }: { rows?: number }) => (
  <div className="space-y-3 py-2" aria-busy="true" aria-label="Loading">
    {Array.from({ length: rows }).map((_, i) => <div key={i} className="skeleton h-4" style={{ width: `${92 - i * 14}%` }} />)}
  </div>
)
export const Notice = ({ tone = 'info', title, children }: { tone?: 'info' | 'warn' | 'ok' | 'risk'; title?: string; children: ReactNode }) => (
  <div role={tone === 'risk' || tone === 'warn' ? 'note' : undefined} className={cn('border-l-2 py-2.5 pl-4 pr-3 text-sm', tone === 'info' && 'border-skyink/50 bg-sky text-skyink', tone === 'ok' && 'border-ok bg-[#eef5f0] text-ok', tone === 'warn' && 'border-maize bg-maize-soft text-[#6b4208]', tone === 'risk' && 'border-clay bg-clay-soft text-[#5e2611]')}>
    {title && <p className="font-semibold">{title}</p>}
    <div>{children}</div>
  </div>
)

// ---- Forms ----
export function Field({ label, hint, error, children }: { label: string; hint?: string; error?: string; children: (p: { id: string; 'aria-describedby'?: string; 'aria-invalid'?: boolean }) => ReactNode }) {
  const id = useId()
  const d = error ? id + 'e' : hint ? id + 'h' : undefined
  return (
    <div>
      <label htmlFor={id} className="mb-1.5 block text-[13px] font-medium">{label}</label>
      {children({ id, 'aria-describedby': d, 'aria-invalid': !!error })}
      {error ? <p id={id + 'e'} className="mt-1 text-[13px] text-danger">{error}</p> : hint ? <p id={id + 'h'} className="mt-1 text-xs text-muted">{hint}</p> : null}
    </div>
  )
}
export const inputCls = 'w-full h-10 rounded-md border border-line-2 bg-white px-3 text-sm placeholder:text-[#9a9e9f] focus:border-ink focus:outline-none aria-[invalid=true]:border-danger'
export const TextField = ({ label, hint, error, ...p }: { label: string; hint?: string; error?: string } & React.InputHTMLAttributes<HTMLInputElement>) => (
  <Field label={label} hint={hint} error={error}>{(a) => <input {...a} {...p} className={inputCls} />}</Field>
)
export const SelectField = ({ label, options, hint, ...p }: { label: string; options: string[]; hint?: string } & React.SelectHTMLAttributes<HTMLSelectElement>) => (
  <Field label={label} hint={hint}>{(a) => <select {...a} {...p} className={inputCls}>{options.map((o) => <option key={o}>{o}</option>)}</select>}</Field>
)
export const Tabs = ({ tabs, value, onChange }: { tabs: string[]; value: string; onChange: (v: string) => void }) => (
  <div role="tablist" className="flex gap-5 overflow-x-auto border-b border-line">
    {tabs.map((t) => (
      <button key={t} role="tab" aria-selected={value === t} onClick={() => onChange(t)} className={cn('-mb-px h-10 whitespace-nowrap border-b-2 text-sm', value === t ? 'border-ink font-medium text-ink' : 'border-transparent text-muted hover:text-ink')}>{t}</button>
    ))}
  </div>
)

// ---- Responsive data table: table on md+, stacked rows on small screens ----
export type Col<T> = { key: string; label: string; render?: (r: T) => ReactNode; align?: 'right'; text?: (r: T) => string }
export function DataTable<T extends Record<string, any>>({ cols, rows, pageSize = 8, searchable = true, filterKey, filterLabel = 'Filter', empty, actions, caption }: { cols: Col<T>[]; rows: T[]; pageSize?: number; searchable?: boolean; filterKey?: string; filterLabel?: string; empty?: string; actions?: (r: T) => ReactNode; caption: string }) {
  const [q, setQ] = useState('')
  const [f, setF] = useState('All')
  const [page, setPage] = useState(0)
  const opts = useMemo(() => (filterKey ? ['All', ...Array.from(new Set(rows.map((r) => String(r[filterKey]))))] : []), [rows, filterKey])
  const filtered = rows.filter((r) => (f === 'All' || String(r[filterKey!]) === f) && (!q || Object.values(r).join(' ').toLowerCase().includes(q.toLowerCase())))
  const pages = Math.max(1, Math.ceil(filtered.length / pageSize))
  const p = Math.min(page, pages - 1)
  const view = filtered.slice(p * pageSize, p * pageSize + pageSize)
  const cell = (c: Col<T>, r: T) => (c.render ? c.render(r) : String(r[c.key] ?? ''))
  return (
    <div>
      {(searchable || filterKey) && (
        <div className="mb-3 flex flex-wrap gap-2">
          {searchable && (
            <div className="relative min-w-[200px] flex-1 sm:max-w-xs">
              <Icon n="search" size={16} className="absolute left-3 top-3 text-muted" />
              <input aria-label={`Search ${caption}`} placeholder="Search" value={q} onChange={(e) => { setQ(e.target.value); setPage(0) }} className={cn(inputCls, 'pl-9')} />
            </div>
          )}
          {filterKey && (
            <select aria-label={filterLabel} value={f} onChange={(e) => { setF(e.target.value); setPage(0) }} className={cn(inputCls, 'w-auto')}>
              {opts.map((o) => <option key={o} value={o}>{o === 'All' ? `${filterLabel}: all` : o}</option>)}
            </select>
          )}
        </div>
      )}
      {view.length === 0 ? (
        <Empty title="Nothing to show" body={empty || 'No records match your search or filter.'} action={(q || f !== 'All') ? <Btn variant="outline" size="sm" onClick={() => { setQ(''); setF('All') }}>Clear filters</Btn> : undefined} />
      ) : (
        <>
          <div className="hidden overflow-x-auto rounded-lg border border-line bg-white md:block">
            <table className="w-full text-left text-sm">
              <caption className="sr-only">{caption}</caption>
              <thead className="text-[12px] text-muted">
                <tr>{cols.map((c) => <th key={c.key} scope="col" className={cn('px-4 py-2.5 font-medium', c.align === 'right' && 'text-right')}>{c.label}</th>)}{actions && <th scope="col" className="px-4 py-2.5"><span className="sr-only">Actions</span></th>}</tr>
              </thead>
              <tbody>
                {view.map((r, i) => (
                  <tr key={i} className="border-t border-line hover:bg-[#fbfbfa]">
                    {cols.map((c) => <td key={c.key} className={cn('px-4 py-2.5 align-middle', c.align === 'right' && 'text-right tabular-nums')}>{cell(c, r)}</td>)}
                    {actions && <td className="px-4 py-2 text-right">{actions(r)}</td>}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <ul className="divide-y divide-line rounded-lg border border-line bg-white md:hidden">
            {view.map((r, i) => (
              <li key={i} className="p-4">
                <dl className="space-y-1">
                  {cols.map((c) => (
                    <div key={c.key} className="flex justify-between gap-4 text-sm"><dt className="text-muted">{c.label}</dt><dd className="text-right">{cell(c, r)}</dd></div>
                  ))}
                </dl>
                {actions && <div className="mt-3 flex justify-end">{actions(r)}</div>}
              </li>
            ))}
          </ul>
        </>
      )}
      {filtered.length > 0 && (
        <div className="mt-2.5 flex items-center justify-between text-[13px] text-muted">
          <span>{filtered.length} record{filtered.length === 1 ? '' : 's'}</span>
          {pages > 1 && (
            <nav aria-label="Pagination" className="flex items-center gap-2">
              <Btn size="sm" variant="ghost" disabled={p === 0} onClick={() => setPage(p - 1)}>Previous</Btn>
              <span className="tabular-nums">{p + 1} / {pages}</span>
              <Btn size="sm" variant="ghost" disabled={p >= pages - 1} onClick={() => setPage(p + 1)}>Next</Btn>
            </nav>
          )}
        </div>
      )}
    </div>
  )
}

// ---- Charts (lightweight SVG, with text alternatives) ----
const AXIS = '#8a8f91'
export function BarChart({ data, label, fmt = (n: number) => String(n), color = '#1d4a37', h = 150 }: { data: { l: string; v: number; v2?: number }[]; label: string; fmt?: (n: number) => string; color?: string; h?: number }) {
  const max = Math.max(...data.flatMap((d) => [d.v, d.v2 || 0])) || 1
  const W = data.length * 56
  return (
    <figure>
      <svg viewBox={`0 0 ${W} ${h + 24}`} role="img" aria-label={`${label}: ${data.map((d) => `${d.l} ${fmt(d.v)}${d.v2 !== undefined ? ' / ' + fmt(d.v2) : ''}`).join(', ')}`} className="w-full">
        <line x1="0" x2={W} y1={h + 0.5} y2={h + 0.5} stroke="#e4e4e0" />
        {data.map((d, i) => {
          const bh = (d.v / max) * h
          const b2 = ((d.v2 || 0) / max) * h
          return (
            <g key={i} transform={`translate(${i * 56 + 12},0)`}>
              <rect y={h - bh} width={d.v2 !== undefined ? 15 : 32} height={bh} fill={color} />
              {d.v2 !== undefined ? <rect x={17} y={h - b2} width={15} height={b2} fill="#b9c3bd" /> : null}
              <text x={16} y={h + 16} textAnchor="middle" fontSize="11" fill={AXIS}>{d.l}</text>
            </g>
          )
        })}
      </svg>
      <figcaption className="sr-only">{label}</figcaption>
    </figure>
  )
}
export function LineChart({ data, label, fmt = (n: number) => String(n) }: { data: { l: string; v: number }[]; label: string; fmt?: (n: number) => string }) {
  const W = 320, H = 120
  const max = Math.max(...data.map((d) => d.v)) || 1
  const min = Math.min(...data.map((d) => d.v)) * 0.9
  const pts = data.map((d, i) => [(i / (data.length - 1)) * (W - 20) + 10, H - ((d.v - min) / (max - min || 1)) * (H - 20) - 5])
  const last = pts[pts.length - 1]
  return (
    <svg viewBox={`0 0 ${W} ${H + 22}`} role="img" aria-label={`${label}: ${data.map((d) => `${d.l} ${fmt(d.v)}`).join(', ')}`} className="w-full">
      <line x1="0" x2={W} y1={H + 0.5} y2={H + 0.5} stroke="#e4e4e0" />
      <polyline points={pts.map((p) => p.join(',')).join(' ')} fill="none" stroke="#1d4a37" strokeWidth="1.75" strokeLinejoin="round" />
      <circle cx={last[0]} cy={last[1]} r="3" fill="#1d4a37" />
      {data.map((d, i) => <text key={i} x={pts[i][0]} y={H + 16} textAnchor="middle" fontSize="11" fill={AXIS}>{d.l}</text>)}
    </svg>
  )
}
const donutColors = ['#1d4a37', '#5a8572', '#9db5a8', '#cfdbd4', '#8a8f91']
export function Donut({ data, label }: { data: { l: string; v: number }[]; label: string }) {
  const total = data.reduce((a, d) => a + d.v, 0) || 1
  return (
    <div>
      <div className="flex h-2.5 w-full overflow-hidden rounded-sm" role="img" aria-label={`${label}: ${data.map((d) => `${d.l} ${Math.round((d.v / total) * 100)}%`).join(', ')}`}>
        {data.map((d, i) => <div key={i} style={{ width: `${(d.v / total) * 100}%`, background: donutColors[i % 5] }} className="h-full border-r border-white last:border-0" />)}
      </div>
      <ul className="mt-3 space-y-1.5 text-sm">
        {data.map((d, i) => <li key={i} className="flex items-center gap-2"><span className="h-2 w-2 rounded-sm" style={{ background: donutColors[i % 5] }} /><span className="flex-1">{d.l}</span><span className="tabular-nums text-muted">{Math.round((d.v / total) * 100)}%</span></li>)}
      </ul>
    </div>
  )
}

// ---- Dashboard shell ----
export type NavItem = { id: string; label: string; icon: string; badge?: string; group?: string }
export function Shell({ nav, base, page, brand, who, sub, children, onExit, secure }: { nav: NavItem[]; base: string; page: string; brand?: ReactNode; who: string; sub: string; children: ReactNode; onExit: () => void; secure?: boolean }) {
  const [open, setOpen] = useState(false)
  useEffect(() => setOpen(false), [page])
  const list = (
    <nav aria-label="Main" className="flex flex-col px-3">
      {nav.map((n, i) => (
        <div key={n.id}>
          {n.group && <p className={cn('px-2 pb-1 text-[11px] font-medium uppercase tracking-wider text-muted', i > 0 && 'mt-5')}>{n.group}</p>}
          <a href={`#${base}/${n.id}`} aria-current={page === n.id ? 'page' : undefined} className={cn('flex h-8 items-center gap-2 rounded px-2 text-sm', page === n.id ? 'bg-mint font-medium text-ink' : 'text-muted hover:text-ink')}>
            <span className="flex-1 truncate">{n.label}</span>
            {n.badge && <span className="font-mono text-[11px] text-ink">{n.badge}</span>}
          </a>
        </div>
      ))}
    </nav>
  )
  const side = (
    <>
      <div className="flex h-14 items-center gap-2 px-5">{brand ?? <Logo />}</div>
      <div className="flex-1 overflow-y-auto py-3">{list}</div>
      <div className="border-t border-line px-5 py-4 text-sm">
        <p className="font-medium">{who}</p>
        <p className="text-[13px] text-muted">{sub}</p>
        <button onClick={onExit} className="mt-2 text-[13px] text-muted underline hover:text-ink">Sign out</button>
      </div>
    </>
  )
  return (
    <div className={cn('min-h-screen lg:flex', secure && 'border-t-[3px] border-clay')}>
      <aside className="hidden w-60 shrink-0 flex-col border-r border-line bg-white lg:sticky lg:top-0 lg:flex lg:h-screen">{side}</aside>
      <div className="min-w-0 flex-1">
        <header className="sticky top-0 z-30 flex h-12 items-center justify-between border-b border-line bg-white px-4 lg:hidden">
          {brand ?? <Logo size={20} />}
          <button aria-label="Open menu" aria-expanded={open} onClick={() => setOpen(true)} className="-mr-2 flex h-10 w-10 items-center justify-center"><Icon n="menu" /></button>
        </header>
        {open && (
          <div className="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menu">
            <div className="absolute inset-0 bg-ink/40" onClick={() => setOpen(false)} />
            <div className="relative flex h-full w-64 max-w-[85%] flex-col bg-white">
              <button aria-label="Close menu" onClick={() => setOpen(false)} className="absolute right-2 top-2 flex h-10 w-10 items-center justify-center"><Icon n="x" /></button>
              {side}
            </div>
          </div>
        )}
        <main id="main" className="mx-auto max-w-6xl px-4 py-6 sm:px-8 lg:py-10">{children}</main>
      </div>
    </div>
  )
}
export const PageHead = ({ title, sub, action }: { title: string; sub?: string; action?: ReactNode }) => (
  <div className="mb-7 flex flex-wrap items-end justify-between gap-3">
    <div><h1 className="text-[22px]">{title}</h1>{sub && <p className="mt-0.5 max-w-2xl text-sm text-muted">{sub}</p>}</div>
    {action}
  </div>
)

// Short simulated load so the loading state is visible in the prototype
export function Async({ children, rows = 4 }: { children: ReactNode; rows?: number }) {
  const [s, setS] = useState<'load' | 'ok'>('load')
  useEffect(() => { const t = setTimeout(() => setS('ok'), 250); return () => clearTimeout(t) }, [])
  return s === 'load' ? <Skeleton rows={rows} /> : <>{children}</>
}

export function useHash() {
  const [h, setH] = useState(() => location.hash.slice(1) || '/')
  useEffect(() => {
    const f = () => { setH(location.hash.slice(1) || '/'); window.scrollTo(0, 0) }
    window.addEventListener('hashchange', f)
    return () => window.removeEventListener('hashchange', f)
  }, [])
  return h
}
