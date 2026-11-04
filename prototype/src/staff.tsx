import { useState, type ReactNode } from 'react'
import { Async, Badge, BarChart, Btn, Card, Confirm, DataTable, Donut, Empty, Field, Icon, inputCls, LineChart, Logo, Notice, PageHead, Progress, RiskBadge, SelectField, Shell, Stat, Status, Tabs, TextField, toast, cn, type NavItem } from './ui'
import { createStore, feePct, mk, productStore, ruleStore, activeRules, useRules, RISK_INFO, type Risk, type Rules } from './store'

type Role = 'credit' | 'it' | 'sales' | 'finance' | 'exec'
export const staff = createStore<{ role: Role | null }>({ role: null })
const ROLES: Record<Role, { title: string; who: string; email: string; nav: NavItem[] }> = {
  credit: { title: 'Credit Assessment Officer', who: 'Tadala Phiri', email: 'tadala.phiri@banyira.internal', nav: [{ id: 'queue', label: 'Application Queue', icon: 'list', badge: '5' }, { id: 'assess', label: 'Assessment Workspace', icon: 'doc' }, { id: 'requests', label: 'Information Requests', icon: 'mail' }, { id: 'history', label: 'Assessment History', icon: 'clock' }] },
  it: { title: 'IT Administrator', who: 'Kondwani Mwale', email: 'kondwani.mwale@banyira.internal', nav: [{ id: 'health', label: 'System Health', icon: 'chart' }, { id: 'users', label: 'User Management', icon: 'users' }, { id: 'roles', label: 'Roles & Permissions', icon: 'lock' }, { id: 'security', label: 'Security & Logins', icon: 'shield' }, { id: 'audit', label: 'Audit Logs', icon: 'list' }, { id: 'jobs', label: 'Background Jobs', icon: 'clock' }, { id: 'integrations', label: 'Integrations', icon: 'cog' }, { id: 'backups', label: 'Backups', icon: 'down' }, { id: 'config', label: 'Configuration', icon: 'cog' }] },
  sales: { title: 'Sales and Marketing Specialist', who: 'Mercy Chirwa', email: 'mercy.chirwa@banyira.internal', nav: [{ id: 'growth', label: 'Growth Overview', icon: 'chart' }, { id: 'leads', label: 'Leads & Prospects', icon: 'users' }, { id: 'campaigns', label: 'Campaigns', icon: 'mail' }, { id: 'comms', label: 'Customer Communication', icon: 'mail' }, { id: 'followups', label: 'Follow-ups', icon: 'clock' }, { id: 'reports', label: 'Conversion Reports', icon: 'doc' }] },
  finance: { title: 'Finance Officer', who: 'Blessings Nkhoma', email: 'blessings.nkhoma@banyira.internal', nav: [{ id: 'overview', label: 'Finance Overview', icon: 'chart' }, { id: 'fees', label: 'Service Fees', icon: 'wallet' }, { id: 'disbursements', label: 'Disbursements', icon: 'out' }, { id: 'contributions', label: 'Investor Contributions', icon: 'wallet' }, { id: 'reconciliation', label: 'Repayment Reconciliation', icon: 'check' }, { id: 'expenses', label: 'Expenses & Payroll', icon: 'list' }, { id: 'statements', label: 'Statements & Exports', icon: 'doc' }] },
  exec: { title: 'Executive Management', who: 'Dr. Grace Kachale', email: 'grace.kachale@banyira.internal', nav: [{ id: 'overview', label: 'Platform Performance', icon: 'chart' }, { id: 'approvals', label: 'Approvals', icon: 'check', badge: '1' }, { id: 'portfolio', label: 'Repayment & Risk', icon: 'shield' }, { id: 'financials', label: 'Revenue & Profitability', icon: 'wallet' }, { id: 'people', label: 'Employee Performance', icon: 'users' }, { id: 'reports', label: 'Strategic Reports', icon: 'doc' }] },
}
const roleIds = Object.keys(ROLES) as Role[]
const NOTE: Record<Role, string> = {
  credit: 'Access: applications and assessments only. You cannot see finance or system settings.', it: 'Access: system, users and configuration. Rule changes need executive approval. No access to customer funds.', sales: 'Access: leads, campaigns and aggregated onboarding data. No access to credit assessments or financial records.', finance: 'Access: Banyira revenue, expenses, and client-fund records (held separately). No access to credit decisions.', exec: 'Oversight and approval rights. Operational records are viewed read-only; changes are made by the responsible role.',
}

// ---------- Secure gate ----------
export function StaffGate() {
  const [step, setStep] = useState<'cred' | 'mfa'>('cred')
  const [role, setRole] = useState<Role>('credit')
  const [fails, setFails] = useState(0)
  const [err, setErr] = useState('')
  const locked = fails >= 5
  return (
    <div className="flex min-h-screen items-center justify-center bg-ink px-4 py-10">
      <main id="main" className="w-full max-w-md rounded-2xl bg-white p-7">
        <Logo />
        <p className="mt-1 text-xs font-bold uppercase tracking-widest text-clay">Authorised staff only</p>
        <h1 className="mt-3 text-2xl">{step === 'cred' ? 'Staff sign-in' : 'Multi-factor verification'}</h1>
        {locked ? <div className="mt-5"><Notice tone="warn" title="Too many attempts">This account is temporarily locked for 15 minutes and the security team has been alerted.</Notice><div className="mt-3"><Btn variant="outline" onClick={() => setFails(0)}>Demo: reset lock</Btn></div></div> : step === 'cred' ? (
          <form className="mt-5 space-y-4" onSubmit={(e) => { e.preventDefault(); const f = new FormData(e.currentTarget); if (f.get('pw') !== 'Staff#2025') { setFails(fails + 1); setErr(`Invalid credentials. ${4 - fails > 0 ? 4 - fails + ' attempts left.' : ''}`); return } setErr(''); setStep('mfa') }}>
            {err && <Notice tone="warn">{err}</Notice>}
            <Field label="Staff account">{(a) => <select {...a} value={role} onChange={(e) => setRole(e.target.value as Role)} className={inputCls}>{roleIds.map((r) => <option key={r} value={r}>{ROLES[r].email}</option>)}</select>}</Field>
            <TextField label="Password" name="pw" type="password" autoComplete="current-password" />
            <Btn type="submit" full>Continue</Btn>
          </form>
        ) : (
          <form className="mt-5 space-y-4" onSubmit={(e) => { e.preventDefault(); const f = new FormData(e.currentTarget); if (f.get('code') !== '482913') { setFails(fails + 1); setErr('That code is not valid.'); return } staff.set({ role }); location.hash = `/internal/${role}/${ROLES[role].nav[0].id}` }}>
            <p className="text-sm text-muted">Enter the 6-digit code from your authenticator app.</p>
            {err && <Notice tone="warn">{err}</Notice>}
            <TextField label="Authenticator code" name="code" inputMode="numeric" maxLength={6} autoComplete="one-time-code" />
            <Btn type="submit" full>Verify and sign in</Btn>
          </form>
        )}
        <ul className="mt-6 space-y-1 border-t border-line pt-4 text-xs text-muted"><li><Icon n="lock" size={13} className="mr-1 inline" />Sessions expire after 15 minutes of inactivity</li><li><Icon n="shield" size={13} className="mr-1 inline" />Rate limiting, device checks and IP allow-listing enforced server-side</li><li><Icon n="list" size={13} className="mr-1 inline" />All sign-ins and actions are logged</li></ul>
        <p className="mt-4 rounded-lg bg-maize-soft p-3 text-xs text-[#5b3d00]"><strong>Prototype only:</strong> password <code>Staff#2025</code>, MFA code <code>482913</code>. In production this route is protected by network policy and server-side authentication, not by a hidden URL.</p>
      </main>
    </div>
  )
}

