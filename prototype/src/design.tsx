import { useState } from 'react'
import { Badge, Btn, Card, Confirm, DataTable, Donut, Empty, ErrorState, Logo, Notice, RiskBadge, Skeleton, Status, TextField, toast, BarChart, Icon } from './ui'
import { RISK_INFO, type Risk } from './store'

const swatches: [string, string, string][] = [['Forest', '#0F3D33', 'Primary, trust'], ['Forest 2', '#1B5A4B', 'Hover, charts'], ['Maize', '#E2A72E', 'Growth accent, focus'], ['Paper', '#F6F4EE', 'Background'], ['Ink', '#14211D', 'Text'], ['Clay', '#B4532A', 'Caution, class C'], ['Danger', '#B3261E', 'Errors'], ['Mint', '#E3EFE9', 'Soft success']]

const journeys: [string, string, [string, string][]][] = [
  ['Business: apply, get offers and accept', 'From registration to accepting an investor offer', [['Choose account type', '/auth/register'], ['Register business', '/auth/register/business'], ['Verify phone (code 123456)', '/auth/verify/business'], ['New application', '/business/applications'], ['Compare and accept offers', '/business/offers'], ['Financing management', '/business/financing']]],
  ['Investor: find, offer and track', 'From registration to a validated offer', [['Register investor', '/auth/register/investor'], ['Browse marketplace', '/investor/marketplace'], ['Offers and withdrawals', '/investor/offers'], ['Portfolio', '/investor/portfolio']]],
  ['Credit officer: assess an application', 'Structured workflow with scoring and audit trail', [['Staff gate (password Staff#2025, MFA 482913)', '/internal'], ['Open the queue', '/internal/credit/queue'], ['Assessment workspace', '/internal/credit/assess']]],
  ['Configurable rules: propose, approve, apply', 'IT proposes. Executive approves. Public pages and offer validation update.', [['IT: Configuration', '/internal/it/config'], ['Executive: Approvals', '/internal/exec/approvals'], ['Public: fees and limits', '/products']]],
  ['Finance: client funds vs Banyira revenue', 'Segregated reporting', [['Finance overview', '/internal/finance/overview'], ['Statements and exports', '/internal/finance/statements']]],
  ['Sales, IT and Executive dashboards', 'Remaining role dashboards', [['Sales growth', '/internal/sales/growth'], ['IT system health', '/internal/it/health'], ['Executive overview', '/internal/exec/overview']]],
]