export function Staff({ role: r, page }: { role: string; page: string }) {
  const s = staff.use()
  const role = r as Role
  if (!ROLES[role] || s.role !== role) return <StaffGate />
  const cfg = ROLES[role]
  const cur = cfg.nav.find((n) => n.id === page) || cfg.nav[0]
  return (
    <Shell secure nav={cfg.nav} base={`/internal/${role}`} page={cur.id} who={cfg.who} sub={cfg.title} brand={<div><Logo light /><p className="mt-2 inline-block rounded bg-clay px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-white">Internal</p></div>} onExit={() => { staff.set({ role: null }); location.hash = '/internal' }}>
      <PageHead title={cur.label} sub={cfg.title} />
      <div className="mb-5"><Notice tone="info">{NOTE[role]}</Notice></div>
      <Async key={role + cur.id}><Body role={role} id={cur.id} /></Async>
    </Shell>
  )
}

function Body({ role, id }: { role: Role; id: string }) {
  switch (role) { case 'credit': return <Credit id={id} />; case 'it': return <IT id={id} />; case 'sales': return <Sales id={id} />; case 'finance': return <Finance id={id} />; default: return <Exec id={id} /> }
}
const Grid = ({ children, c = 4 }: { children: ReactNode; c?: number }) => <div className={cn('grid grid-cols-2 gap-3', c === 4 && 'lg:grid-cols-4', c === 3 && 'lg:grid-cols-3')}>{children}</div>
const Two = ({ children }: { children: ReactNode }) => <div className="grid gap-5 lg:grid-cols-2">{children}</div>

// ================= CREDIT =================
const apps = [
  { id: 'APP-2058', biz: 'Shire Valley Agro', amt: 3_500_000, months: 12, stage: 'Pending verification', sub: '4 Jun' },
  { id: 'APP-2057', biz: 'Dowa Poultry Farm', amt: 1_200_000, months: 12, stage: 'Document review', sub: '3 Jun' },
  { id: 'APP-2055', biz: 'Ntcheu Building Supplies', amt: 6_800_000, months: 24, stage: 'Financial review', sub: '2 Jun' },
  { id: 'APP-2052', biz: 'Karonga Rice Millers', amt: 2_900_000, months: 18, stage: 'Awaiting information', sub: '30 May' },
  { id: 'APP-2049', biz: 'Chitipa Honey Co-op', amt: 900_000, months: 9, stage: 'Pending verification', sub: '29 May' },
]
const criteria: [string, number][] = [['Cash flow and turnover', 30], ['Repayment history', 25], ['Business stability', 20], ['Assets and security', 15], ['Management quality', 10]]
const classFor = (s: number): Risk | null => (s >= 85 ? 'A+' : s >= 70 ? 'A' : s >= 55 ? 'B' : s >= 40 ? 'C' : null)

function Credit({ id }: { id: string }) {
    if (id === 'queue') return (
    <DataTable caption="submitted applications" filterKey="stage" filterLabel="Stage" rows={apps} cols={[{ key: 'id', label: 'Reference' }, { key: 'biz', label: 'Business' }, { key: 'amt', label: 'Amount', align: 'right', render: (r) => mk(r.amt) }, { key: 'months', label: 'Months' }, { key: 'sub', label: 'Submitted' }, { key: 'stage', label: 'Stage', render: (r) => <Status s={r.stage} /> }]} actions={(r) => <Btn size="sm" onClick={() => { location.hash = '/internal/credit/assess'; sessionStorage.setItem('app', r.id) }}>Open</Btn>} />
  )
  if (id === 'assess') return <Assess app={apps.find((a) => a.id === sessionStorage.getItem('app')) || apps[2]} />
  if (id === 'requests') return <DataTable caption="information requests" filterKey="status" rows={[{ a: 'APP-2052', biz: 'Karonga Rice Millers', ask: 'Latest mill utility bills', sent: '31 May', status: 'Awaiting reply' }, { a: 'APP-2041', biz: 'Mzuzu Fresh Foods', ask: 'Stock list', sent: '2 Jun', status: 'Replied' }, { a: 'APP-2044', biz: 'Dedza Pottery', ask: 'Bank statements, March', sent: '28 May', status: 'Overdue' }]} cols={[{ key: 'a', label: 'Application' }, { key: 'biz', label: 'Business' }, { key: 'ask', label: 'Request' }, { key: 'sent', label: 'Sent' }, { key: 'status', label: 'Status', render: (r) => <Status s={r.status} /> }]} actions={(r) => <Btn size="sm" variant="outline" onClick={() => toast(`Reminder sent to ${r.biz}`)}>Remind</Btn>} />
  return <DataTable caption="assessment history" filterKey="decision" filterLabel="Decision" rows={[{ a: 'APP-2041', biz: 'Mzuzu Fresh Foods', d: '28 May', score: 62, risk: 'B', decision: 'Approved' }, { a: 'APP-2038', biz: 'Blantyre Logistics Co.', d: '20 May', score: 88, risk: 'A+', decision: 'Approved' }, { a: 'APP-2030', biz: 'Zomba Hardware', d: '12 May', score: 47, risk: 'C', decision: 'Approved' }, { a: 'APP-2021', biz: 'Lilongwe Tuck Shop', d: '3 May', score: 33, risk: '', decision: 'Rejected' }]} cols={[{ key: 'a', label: 'Application' }, { key: 'biz', label: 'Business' }, { key: 'd', label: 'Date' }, { key: 'score', label: 'Score', align: 'right' }, { key: 'risk', label: 'Class', render: (r) => (r.risk ? <RiskBadge r={r.risk as Risk} /> : '—') }, { key: 'decision', label: 'Decision', render: (r) => <Status s={r.decision} /> }]} />
}

function Assess({ app }: { app: (typeof apps)[number] }) {
  const rules = useRules()
  const tabs = ['1 Identity', '2 Documents', '3 Financials', '4 Scoring', '5 Recommendation']
  const [tab, setTab] = useState(tabs[0])
  const [ok, setOk] = useState<Record<string, boolean>>({})
  const [sc, setSc] = useState<number[]>([3, 3, 3, 3, 3])
  const [cls, setCls] = useState<Risk | ''>('')
  const [rec, setRec] = useState('Approve')
  const [pub, setPub] = useState(true)
  const [note, setNote] = useState('')
  const [audit, setAudit] = useState<[string, string][]>([['Application assigned to Tadala Phiri', '2 Jun 09:12']])
  const [confirm, setConfirm] = useState(false)
  const [done, setDone] = useState(false)
  const log = (t: string) => setAudit((a) => [[t, 'Just now'], ...a])
  const total = Math.round(criteria.reduce((a, [, w], i) => a + (sc[i] / 5) * w, 0))
  const suggested = classFor(total)
  const final = (cls || suggested) as Risk | null
  const band = final ? rules.bands[final] : null
  const breach = band && ((band.maxAmount && app.amt > band.maxAmount) || (band.maxMonths && app.months > band.maxMonths))
  const check = (k: string, label: string) => <label className="flex min-h-11 items-center gap-3"><input type="checkbox" className="h-5 w-5" checked={!!ok[k]} onChange={(e) => { setOk({ ...ok, [k]: e.target.checked }); log(`${label} ${e.target.checked ? 'confirmed' : 'unconfirmed'}`) }} />{label}</label>
  if (done) return <Notice tone="ok" title="Recommendation submitted">{app.id} was sent to the approver with class {final || 'n/a'}. The decision is recorded in the audit trail.</Notice>
  return (
    <div className="grid gap-5 lg:grid-cols-[1fr_300px]">
      <div className="space-y-4">
        <Card><p className="text-sm text-muted">{app.id}</p><h2 className="text-2xl">{app.biz}</h2><p className="text-sm">Requested {mk(app.amt)} over {app.months} months</p></Card>
        <Tabs tabs={tabs} value={tab} onChange={setTab} />
        {tab === tabs[0] && <Card title="Business identity verification">{check('i1', 'Registration certificate matches registry')}{check('i2', 'Owner national ID checked')}{check('i3', 'Business address confirmed')}</Card>}
        {tab === tabs[1] && <Card title="Document review">{check('d1', 'Registration certificate is valid')}{check('d2', 'Bank / mobile money statements received (6 months)')}{check('d3', 'Supplier quotation or invoice attached')}<div className="mt-3"><Btn variant="outline" onClick={() => { log('Additional information requested'); toast('Request sent to business') }}>Request additional information</Btn></div></Card>}
        {tab === tabs[2] && <Card title="Financial records review"><Grid c={3}><Stat label="Avg monthly inflow" value="MK1.9m" /><Stat label="Avg monthly outflow" value="MK1.4m" /><Stat label="Debt service cover" value="1.6x" /></Grid><div className="mt-3">{check('f1', 'Inflows reconcile with stated turnover')}{check('f2', 'No unexplained large withdrawals')}</div></Card>}
        {tab === tabs[3] && (
          <Card title="Risk scoring form">
            <div className="space-y-4">{criteria.map(([l, w], i) => <div key={l}><label className="flex justify-between text-sm font-semibold" htmlFor={'c' + i}><span>{l} <span className="font-normal text-muted">(weight {w}%)</span></span><span>{sc[i]} / 5</span></label><input id={'c' + i} type="range" min={1} max={5} value={sc[i]} onChange={(e) => { const n = [...sc]; n[i] = +e.target.value; setSc(n) }} onMouseUp={() => log(`Scored ${l}: ${sc[i]}/5`)} className="h-8 w-full accent-[#0f3d33]" /></div>)}</div>
            <div className="mt-4 flex flex-wrap items-center gap-4 rounded-lg bg-mint p-4"><div><p className="text-sm text-muted">Total score</p><p className="font-display text-3xl text-forest">{total}/100</p></div><div><p className="text-sm text-muted">Suggested class</p>{suggested ? <RiskBadge r={suggested} long /> : <Badge tone="red">Below threshold</Badge>}</div></div>
            <div className="mt-4 max-w-xs"><Field label="Final classification (override with comment)">{(a) => <select {...a} value={cls} onChange={(e) => { setCls(e.target.value as Risk); log(`Class set to ${e.target.value || 'suggested'}`) }} className={inputCls}><option value="">Use suggested</option>{(['A+', 'A', 'B', 'C'] as Risk[]).map((r) => <option key={r} value={r}>{r}: {RISK_INFO[r].label}</option>)}</select>}</Field></div>
            {breach && <div className="mt-3"><Notice tone="warn" title="Exceeds class limits">Class {final} is limited to {[band!.maxAmount && mk(band!.maxAmount), band!.maxMonths && band!.maxMonths + ' months'].filter(Boolean).join(' and ')} under rules {rules.version}. The business must reduce its request or be rejected.</Notice></div>}
          </Card>
        )}
        {tab === tabs[4] && (
          <Card title="Recommendation">
            <div className="space-y-4"><SelectField label="Decision recommendation" options={['Approve', 'Reject', 'Request more information']} value={rec} onChange={(e) => setRec(e.target.value)} />
              <label className="flex items-center gap-3"><input type="checkbox" className="h-5 w-5" checked={pub} onChange={(e) => setPub(e.target.checked)} />Recommend publication to marketplace</label>
              <Field label="Assessment comments (required)">{(a) => <textarea {...a} rows={4} value={note} onChange={(e) => setNote(e.target.value)} className={inputCls + ' py-2'} />}</Field>
              <Btn disabled={!note.trim() || (rec === 'Approve' && (!final || !!breach))} onClick={() => setConfirm(true)}>Submit recommendation</Btn>
              {rec === 'Approve' && (!final || breach) && <p className="text-sm text-danger">Approval needs a class and must satisfy class limits.</p>}</div>
          </Card>
        )}
      </div>
      <Card title="Audit trail"><ol className="space-y-3 text-sm">{audit.map(([a, b], i) => <li key={i} className="border-l-2 border-forest pl-3"><p>{a}</p><p className="text-xs text-muted">{b} · T. Phiri</p></li>)}</ol></Card>
      <Confirm open={confirm} onClose={() => setConfirm(false)} title="Submit this recommendation?" body={`${rec}${rec === 'Approve' ? ` with class ${final}${pub ? ' and publish to marketplace' : ''}` : ''}. This will be logged and cannot be edited.`} confirm="Submit" onConfirm={() => { log(`Recommendation submitted: ${rec}`); setDone(true) }} />
    </div>
  )
}