export function Design() {
  const [c, setC] = useState(false)
  const [st, setSt] = useState<'ok' | 'load' | 'err' | 'empty'>('ok')
  return (
    <div className="mx-auto max-w-6xl px-4 py-10">
      <div className="mb-8 flex items-center justify-between"><a href="#/" aria-label="Home"><Logo /></a><Btn variant="outline" href="#/">Public website</Btn></div>
      <h1 className="text-4xl md:text-5xl">Banyira design system and prototypes</h1>
      <p className="mt-3 max-w-2xl text-lg text-muted">One system across the public site, customer dashboards and the internal employee portal. Open any journey below to try the prototype.</p>

      <h2 className="mt-12 text-3xl">Interactive journeys</h2>
      <div className="mt-5 grid gap-4 md:grid-cols-2">{journeys.map(([t, d, links]) => <Card key={t} title={t}><p className="mb-3 text-sm text-muted">{d}</p><ol className="space-y-1.5">{links.map(([l, h], i) => <li key={h}><a href={'#' + h} className="flex min-h-10 items-center gap-3 rounded-lg px-2 hover:bg-mint"><span className="flex h-6 w-6 items-center justify-center rounded-full bg-forest text-xs font-bold text-white">{i + 1}</span><span className="flex-1 font-medium text-forest">{l}</span><Icon n="arrow" size={16} /></a></li>)}</ol></Card>)}</div>
      <p className="mt-4 text-sm text-muted">The staff portal has no link on the public website. It is listed here only for prototype review.</p>

      <h2 className="mt-14 text-3xl">Colour</h2>
      <div className="mt-5 grid grid-cols-2 gap-3 md:grid-cols-4">{swatches.map(([n, h, u]) => <div key={n} className="overflow-hidden rounded-xl border border-line bg-white"><div className="h-16" style={{ background: h }} /><div className="p-3 text-sm"><p className="font-semibold">{n}</p><p className="text-muted">{h}</p><p className="text-xs text-muted">{u}</p></div></div>)}</div>
      <p className="mt-3 text-sm text-muted">Restrained palette: deep forest for trust, maize for growth. Text and controls meet WCAG AA contrast. Colour is never the only signal.</p>

      <h2 className="mt-14 text-3xl">Typography</h2>
      <Card className="mt-5"><p className="font-display text-4xl font-semibold">Fraunces: headlines and figures</p><p className="mt-2 text-lg">Instrument Sans: body, tables and forms. 16px base, 44px minimum touch targets.</p><p className="mt-2 text-sm text-muted">Two font files only, to keep pages light on low-bandwidth connections.</p></Card>

      <h2 className="mt-14 text-3xl">Credit risk classes</h2>
      <div className="mt-5 grid gap-3 md:grid-cols-4">{(Object.keys(RISK_INFO) as Risk[]).map((r) => <Card key={r}><RiskBadge r={r} /><p className="mt-2 font-semibold">{RISK_INFO[r].label}</p><p className="text-sm text-muted">{RISK_INFO[r].text}</p></Card>)}</div>
      <div className="mt-3"><Notice tone="risk">Risk ratings are assessments, not guarantees of repayment.</Notice></div>

      <h2 className="mt-14 text-3xl">Components</h2>
      <div className="mt-5 grid gap-5 lg:grid-cols-2">
        <Card title="Buttons and badges"><div className="flex flex-wrap gap-2"><Btn>Primary</Btn><Btn variant="accent">Accent</Btn><Btn variant="outline">Outline</Btn><Btn variant="ghost">Ghost</Btn><Btn variant="danger">Danger</Btn><Btn disabled>Disabled</Btn></div><div className="mt-4 flex flex-wrap gap-2"><Status s="Approved" /><Status s="Pending review" /><Status s="Rejected" /><Badge tone="blue">Held in trust</Badge></div></Card>
        <Card title="Form states"><div className="space-y-3"><TextField label="Default" placeholder="Enter a value" hint="Helper text sits under the field." /><TextField label="Error" defaultValue="abc" error="Enter a valid amount." /></div></Card>
        <Card title="Feedback"><div className="flex flex-wrap gap-2"><Btn variant="outline" onClick={() => toast('Saved successfully')}>Success toast</Btn><Btn variant="outline" onClick={() => toast('Could not save', 'err')}>Error toast</Btn><Btn variant="outline" onClick={() => setC(true)}>Confirmation dialog</Btn></div><div className="mt-4 space-y-3"><Notice tone="ok" title="Success">Your application was submitted.</Notice><Notice tone="warn" title="Warning">Review before continuing.</Notice></div></Card>
        <Card title="Charts"><BarChart label="Sample chart" data={[{ l: 'A', v: 30 }, { l: 'B', v: 50 }, { l: 'C', v: 40 }]} /><Donut label="Sample" data={[{ l: 'One', v: 50 }, { l: 'Two', v: 30 }, { l: 'Three', v: 20 }]} /></Card>
      </div>
      <h3 className="mt-8 text-xl">Page states</h3>
      <div className="mt-3 flex flex-wrap gap-2">{(['ok', 'load', 'empty', 'err'] as const).map((s) => <Btn key={s} size="sm" variant={st === s ? 'primary' : 'outline'} onClick={() => setSt(s)}>{{ ok: 'Table', load: 'Loading', empty: 'Empty', err: 'Error' }[s]}</Btn>)}</div>
      <div className="mt-4">{st === 'load' ? <Skeleton /> : st === 'empty' ? <Empty title="No applications yet" body="When you start an application it will be listed here." action={<Btn>New application</Btn>} /> : st === 'err' ? <ErrorState onRetry={() => setSt('ok')} /> : <DataTable caption="sample" pageSize={3} filterKey="status" rows={[{ a: 'Item one', b: 'MK100,000', status: 'Active' }, { a: 'Item two', b: 'MK250,000', status: 'Pending' }, { a: 'Item three', b: 'MK75,000', status: 'Rejected' }, { a: 'Item four', b: 'MK310,000', status: 'Active' }]} cols={[{ key: 'a', label: 'Name' }, { key: 'b', label: 'Amount', align: 'right' }, { key: 'status', label: 'Status', render: (r) => <Status s={r.status} /> }]} />}</div>
      <h2 className="mt-14 text-3xl">Responsive and accessibility</h2>
      <ul className="mt-4 max-w-3xl list-disc space-y-1 pl-5 text-muted"><li>Tables become labelled cards on phones; navigation becomes a drawer below 1024px.</li><li>Visible focus rings, skip link, semantic landmarks, labelled form fields with inline errors.</li><li>Charts are SVG with text alternatives. No images or video, so pages stay light.</li><li>Reduced-motion preference is respected.</li></ul>
      <Confirm open={c} onClose={() => setC(false)} title="Confirm this action?" body="Confirmation dialogs explain the consequence before anything irreversible happens." confirm="Confirm" onConfirm={() => toast('Confirmed')} />
    </div>
  )
}