// ================= IT =================
function IT({ id }: { id: string }) {
  const [confirm, setConfirm] = useState<string | null>(null)
  switch (id) {
    case 'health': return <div className="space-y-5"><Grid><Stat label="Uptime (30 days)" value="99.94%" /><Stat label="API response (p95)" value="420 ms" /><Stat label="Failed jobs (24h)" value="1" tone="warn" /><Stat label="Active sessions" value="312" /></Grid><DataTable caption="services" searchable={false} rows={[{ s: 'Web application', st: 'Healthy', n: '12 ms' }, { s: 'Database', st: 'Healthy', n: 'Replica lag 0.2s' }, { s: 'SMS gateway', st: 'Degraded', n: 'Delivery delays' }, { s: 'Mobile money API', st: 'Healthy', n: '' }, { s: 'Document storage', st: 'Healthy', n: '' }]} cols={[{ key: 's', label: 'Service' }, { key: 'st', label: 'Status', render: (r) => <Status s={r.st} /> }, { key: 'n', label: 'Note' }]} /></div>
    case 'users': return <><div className="mb-4"><Btn onClick={() => toast('Invitation sent. The user must set up MFA at first sign-in.')}><Icon n="plus" size={18} />Invite staff user</Btn></div><DataTable caption="staff users" filterKey="role" filterLabel="Role" rows={roleIds.map((r) => ({ name: ROLES[r].who, email: ROLES[r].email, role: ROLES[r].title, mfa: 'Enabled', st: 'Active' })).concat([{ name: 'Former Employee', email: 'old.user@banyira.internal', role: 'Finance Officer', mfa: 'Enabled', st: 'Blocked' }])} cols={[{ key: 'name', label: 'Name' }, { key: 'role', label: 'Role' }, { key: 'mfa', label: 'MFA' }, { key: 'st', label: 'Status', render: (r) => <Status s={r.st} /> }]} actions={(r) => <Btn size="sm" variant="outline" onClick={() => setConfirm(r.name)}>{r.st === 'Active' ? 'Disable' : 'Enable'}</Btn>} /><Confirm open={!!confirm} danger onClose={() => setConfirm(null)} title="Change account access?" body={`This changes sign-in access for ${confirm}. The action is recorded in the audit log.`} confirm="Confirm" onConfirm={() => toast('Access updated')} /></>
    case 'roles': return <Perms />
    case 'security': return <div className="space-y-5"><Notice tone="warn" title="2 alerts need review">Repeated failed staff sign-ins from one IP address; one sign-in from a new country.</Notice><DataTable caption="login activity" filterKey="res" filterLabel="Result" rows={[{ t: '09:41', u: 'tadala.phiri', ip: '196.xx.12.4', res: 'Success' }, { t: '09:12', u: 'unknown', ip: '45.xx.90.1', res: 'Failed' }, { t: '09:11', u: 'unknown', ip: '45.xx.90.1', res: 'Failed' }, { t: '08:58', u: 'grace.kachale', ip: '41.xx.6.77', res: 'Success' }, { t: '08:30', u: 'mercy.chirwa', ip: '196.xx.12.9', res: 'Success' }, { t: '08:02', u: 'blessings.nkhoma', ip: '77.xx.3.5', res: 'Blocked (new country)' }]} cols={[{ key: 't', label: 'Time' }, { key: 'u', label: 'User' }, { key: 'ip', label: 'IP' }, { key: 'res', label: 'Result', render: (r) => <Status s={r.res} /> }]} /></div>
    case 'audit': return <DataTable caption="audit log" filterKey="area" filterLabel="Area" rows={[{ t: '5 Jun 09:20', who: 'Kondwani Mwale', area: 'Configuration', act: 'Proposed rule change' }, { t: '5 Jun 08:10', who: 'Tadala Phiri', area: 'Assessment', act: 'Set class B on APP-2041' }, { t: '4 Jun 16:45', who: 'Grace Kachale', area: 'Approval', act: 'Approved rules R-2025.1' }, { t: '4 Jun 11:02', who: 'Blessings Nkhoma', area: 'Finance', act: 'Exported transactions CSV' }, { t: '3 Jun 14:30', who: 'Kondwani Mwale', area: 'Users', act: 'Disabled old.user' }, { t: '3 Jun 10:00', who: 'Mercy Chirwa', area: 'Campaign', act: 'Launched “Mzuzu SME” campaign' }]} cols={[{ key: 't', label: 'When' }, { key: 'who', label: 'User' }, { key: 'area', label: 'Area' }, { key: 'act', label: 'Action' }]} />
    case 'jobs': return <DataTable caption="background jobs" filterKey="st" filterLabel="Status" rows={[{ j: 'Repayment reminders (SMS)', s: 'Daily 07:00', last: 'Today 07:00', st: 'Running' }, { j: 'Interest accrual', s: 'Daily 00:30', last: 'Today 00:30', st: 'Complete' }, { j: 'Statement generation', s: 'Monthly', last: '1 Jun', st: 'Complete' }, { j: 'Mobile money reconciliation', s: 'Hourly', last: 'Today 09:00', st: 'Failed' }, { j: 'Nightly backup', s: 'Daily 02:00', last: 'Today 02:00', st: 'Complete' }]} cols={[{ key: 'j', label: 'Job' }, { key: 's', label: 'Schedule' }, { key: 'last', label: 'Last run' }, { key: 'st', label: 'Status', render: (r) => <Status s={r.st} /> }]} actions={(r) => r.st === 'Failed' ? <Btn size="sm" onClick={() => toast('Job queued for retry')}>Retry</Btn> : null} />
    case 'integrations': return <DataTable caption="integrations" searchable={false} rows={[{ n: 'Mobile money (Airtel, TNM)', u: 'Payments', st: 'Active' }, { n: 'SMS gateway', u: 'Notifications', st: 'Degraded' }, { n: 'National ID verification', u: 'KYC', st: 'Active' }, { n: 'Bank transfer API', u: 'Disbursement', st: 'Active' }, { n: 'Email service', u: 'Notifications', st: 'Active' }]} cols={[{ key: 'n', label: 'Integration' }, { key: 'u', label: 'Purpose' }, { key: 'st', label: 'Status', render: (r) => <Status s={r.st} /> }]} actions={() => <Btn size="sm" variant="outline" onClick={() => toast('Connection test passed')}>Test</Btn>} />
    case 'backups': return <div className="space-y-4"><Grid c={3}><Stat label="Last backup" value="Today 02:00" /><Stat label="Retention" value="35 days" /><Stat label="Last restore test" value="12 May" /></Grid><Btn onClick={() => toast('Backup started')}>Run backup now</Btn><DataTable caption="backups" searchable={false} rows={[{ d: 'Today 02:00', sz: '1.8 GB', st: 'Complete' }, { d: 'Yesterday 02:00', sz: '1.8 GB', st: 'Complete' }, { d: '3 Jun 02:00', sz: '1.7 GB', st: 'Complete' }]} cols={[{ key: 'd', label: 'Backup' }, { key: 'sz', label: 'Size' }, { key: 'st', label: 'Status', render: (r) => <Status s={r.st} /> }]} /></div>
    default: return <Config />
  }
}

function Perms() {
  const areas = ['Applications', 'Credit decisions', 'Finance records', 'Marketing data', 'System settings', 'Rule changes', 'Rule approval']
  const grid: Record<Role, string[]> = { credit: ['Applications', 'Credit decisions'], it: ['System settings', 'Rule changes'], sales: ['Marketing data'], finance: ['Finance records'], exec: ['Rule approval', 'Applications', 'Finance records', 'Marketing data'] }
  const [g, setG] = useState(grid)
  return <Card title="Permission matrix"><div className="overflow-x-auto"><table className="w-full text-sm"><caption className="sr-only">Role permissions</caption><thead><tr><th className="p-2 text-left">Role</th>{areas.map((a) => <th key={a} className="p-2 text-xs">{a}</th>)}</tr></thead><tbody>{roleIds.map((r) => <tr key={r} className="border-t border-line"><th scope="row" className="p-2 text-left font-medium">{ROLES[r].title}</th>{areas.map((a) => <td key={a} className="p-2 text-center"><input aria-label={`${ROLES[r].title}: ${a}`} type="checkbox" className="h-5 w-5" checked={g[r].includes(a)} onChange={() => setG({ ...g, [r]: g[r].includes(a) ? g[r].filter((x) => x !== a) : [...g[r], a] })} /></td>)}</tr>)}</tbody></table></div><div className="mt-4"><Btn onClick={() => toast('Permission changes sent for approval')}>Save changes</Btn></div><p className="mt-2 text-xs text-muted">Executives approve; they do not hold direct edit rights over operations. Permission changes are logged.</p></Card>
}

function Config() {
  const history = ruleStore.use()
  const rules = activeRules(history)
  const products = productStore.use()
  const pending = history.find((h) => h.status === 'Pending approval')
  const [tab, setTab] = useState('Financing rules')
  const [f, setF] = useState({ ceiling: String(rules.ceiling), t1: String(rules.feeTiers[0].pct), t2: String(rules.feeTiers[1].pct), t3: String(rules.feeTiers[2].pct), cAmt: String(rules.bands.C.maxAmount), cMo: String(rules.bands.C.maxMonths), bMo: String(rules.bands.B.maxMonths), date: '2025-07-01' })
  const set = (k: string) => (e: React.ChangeEvent<HTMLInputElement>) => setF({ ...f, [k]: e.target.value })
  const propose = () => {
    const n: Rules = { ...rules, version: `R-2025.${history.length + 1}`, effective: f.date, ceiling: +f.ceiling, feeTiers: [{ upTo: rules.feeTiers[0].upTo, pct: +f.t1 }, { upTo: rules.feeTiers[1].upTo, pct: +f.t2 }, { upTo: null, pct: +f.t3 }], bands: { ...rules.bands, C: { maxAmount: +f.cAmt, maxMonths: +f.cMo }, B: { maxAmount: null, maxMonths: +f.bMo } } }
    ruleStore.set((h) => [{ rules: n, status: 'Pending approval', by: 'IT Administrator', note: 'Proposed by IT Administrator' }, ...h]); toast('Proposal sent to Executive Management for approval')
  }
  return (
    <div className="space-y-5">
      <Tabs tabs={['Financing rules', 'Products', 'Rule history']} value={tab} onChange={setTab} />
      {tab === 'Financing rules' && <>
        <Notice tone="info">Active: <strong>{rules.version}</strong>, effective {rules.effective}. Changes do not apply until approved by Executive Management and the effective date arrives.</Notice>
        {pending && <Notice tone="warn" title="A proposal is awaiting approval">Version {pending.rules.version}, effective {pending.rules.effective}. New proposals are blocked until it is decided.</Notice>}
        <form className="grid gap-4 rounded-xl border border-line bg-white p-5 sm:grid-cols-2 lg:grid-cols-3" onSubmit={(e) => { e.preventDefault(); propose() }}>
          <TextField label={`Service fee below ${mk(rules.feeTiers[0].upTo!)} (%)`} value={f.t1} onChange={set('t1')} inputMode="decimal" />
          <TextField label={`Service fee below ${mk(rules.feeTiers[1].upTo!)} (%)`} value={f.t2} onChange={set('t2')} inputMode="decimal" />
          <TextField label="Service fee above (%)" value={f.t3} onChange={set('t3')} inputMode="decimal" />
          <TextField label="Investor interest ceiling (% p.a.)" value={f.ceiling} onChange={set('ceiling')} inputMode="decimal" />
          <TextField label="Class C maximum amount (MK)" value={f.cAmt} onChange={set('cAmt')} inputMode="numeric" />
          <TextField label="Class C maximum months" value={f.cMo} onChange={set('cMo')} inputMode="numeric" />
          <TextField label="Class B maximum months" value={f.bMo} onChange={set('bMo')} inputMode="numeric" />
          <TextField label="Effective date" type="date" value={f.date} onChange={set('date')} />
          <div className="flex items-end"><Btn type="submit" disabled={!!pending}>Submit for approval</Btn></div>
          <p className="text-xs text-muted sm:col-span-2 lg:col-span-3">Class A+ and A: no additional limits currently defined.</p>
        </form></>}
      {tab === 'Products' && <div className="space-y-3"><p className="text-sm text-muted">Toggle visibility on the public website and application form. Changes appear on the Our Products page immediately.</p>{products.map((p) => <div key={p.slug} className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-white p-4"><div><p className="font-semibold">{p.name}</p><p className="text-sm text-muted">{p.range} · {p.term}</p></div><label className="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" role="switch" className="h-6 w-6" checked={p.active} onChange={() => { productStore.set((all) => all.map((x) => (x.slug === p.slug ? { ...x, active: !x.active } : x))); toast(`${p.name} ${p.active ? 'hidden' : 'published'}`) }} />{p.active ? 'Published' : 'Hidden'}</label></div>)}</div>}
      {tab === 'Rule history' && <RuleHistory />}
    </div>
  )
}
function RuleHistory() {
  const h = ruleStore.use()
  return <DataTable caption="rule history" filterKey="status" pageSize={5} rows={h.map((x) => ({ v: x.rules.version, eff: x.rules.effective, ceil: x.rules.ceiling + '%', fees: x.rules.feeTiers.map((t) => t.pct + '%').join(' / '), c: `${mk(x.rules.bands.C.maxAmount || 0)}, ${x.rules.bands.C.maxMonths}mo`, b: x.rules.bands.B.maxMonths + ' mo', by: x.by, status: x.status }))} cols={[{ key: 'v', label: 'Version' }, { key: 'eff', label: 'Effective' }, { key: 'fees', label: 'Fees' }, { key: 'ceil', label: 'Ceiling' }, { key: 'c', label: 'Class C' }, { key: 'b', label: 'Class B' }, { key: 'status', label: 'Status', render: (r) => <Status s={r.status} /> }]} />
}

// ================= SALES =================
function Sales({ id }: { id: string }) {
  const [leads, setLeads] = useState([{ n: 'Nkhata Bay Cold Storage', t: 'Business', src: 'Referral', st: 'Contacted', next: '8 Jun' }, { n: 'Peter Mhone', t: 'Investor', src: 'Facebook ad', st: 'New', next: '6 Jun' }, { n: 'Mulanje Tea Packers', t: 'Business', src: 'Radio', st: 'Qualified', next: '9 Jun' }, { n: 'Agnes Kamanga', t: 'Investor', src: 'Event', st: 'Onboarding', next: '7 Jun' }, { n: 'Lunzu Bakery', t: 'Business', src: 'WhatsApp', st: 'Lost', next: '—' }])
  const [name, setName] = useState('')
  const funnel = [{ l: 'Leads', v: 120 }, { l: 'Contacted', v: 84 }, { l: 'Registered', v: 51 }, { l: 'Verified', v: 33 }, { l: 'Active', v: 21 }]
  if (id === 'growth') return <div className="space-y-5"><Grid><Stat label="New business leads (month)" value="38" /><Stat label="New investor leads (month)" value="27" /><Stat label="Registrations" value="51" /><Stat label="Lead to active" value="17.5%" note="Illustrative sample data" /></Grid><Two><Card title="Acquisition funnel"><BarChart label="Acquisition funnel" data={funnel} /></Card><Card title="Marketplace growth: registered users"><LineChart label="Registered users" data={[{ l: 'Jan', v: 80 }, { l: 'Feb', v: 110 }, { l: 'Mar', v: 150 }, { l: 'Apr', v: 190 }, { l: 'May', v: 245 }]} /></Card></Two></div>
  if (id === 'leads') return <div className="space-y-4"><form className="flex flex-wrap gap-2" onSubmit={(e) => { e.preventDefault(); if (!name.trim()) return toast('Enter a name first', 'err'); setLeads([{ n: name, t: 'Business', src: 'Manual', st: 'New', next: '10 Jun' }, ...leads]); setName(''); toast('Lead added') }}><input aria-label="New lead name" placeholder="Add a prospect name" value={name} onChange={(e) => setName(e.target.value)} className={cn(inputCls, 'max-w-xs')} /><Btn type="submit"><Icon n="plus" size={18} />Add lead</Btn></form><DataTable caption="leads" filterKey="st" filterLabel="Stage" rows={leads} cols={[{ key: 'n', label: 'Name' }, { key: 't', label: 'Type' }, { key: 'src', label: 'Source' }, { key: 'st', label: 'Stage', render: (r) => <Status s={r.st} /> }, { key: 'next', label: 'Next follow-up' }]} actions={(r) => <Btn size="sm" variant="outline" onClick={() => { setLeads(leads.map((l) => (l === r ? { ...l, st: 'Contacted' } : l))); toast('Marked as contacted') }}>Log contact</Btn>} /></div>
  if (id === 'campaigns') return <DataTable caption="campaigns" filterKey="st" rows={[{ n: 'Mzuzu SME outreach', ch: 'SMS + radio', aud: 'Businesses', reach: 2400, reg: 31, st: 'Running' }, { n: 'First-time investors', ch: 'Facebook', aud: 'Investors', reach: 5100, reg: 22, st: 'Running' }, { n: 'Agro season stock', ch: 'WhatsApp', aud: 'Businesses', reach: 900, reg: 9, st: 'Scheduled' }, { n: 'Launch week', ch: 'Events', aud: 'Both', reach: 700, reg: 40, st: 'Complete' }]} cols={[{ key: 'n', label: 'Campaign' }, { key: 'ch', label: 'Channel' }, { key: 'aud', label: 'Audience' }, { key: 'reach', label: 'Reach', align: 'right' }, { key: 'reg', label: 'Sign-ups', align: 'right' }, { key: 'st', label: 'Status', render: (r) => <Status s={r.st} /> }]} actions={() => <Btn size="sm" variant="outline" onClick={() => toast('Campaign opened')}>Manage</Btn>} />
  if (id === 'comms') return <Comms />
  if (id === 'followups') return <DataTable caption="follow-ups" filterKey="st" rows={[{ d: '6 Jun', who: 'Peter Mhone', a: 'Call to explain investor verification', st: 'Due' }, { d: '7 Jun', who: 'Agnes Kamanga', a: 'Help upload ID', st: 'Scheduled' }, { d: '8 Jun', who: 'Nkhata Bay Cold Storage', a: 'Visit and document checklist', st: 'Scheduled' }, { d: '3 Jun', who: 'Lunzu Bakery', a: 'Re-engage', st: 'Overdue' }]} cols={[{ key: 'd', label: 'Date' }, { key: 'who', label: 'Contact' }, { key: 'a', label: 'Activity' }, { key: 'st', label: 'Status', render: (r) => <Status s={r.st} /> }]} actions={() => <Btn size="sm" variant="outline" onClick={() => toast('Marked complete')}>Done</Btn>} />
  return <div className="space-y-5"><Grid c={3}><Stat label="Visit to registration" value="4.3%" note="Sample" /><Stat label="Registration to verified" value="64.7%" /><Stat label="Verified to active" value="63.6%" /></Grid><Two><Card title="Conversion by channel"><BarChart label="Sign-ups by channel" data={[{ l: 'Radio', v: 18 }, { l: 'Social', v: 22 }, { l: 'Referral', v: 31 }, { l: 'Events', v: 40 }]} /></Card><Card title="Business vs investor onboarding"><Donut label="Onboarding split" data={[{ l: 'Businesses', v: 58 }, { l: 'Investors', v: 42 }]} /></Card></Two><Btn variant="outline" onClick={() => toast('Report exported')}><Icon n="down" size={18} />Export report</Btn></div>
}
function Comms() {
  const [aud, setAud] = useState('All new businesses'); const [msg, setMsg] = useState(''); const [c, setC] = useState(false)
  return <div className="max-w-xl space-y-4"><SelectField label="Audience" options={['All new businesses', 'All new investors', 'Leads not yet registered']} value={aud} onChange={(e) => setAud(e.target.value)} /><Field label="Message" hint="Do not promise approval, funding or returns.">{(a) => <textarea {...a} rows={4} value={msg} onChange={(e) => setMsg(e.target.value)} className={inputCls + ' py-2'} />}</Field><Btn disabled={!msg.trim()} onClick={() => setC(true)}>Send message</Btn><Confirm open={c} onClose={() => setC(false)} title="Send to this audience?" body={`Your message will go to: ${aud}.`} confirm="Send" onConfirm={() => { toast('Message queued for sending'); setMsg('') }} /></div>
}

// ================= FINANCE =================
function Finance({ id }: { id: string }) {
  const rules = useRules()
  const Split = () => <Notice tone="warn" title="Banyira funds and client funds are separate">Service fees and Banyira expenses below are Banyira’s own accounts. Investor capital, borrower repayments in transit and funds held on behalf of others are shown separately and are never counted as revenue.</Notice>
  const exp = (n: string) => toast(`${n} exported (CSV)`)
  switch (id) {
    case 'overview': return <div className="space-y-5"><Split /><div className="grid gap-5 lg:grid-cols-2"><Card title="Banyira own accounts (May 2025)"><Grid c={3}><Stat label="Service-fee revenue" value="MK3,420,000" /><Stat label="Operating expenses" value="MK2,610,000" /><Stat label="Net operating result" value="MK810,000" /></Grid></Card><Card title="Funds held for others (not Banyira money)"><Grid c={3}><Stat label="Investor capital awaiting disbursement" value="MK6,200,000" /><Stat label="Borrower repayments awaiting payout" value="MK1,940,000" /><Stat label="Outstanding borrower obligations" value="MK48,300,000" /></Grid></Card></div><Card title="Revenue vs expenses, Banyira only (MK thousands)"><BarChart label="Revenue vs expenses" data={[{ l: 'Jan', v: 2200, v2: 2300 }, { l: 'Feb', v: 2600, v2: 2400 }, { l: 'Mar', v: 2900, v2: 2500 }, { l: 'Apr', v: 3100, v2: 2550 }, { l: 'May', v: 3420, v2: 2610 }]} /><p className="mt-2 text-xs text-muted">Green: revenue. Gold: expenses. Sample data.</p></Card></div>
    case 'fees': return <div className="space-y-4"><Notice tone="info">Fee bands in use (rules {rules.version}): {rules.feeTiers.map((t, i) => `${t.upTo ? 'below ' + mk(t.upTo) : 'above'} ${t.pct}%`).join(', ')}.</Notice><DataTable caption="service fees" filterKey="st" rows={[{ id: 'AG-0071', biz: 'Mzuzu Fresh Foods', amt: 700_000, st: 'Collected' }, { id: 'AG-0080', biz: 'Blantyre Logistics', amt: 12_000_000, st: 'Collected' }, { id: 'AG-0083', biz: 'Kasungu Tailoring', amt: 1_800_000, st: 'Pending' }, { id: 'AG-0085', biz: 'Salima Lodge', amt: 5_000_000, st: 'Pending' }].map((r) => ({ ...r, pct: feePct(rules, r.amt) + '%', fee: mk((r.amt * feePct(rules, r.amt)) / 100) }))} cols={[{ key: 'id', label: 'Agreement' }, { key: 'biz', label: 'Business' }, { key: 'amt', label: 'Financing', align: 'right', render: (r) => mk(r.amt) }, { key: 'pct', label: 'Fee %' }, { key: 'fee', label: 'Fee (revenue)', align: 'right' }, { key: 'st', label: 'Status', render: (r) => <Status s={r.st} /> }]} /></div>
    case 'disbursements': return <DataTable caption="disbursements" filterKey="st" rows={[{ id: 'DSB-210', biz: 'Blantyre Logistics', amt: 9_000_000, d: '2 Jun', st: 'Complete' }, { id: 'DSB-211', biz: 'Kasungu Tailoring', amt: 1_800_000, d: '4 Jun', st: 'Pending' }, { id: 'DSB-209', biz: 'Lilongwe Print House', amt: 5_400_000, d: '28 May', st: 'Complete' }, { id: 'DSB-212', biz: 'Mzuzu Fresh Foods', amt: 700_000, d: '5 Jun', st: 'Awaiting approval' }]} cols={[{ key: 'id', label: 'Reference' }, { key: 'biz', label: 'Borrower' }, { key: 'amt', label: 'Amount (investor funds)', align: 'right', render: (r) => mk(r.amt) }, { key: 'd', label: 'Date' }, { key: 'st', label: 'Status', render: (r) => <Status s={r.st} /> }]} actions={(r) => r.st === 'Awaiting approval' ? <Btn size="sm" onClick={() => toast('Disbursement released')}>Release</Btn> : null} />
    case 'contributions': return <DataTable caption="investor contributions" rows={[{ i: 'Investor 4821', t: 'Mzuzu Fresh Foods', a: 1_000_000, d: '2 Jun', st: 'Held in trust' }, { i: 'Investor 1180', t: 'Mzuzu Fresh Foods', a: 700_000, d: '29 May', st: 'Disbursed' }, { i: 'Investor 9034', t: 'Blantyre Logistics', a: 3_000_000, d: '26 May', st: 'Disbursed' }]} cols={[{ key: 'i', label: 'Investor' }, { key: 't', label: 'Financing' }, { key: 'a', label: 'Amount', align: 'right', render: (r) => mk(r.a) }, { key: 'd', label: 'Received' }, { key: 'st', label: 'Status', render: (r) => <Badge tone="blue">{r.st}</Badge> }]} />
    case 'reconciliation': return <div className="space-y-4"><Grid c={3}><Stat label="Matched today" value="46" /><Stat label="Unmatched" value="3" tone="warn" /><Stat label="Difference" value="MK38,000" tone="warn" /></Grid><DataTable caption="reconciliation" filterKey="st" rows={[{ ref: 'MM-77120', from: 'Zomba Hardware', a: 120_000, st: 'Reconciled' }, { ref: 'MM-77131', from: 'Unknown sender', a: 38_000, st: 'Unmatched' }, { ref: 'BK-4410', from: 'Blantyre Logistics', a: 560_000, st: 'Reconciled' }, { ref: 'MM-77140', from: 'Dedza Pottery', a: 45_000, st: 'Unmatched' }]} cols={[{ key: 'ref', label: 'Reference' }, { key: 'from', label: 'Received from' }, { key: 'a', label: 'Amount', align: 'right', render: (r) => mk(r.a) }, { key: 'st', label: 'Status', render: (r) => <Status s={r.st} /> }]} actions={(r) => r.st === 'Unmatched' ? <Btn size="sm" onClick={() => toast('Payment matched to agreement')}>Match</Btn> : null} /></div>
    case 'expenses': return <div className="space-y-5"><Grid c={3}><Stat label="Payroll (May)" value="MK1,450,000" /><Stat label="Operational expenditure" value="MK1,160,000" /><Stat label="Total Banyira expenses" value="MK2,610,000" /></Grid><DataTable caption="expenses" filterKey="cat" filterLabel="Category" rows={[{ d: '30 May', cat: 'Payroll', desc: 'May salaries (7 staff)', a: 1_450_000 }, { d: '28 May', cat: 'Operations', desc: 'Office rent', a: 400_000 }, { d: '25 May', cat: 'Technology', desc: 'Hosting and SMS', a: 310_000 }, { d: '20 May', cat: 'Marketing', desc: 'Radio campaign', a: 280_000 }, { d: '12 May', cat: 'Operations', desc: 'Utilities and transport', a: 170_000 }]} cols={[{ key: 'd', label: 'Date' }, { key: 'cat', label: 'Category' }, { key: 'desc', label: 'Description' }, { key: 'a', label: 'Amount', align: 'right', render: (r) => mk(r.a) }]} /></div>
    default: return <div className="grid gap-4 md:grid-cols-2">{[['Statement of Banyira income and expenses', 'Own revenue and costs only'], ['Client funds statement', 'Investor capital and borrower repayments held or transferred on behalf of others'], ['Revenue report by fee band', 'Service fees'], ['Transaction export (all)', 'CSV with account type column']].map(([t, d]) => <Card key={t} title={t}><p className="text-sm text-muted">{d}</p><div className="mt-3 flex gap-2"><Btn variant="outline" onClick={() => exp(t)}><Icon n="down" size={16} />CSV</Btn><Btn variant="outline" onClick={() => exp(t)}>PDF</Btn></div></Card>)}</div>
  }
}

// ================= EXEC =================
function Exec({ id }: { id: string }) {
  const history = ruleStore.use()
  const pending = history.find((h) => h.status === 'Pending approval')
  const [c, setC] = useState<'Active' | 'Rejected' | null>(null)
  switch (id) {
    case 'overview': return <div className="space-y-5"><Grid><Stat label="Businesses onboarded" value="142" /><Stat label="Applications received" value="318" /><Stat label="Approval rate" value="41%" note="Of assessed applications" /><Stat label="Active financing" value="MK48.3m" /><Stat label="Total marketplace funding" value="MK96.1m" /><Stat label="Investors participating" value="389" /><Stat label="Revenue (May)" value="MK3.42m" /><Stat label="Profit (May)" value="MK0.81m" /></Grid><Notice tone="info">Illustrative sample data for the prototype.</Notice><Two><Card title="Applications received per month"><BarChart label="Applications per month" data={[{ l: 'Jan', v: 40 }, { l: 'Feb', v: 52 }, { l: 'Mar', v: 61 }, { l: 'Apr', v: 70 }, { l: 'May', v: 95 }]} /></Card><Card title="Funding by risk class"><Donut label="Funding by class" data={[{ l: 'A+', v: 22 }, { l: 'A', v: 38 }, { l: 'B', v: 30 }, { l: 'C', v: 10 }]} /></Card></Two></div>
    case 'approvals': return <div className="space-y-4">{pending ? <Card title={`Pending: financing rules ${pending.rules.version}`} action={<Badge tone="amber">Pending approval</Badge>}><dl className="grid gap-2 text-sm sm:grid-cols-2"><div><dt className="text-muted">Proposed by</dt><dd>{pending.by}</dd></div><div><dt className="text-muted">Effective date</dt><dd>{pending.rules.effective}</dd></div><div><dt className="text-muted">Fee bands</dt><dd>{pending.rules.feeTiers.map((t) => t.pct + '%').join(' / ')}</dd></div><div><dt className="text-muted">Investor ceiling</dt><dd>{pending.rules.ceiling}% p.a.</dd></div><div><dt className="text-muted">Class C</dt><dd>{mk(pending.rules.bands.C.maxAmount || 0)}, {pending.rules.bands.C.maxMonths} months</dd></div><div><dt className="text-muted">Class B</dt><dd>{pending.rules.bands.B.maxMonths} months</dd></div></dl><div className="mt-4 flex gap-2"><Btn onClick={() => setC('Active')}>Approve</Btn><Btn variant="outline" onClick={() => setC('Rejected')}>Reject</Btn></div></Card> : <Empty title="No pending approvals" body="Rule changes proposed by the IT Administrator will appear here for your decision." />}
      <Card title="Other approvals"><DataTable caption="approvals" searchable={false} rows={[{ i: 'Disbursement over MK10m: Blantyre Logistics', st: 'Approved' }, { i: 'Q2 marketing budget', st: 'Approved' }]} cols={[{ key: 'i', label: 'Item' }, { key: 'st', label: 'Status', render: (r) => <Status s={r.st} /> }]} /></Card>
      <h2 className="text-xl">Rule history</h2><RuleHistory />
      <Confirm open={!!c} danger={c === 'Rejected'} onClose={() => setC(null)} title={c === 'Active' ? 'Approve rule change?' : 'Reject rule change?'} body={c === 'Active' ? 'Approved rules replace the current rules from their effective date. The previous version is kept in history. This is applied immediately in this prototype.' : 'The proposal will be recorded as rejected.'} confirm={c === 'Active' ? 'Approve' : 'Reject'} onConfirm={() => { ruleStore.set((h) => h.map((x) => (x.status === 'Pending approval' ? { ...x, status: c!, approvedBy: 'Executive Management' } : c === 'Active' && x.status === 'Active' ? { ...x, status: 'Superseded' } : x))); toast(c === 'Active' ? 'Rules approved and now active' : 'Proposal rejected') }} />
    </div>
    case 'portfolio': return <div className="space-y-5"><Grid><Stat label="On-time repayment rate" value="94.2%" /><Stat label="Late 1-30 days" value="4.1%" tone="warn" /><Stat label="Late over 30 days" value="1.2%" tone="warn" /><Stat label="Defaults (written off)" value="0.5%" tone="warn" /></Grid><Two><Card title="Delinquency trend (%)"><LineChart label="Delinquency trend" data={[{ l: 'Jan', v: 3.1 }, { l: 'Feb', v: 3.8 }, { l: 'Mar', v: 4.2 }, { l: 'Apr', v: 5.0 }, { l: 'May', v: 5.3 }]} /></Card><Card title="Late share by risk class"><BarChart label="Late share by class" data={[{ l: 'A+', v: 1 }, { l: 'A', v: 2 }, { l: 'B', v: 6 }, { l: 'C', v: 11 }]} /></Card></Two></div>
    case 'financials': return <div className="space-y-5"><Notice tone="warn">Revenue and profit refer to Banyira’s own accounts only. Investor capital and borrower obligations are excluded.</Notice><Grid c={3}><Stat label="Revenue (YTD)" value="MK14.2m" /><Stat label="Expenses (YTD)" value="MK12.4m" /><Stat label="Profit (YTD)" value="MK1.8m" /></Grid><Card title="Revenue vs expenses (MK thousands)"><BarChart label="Revenue vs expenses" data={[{ l: 'Jan', v: 2200, v2: 2300 }, { l: 'Feb', v: 2600, v2: 2400 }, { l: 'Mar', v: 2900, v2: 2500 }, { l: 'Apr', v: 3100, v2: 2550 }, { l: 'May', v: 3420, v2: 2610 }]} /></Card></div>
    case 'people': return <DataTable caption="employee performance" rows={[{ n: 'Tadala Phiri', r: 'Credit Officer', m: 'Assessments completed', v: '42', t: '40', st: 'On track' }, { n: 'Mercy Chirwa', r: 'Sales & Marketing', m: 'Verified sign-ups', v: '33', t: '40', st: 'Monitor' }, { n: 'Blessings Nkhoma', r: 'Finance', m: 'Reconciled on day', v: '97%', t: '95%', st: 'On track' }, { n: 'Kondwani Mwale', r: 'IT', m: 'Uptime', v: '99.94%', t: '99.9%', st: 'On track' }]} cols={[{ key: 'n', label: 'Employee' }, { key: 'r', label: 'Role' }, { key: 'm', label: 'Measure' }, { key: 'v', label: 'Actual', align: 'right' }, { key: 't', label: 'Target', align: 'right' }, { key: 'st', label: 'Status', render: (r) => <Status s={r.st} /> }]} />
    default: return <div className="grid gap-4 md:grid-cols-2">{['Quarterly board pack', 'Portfolio quality report', 'Growth and acquisition review', 'Annual strategy review'].map((t) => <Card key={t} title={t}><p className="text-sm text-muted">Prepared from approved data sources.</p><div className="mt-3"><Btn variant="outline" onClick={() => toast(`${t} downloaded`)}><Icon n="down" size={16} />Download</Btn></div></Card>)}</div>
  }
}
