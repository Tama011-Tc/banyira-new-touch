import { useMemo, useState } from 'react'
import { Async, Badge, Btn, Card, Confirm, DataTable, Donut, BarChart, LineChart, Empty, ErrorState, Field, Icon, inputCls, Modal, Notice, PageHead, Progress, RiskBadge, SelectField, Shell, Stat, Status, Tabs, TextField, toast, cn, type NavItem } from './ui'
import { feePct, mk, offerStore, opps, useRules, type Opp, type Risk } from './store'
import { session } from './auth'

const exit = () => { session.set({ type: null }); location.hash = '/' }
export const fundedOf = (o: Opp, offers: { oppId: string; amount: number; status: string }[]) =>
  o.id === 'o1' ? offers.filter((f) => f.oppId === 'o1' && f.status === 'Accepted').reduce((a, f) => a + f.amount, 0) : o.funded
export const monthly = (amt: number, rate: number, m: number) => { const r = rate / 1200; return r ? (amt * r) / (1 - Math.pow(1 + r, -m)) : amt / m }

// ---------- shared pages ----------
function Chat({ who, docs }: { who: string[]; docs?: string }) {
  const [sel, setSel] = useState(0)
  const [msgs, setMsgs] = useState<Record<number, { me: boolean; t: string }[]>>({ 0: [{ me: false, t: 'Hello, thank you for the offer. Could you share your latest stock list?' }, { me: true, t: 'Of course. I will upload it to Documents today.' }], 1: [{ me: false, t: 'Your agreement is ready for review.' }], 2: [] })
  const [txt, setTxt] = useState('')
  const send = (t: string) => { if (!t.trim()) return; setMsgs({ ...msgs, [sel]: [...(msgs[sel] || []), { me: true, t }] }); setTxt('') }
  return (
    <div className="grid overflow-hidden rounded-xl border border-line bg-white md:grid-cols-[240px_1fr]">
      <ul className="border-b border-line md:border-b-0 md:border-r" aria-label="Conversations">
        {who.map((w, i) => <li key={w}><button onClick={() => setSel(i)} aria-current={sel === i} className={cn('flex min-h-14 w-full items-center gap-3 px-4 text-left', sel === i && 'bg-mint')}><span className="flex h-9 w-9 items-center justify-center rounded-full bg-forest text-sm font-bold text-white">{w[0]}</span><span className="text-sm font-semibold">{w}</span></button></li>)}
      </ul>
      <div className="flex min-h-[340px] flex-col">
        <div className="border-b border-line bg-[#f7f5ee] px-4 py-2 text-xs text-muted"><Icon n="lock" size={14} className="mr-1 inline" />Secure in-platform messaging. Never share passwords or PINs.</div>
        <div className="flex-1 space-y-2 p-4" aria-live="polite">
          {(msgs[sel] || []).length === 0 ? <Empty title="No messages yet" body="Start the conversation below." /> : msgs[sel].map((m, i) => <div key={i} className={cn('max-w-[80%] rounded-xl px-3 py-2 text-sm', m.me ? 'ml-auto bg-forest text-white' : 'bg-[#ebe8dd]')}>{m.t}</div>)}
        </div>
        <form className="flex gap-2 border-t border-line p-3" onSubmit={(e) => { e.preventDefault(); send(txt) }}>
          <input aria-label="Message" value={txt} onChange={(e) => setTxt(e.target.value)} placeholder="Write a message" className={inputCls} />
          <Btn variant="outline" label="Share a document" onClick={() => { send(`Shared document: ${docs || 'Document.pdf'}`); toast('Document shared securely') }}><Icon n="doc" size={18} /></Btn>
          <Btn type="submit">Send</Btn>
        </form>
      </div>
    </div>
  )
}
const Notifs = ({ items }: { items: [string, string, string][] }) => {
  const [read, setRead] = useState<number[]>([])
  return <div className="space-y-2">{items.map(([t, d, w], i) => <div key={i} className={cn('flex items-start gap-3 rounded-xl border border-line bg-white p-4', !read.includes(i) && 'border-l-4 border-l-maize')}><Icon n="bell" className="mt-0.5 text-forest" /><div className="flex-1"><p className="font-semibold">{t}</p><p className="text-sm text-muted">{d}</p><p className="mt-1 text-xs text-muted">{w}</p></div>{!read.includes(i) && <Btn size="sm" variant="ghost" onClick={() => setRead([...read, i])}>Mark read</Btn>}</div>)}</div>
}
export function Docs({ rows }: { rows: { name: string; type: string; date: string; status: string }[] }) {
  return (
    <>
      <div className="mb-4"><Btn onClick={() => toast('Document uploaded. It will be checked within 1 working day.')}><Icon n="plus" size={18} /> Upload document</Btn></div>
      <DataTable caption="documents" rows={rows} filterKey="type" filterLabel="Document type" cols={[{ key: 'name', label: 'Document' }, { key: 'type', label: 'Type' }, { key: 'date', label: 'Date' }, { key: 'status', label: 'Status', render: (r) => <Status s={r.status} /> }]} actions={() => <Btn size="sm" variant="outline" onClick={() => toast('Download started')}><Icon n="down" size={16} />Download</Btn>} />
    </>
  )
}
function Settings() {
  const [s, setS] = useState({ sms: true, email: true, news: false, twofa: true })
  const T = ({ k, l, d }: { k: keyof typeof s; l: string; d: string }) => <label className="flex min-h-14 items-center justify-between gap-4 border-b border-line py-2 last:border-0"><span><span className="block font-semibold">{l}</span><span className="text-sm text-muted">{d}</span></span><input type="checkbox" role="switch" checked={s[k]} onChange={() => setS({ ...s, [k]: !s[k] })} className="h-6 w-6" /></label>
  return <div className="grid gap-5 md:grid-cols-2"><Card title="Notifications"><T k="sms" l="SMS alerts" d="Repayment dates and offers." /><T k="email" l="Email updates" d="Statements and agreements." /><T k="news" l="Product news" d="Occasional updates from Banyira." /></Card><Card title="Security"><T k="twofa" l="Two-step verification" d="Confirm sign-ins with an SMS code." /><div className="mt-4"><Btn variant="outline" onClick={() => toast('Password reset link sent')}>Change password</Btn></div></Card><div className="md:col-span-2"><Btn onClick={() => toast('Settings saved')}>Save settings</Btn></div></div>
}
function Help({ biz }: { biz?: boolean }) {
  const q: [string, string][] = biz ? [['How do I accept an offer?', 'Open Marketplace Offers, compare the terms, then choose Accept. You will confirm before anything is final.'], ['What if Banyira asks for more information?', 'Open the application and use Respond to the request. Your assessment resumes once you reply.'], ['When do repayments fall due?', 'Dates are in Financing Management. Reminders are sent by SMS.']] : [['How is my money protected?', 'Agreements are recorded by Banyira, but investing carries risk and capital can be lost.'], ['What does the risk rating mean?', 'It is an assessment, not a guarantee of repayment.'], ['Can I withdraw an offer?', 'Yes, while it is pending, from Investment Offers.']]
  return <div className="grid gap-5 md:grid-cols-[1.4fr_1fr]"><div className="space-y-3">{q.map(([a, b]) => <details key={a} className="rounded-xl border border-line bg-white p-4"><summary className="min-h-8 cursor-pointer font-semibold">{a}</summary><p className="mt-2 text-muted">{b}</p></details>)}</div><Card title="Contact support"><p className="text-sm text-muted">Mon–Fri, 08:00–17:00</p><p className="mt-2">support@banyira.example</p><p>+265 000 000 000</p><div className="mt-4"><Btn onClick={() => toast('Support request sent. Reference SR-2041')}>Request a call back</Btn></div></Card></div>
}
function Profile({ biz }: { biz?: boolean }) {
  const steps = biz ? [['Email confirmed', true], ['Phone confirmed', true], ['Business registration verified', true], ['Owner identity verified', true], ['Bank account confirmed', false]] : [['Email confirmed', true], ['Phone confirmed', true], ['Identity document verified', true], ['Source of funds confirmed', false]]
  return <div className="grid gap-5 md:grid-cols-2"><Card title="Verification status"><ul className="space-y-2">{steps.map(([l, d]) => <li key={String(l)} className="flex items-center justify-between gap-3"><span className="flex items-center gap-2"><Icon n={d ? 'check' : 'clock'} className={d ? 'text-ok' : 'text-[#7a5200]'} />{l}</span><Status s={d ? 'Verified' : 'Pending'} /></li>)}</ul></Card><Card title={biz ? 'Business details' : 'Personal details'}><form className="space-y-3" onSubmit={(e) => { e.preventDefault(); toast('Profile saved') }}><TextField label={biz ? 'Business name' : 'Full name'} defaultValue={biz ? 'Mzuzu Fresh Foods' : 'Chikondi Banda'} /><TextField label="Phone" defaultValue="0999 123 456" />{biz && <TextField label="Address" defaultValue="Area 4, Mzuzu" />}<Btn type="submit">Save</Btn></form></Card></div>
}

// ---------- offer modal ----------
export function OfferModal({ open, onClose, opp, onDone }: { open: boolean; onClose: () => void; opp: Opp; onDone?: () => void }) {
  const rules = useRules(), offers = offerStore.use()
  const remaining = opp.amount - fundedOf(opp, offers)
  const [v, setV] = useState({ amount: '', rate: String(opp.rate), months: String(opp.months) })
  const [errs, setErrs] = useState<Record<string, string>>({})
  const band = rules.bands[opp.risk]
  const a = +v.amount, r = +v.rate, m = +v.months
  const submit = () => {
    const e: Record<string, string> = {}
    if (!a || a < 50000) e.amount = 'Minimum offer is MK50,000.'
    else if (a > remaining) e.amount = `Only ${mk(remaining)} remains to be funded.`
    if (!r || r <= 0) e.rate = 'Enter an annual interest rate.'
    else if (r > rules.ceiling) e.rate = `The maximum allowed rate is ${rules.ceiling}% per year.`
    if (!m || m < 1) e.months = 'Enter a number of months.'
    else if (band.maxMonths && m > band.maxMonths) e.months = `Class ${opp.risk} businesses are limited to ${band.maxMonths} months.`
    if (band.maxAmount && opp.amount > band.maxAmount) e.amount = `Class ${opp.risk} financing is limited to ${mk(band.maxAmount)}.`
    setErrs(e)
    if (Object.keys(e).length) return
    offerStore.set((o) => [{ id: 'F-' + (1040 + o.length), oppId: opp.id, investor: 'You', amount: a, rate: r, months: m, status: 'Pending', date: '2025-06-05' }, ...o])
    toast(`Offer of ${mk(a)} submitted to ${opp.name}`); onDone?.(); onClose()
  }
  return (
    <Modal open={open} onClose={onClose} title={`Make an offer: ${opp.name}`} actions={<><Btn variant="outline" onClick={onClose}>Cancel</Btn><Btn onClick={submit}>Submit offer</Btn></>}>
      <div className="space-y-3 text-ink">
        <div className="flex items-center gap-3 text-sm"><RiskBadge r={opp.risk} long /><span className="text-muted">Remaining {mk(remaining)}</span></div>
        <TextField label="Amount (MK)" inputMode="numeric" value={v.amount} onChange={(e) => setV({ ...v, amount: e.target.value.replace(/\D/g, '') })} error={errs.amount} hint={`Up to ${mk(remaining)}`} />
        <TextField label="Annual interest rate (%)" inputMode="decimal" value={v.rate} onChange={(e) => setV({ ...v, rate: e.target.value })} error={errs.rate} hint={`Maximum ${rules.ceiling}% per year (current platform rule).`} />
        <TextField label="Repayment period (months)" inputMode="numeric" value={v.months} onChange={(e) => setV({ ...v, months: e.target.value })} error={errs.months} hint={band.maxMonths ? `Class ${opp.risk} limit: ${band.maxMonths} months.` : undefined} />
        {a > 0 && r > 0 && m > 0 && <p className="rounded-lg bg-mint p-3 text-sm text-forest">If accepted, estimated monthly repayment to you: <strong>{mk(monthly(a, r, m))}</strong>. This is an estimate, not a guaranteed return.</p>}
        <p className="text-xs text-muted">Investing involves risk. You may lose some or all of your capital.</p>
      </div>
    </Modal>
  )
}

// ================= INVESTOR =================
const INV_NAV: NavItem[] = [
  { id: 'overview', label: 'Overview', icon: 'home' }, { id: 'marketplace', label: 'Marketplace', icon: 'store' }, { id: 'investments', label: 'My Investments', icon: 'wallet' }, { id: 'offers', label: 'Investment Offers', icon: 'list' }, { id: 'messages', label: 'Messages', icon: 'mail', badge: '2' },
  { id: 'portfolio', label: 'Portfolio', icon: 'chart' }, { id: 'transactions', label: 'Transactions', icon: 'list' }, { id: 'repayments', label: 'Repayment History', icon: 'clock' }, { id: 'agreements', label: 'Agreements', icon: 'doc' }, { id: 'documents', label: 'Documents', icon: 'doc' },
  { id: 'notifications', label: 'Notifications', icon: 'bell' }, { id: 'profile', label: 'Profile & Verification', icon: 'user' }, { id: 'settings', label: 'Settings', icon: 'cog' }, { id: 'help', label: 'Help Centre', icon: 'help' },
]
const investments = [
  { id: 'INV-301', biz: 'Blantyre Logistics Co.', risk: 'A+' as Risk, amount: 2_000_000, rate: 18, months: 36, repaid: 22, status: 'Active' },
  { id: 'INV-288', biz: 'Kasungu Tailoring Collective', risk: 'B' as Risk, amount: 500_000, rate: 26, months: 15, repaid: 60, status: 'Active' },
  { id: 'INV-265', biz: 'Lilongwe Print House', risk: 'A' as Risk, amount: 800_000, rate: 22, months: 24, repaid: 35, status: 'Active' },
  { id: 'INV-240', biz: 'Salima Lakeside Lodge', risk: 'A' as Risk, amount: 700_000, rate: 24, months: 24, repaid: 8, status: 'Active' },
  { id: 'INV-199', biz: 'Zomba Grain Traders', risk: 'B' as Risk, amount: 300_000, rate: 27, months: 9, repaid: 100, status: 'Completed' },
]
const outstanding = (i: (typeof investments)[number]) => Math.round(i.amount * (1 - i.repaid / 100))

export function Investor({ page }: { page: string }) {
  const [view, setView] = useState<'ok' | 'err'>('ok')
  const cur = INV_NAV.find((n) => n.id === page) || INV_NAV[0]
  return (
    <Shell nav={INV_NAV} base="/investor" page={cur.id} who="Chikondi Banda" sub="Investor account" onExit={exit}>
      <PageHead title={cur.label} />
      {view === 'err' ? <ErrorState onRetry={() => setView('ok')} /> : <Async key={cur.id}><InvPage id={cur.id} onErr={() => setView('err')} /></Async>}
    </Shell>
  )
}

function InvPage({ id, onErr }: { id: string; onErr: () => void }) {
  const offers = offerStore.use()
  const mine = offers.filter((o) => o.investor === 'You')
  const [open, setOpen] = useState<Opp | null>(null)
  const [wd, setWd] = useState<string | null>(null)
  switch (id) {
    case 'overview': {
      const active = investments.filter((i) => i.status === 'Active')
      const total = investments.reduce((a, i) => a + i.amount, 0)
      return (
        <div className="space-y-5">
          <div className="grid grid-cols-2 gap-3 lg:grid-cols-3">
            <Stat label="Total invested" value={mk(total)} /><Stat label="Active investments" value={String(active.length)} /><Stat label="Outstanding balance" value={mk(active.reduce((a, i) => a + outstanding(i), 0))} />
            <Stat label="Repayments received" value="MK1,184,500" note="Capital and interest" /><Stat label="Next repayment" value="MK142,300" note="Due 15 Jun 2025" /><Stat label="Pending offers" value={String(mine.filter((o) => o.status === 'Pending').length)} />
          </div>
          <div className="grid gap-5 lg:grid-cols-2">
            <Card title="Portfolio distribution by risk class"><Donut label="Portfolio by risk class" data={[{ l: 'A+', v: 2_000_000 }, { l: 'A', v: 1_500_000 }, { l: 'B', v: 800_000 }]} /></Card>
            <Card title="Repayments received (MK thousands)"><BarChart label="Repayments received per month" data={[['Jan', 120], ['Feb', 165], ['Mar', 190], ['Apr', 210], ['May', 245], ['Jun', 255]].map(([l, v]) => ({ l: l as string, v: v as number }))} /></Card>
          </div>
          <Card title="Upcoming repayments"><DataTable caption="upcoming repayments" searchable={false} pageSize={4} rows={[{ d: '15 Jun 2025', b: 'Blantyre Logistics Co.', a: mk(71_200) }, { d: '18 Jun 2025', b: 'Kasungu Tailoring Collective', a: mk(36_100) }, { d: '22 Jun 2025', b: 'Lilongwe Print House', a: mk(35_000) }]} cols={[{ key: 'd', label: 'Date' }, { key: 'b', label: 'Business' }, { key: 'a', label: 'Expected', align: 'right' }]} /></Card>
          <Notice tone="risk">Past repayments do not guarantee future repayments. Ratings are assessments, not guarantees.</Notice>
        </div>
      )
    }
    case 'marketplace':
      return <Marketplace onOffer={setOpen} open={open} setOpen={setOpen} />
    case 'investments':
      return <InvTable />
    case 'offers':
      return (
        <div className="space-y-4">
          <div className="flex flex-wrap gap-2"><Btn href="#/investor/marketplace"><Icon n="plus" size={18} />Make a new offer</Btn><Btn variant="ghost" onClick={onErr}>Preview error state</Btn></div>
          {mine.length === 0 ? <Empty title="No offers yet" body="Find a business in the marketplace and make your first offer." action={<Btn href="#/investor/marketplace">Browse marketplace</Btn>} /> : (
            <DataTable caption="your offers" filterKey="status" filterLabel="Status" rows={mine.map((o) => ({ ...o, biz: opps.find((x) => x.id === o.oppId)!.name }))} cols={[{ key: 'id', label: 'Offer' }, { key: 'biz', label: 'Business' }, { key: 'amount', label: 'Amount', align: 'right', render: (r) => mk(r.amount) }, { key: 'rate', label: 'Rate', render: (r) => r.rate + '% p.a.' }, { key: 'months', label: 'Period', render: (r) => r.months + ' months' }, { key: 'status', label: 'Status', render: (r) => <Status s={r.status} /> }]} actions={(r) => r.status === 'Pending' ? <Btn size="sm" variant="outline" onClick={() => setWd(r.id)}>Withdraw</Btn> : null} />
          )}
          <Confirm open={!!wd} danger title="Withdraw this offer?" body="The business will no longer see it. You can make a new offer later if funding is still open." confirm="Withdraw offer" onClose={() => setWd(null)} onConfirm={() => { offerStore.set((o) => o.map((x) => (x.id === wd ? { ...x, status: 'Withdrawn' } : x))); toast('Offer withdrawn') }} />
        </div>
      )
    case 'messages': return <Chat who={['Blantyre Logistics', 'Banyira Support', 'Mzuzu Fresh Foods']} docs="Offer-summary.pdf" />
    case 'portfolio':
      return <div className="grid gap-5 lg:grid-cols-2"><Card title="By risk class"><Donut label="By risk class" data={[{ l: 'A+', v: 40 }, { l: 'A', v: 35 }, { l: 'B', v: 25 }]} /></Card><Card title="By industry"><Donut label="By industry" data={[{ l: 'Transport', v: 45 }, { l: 'Printing', v: 20 }, { l: 'Manufacturing', v: 15 }, { l: 'Hospitality', v: 20 }]} /></Card><Card title="Outstanding balance trend (MK thousands)"><LineChart label="Outstanding balance" data={[{ l: 'Jan', v: 3100 }, { l: 'Mar', v: 3300 }, { l: 'Apr', v: 3250 }, { l: 'May', v: 3000 }, { l: 'Jun', v: 2850 }]} /></Card><Card title="Concentration"><p className="text-muted">Largest single exposure: Blantyre Logistics Co. (50% of capital). Spreading capital across more businesses can reduce the impact of one default.</p><div className="mt-3"><Notice tone="info">Diversification does not remove risk.</Notice></div></Card></div>
    case 'transactions': return <DataTable caption="transactions" filterKey="type" pageSize={6} rows={[{ d: '2025-06-01', type: 'Repayment', ref: 'TX-8812', desc: 'Kasungu Tailoring', amt: 36_100 }, { d: '2025-05-28', type: 'Offer funded', ref: 'TX-8790', desc: 'Lilongwe Print House', amt: -500_000 }, { d: '2025-05-15', type: 'Repayment', ref: 'TX-8744', desc: 'Blantyre Logistics', amt: 71_200 }, { d: '2025-05-10', type: 'Deposit', ref: 'TX-8731', desc: 'Mobile money', amt: 1_000_000 }, { d: '2025-04-30', type: 'Repayment', ref: 'TX-8702', desc: 'Salima Lakeside', amt: 32_000 }, { d: '2025-04-18', type: 'Repayment', ref: 'TX-8660', desc: 'Lilongwe Print House', amt: 35_000 }, { d: '2025-04-11', type: 'Offer funded', ref: 'TX-8610', desc: 'Blantyre Logistics', amt: -2_000_000 }]} cols={[{ key: 'd', label: 'Date' }, { key: 'ref', label: 'Reference' }, { key: 'type', label: 'Type' }, { key: 'desc', label: 'Description' }, { key: 'amt', label: 'Amount', align: 'right', render: (r) => <span className={r.amt < 0 ? 'text-clay' : 'text-ok'}>{r.amt < 0 ? '-' : '+'}{mk(Math.abs(r.amt))}</span> }]} />
    case 'repayments': return <DataTable caption="repayment history" filterKey="status" rows={[{ d: '2025-06-01', b: 'Kasungu Tailoring', cap: 30_000, int: 6_100, status: 'Paid' }, { d: '2025-05-15', b: 'Blantyre Logistics', cap: 48_000, int: 23_200, status: 'Paid' }, { d: '2025-05-12', b: 'Lilongwe Print House', cap: 22_000, int: 13_000, status: 'Paid' }, { d: '2025-04-30', b: 'Salima Lakeside', cap: 20_000, int: 12_000, status: 'Paid late' }, { d: '2025-04-15', b: 'Blantyre Logistics', cap: 47_000, int: 24_200, status: 'Paid' }, { d: '2025-03-15', b: 'Zomba Grain Traders', cap: 33_000, int: 6_500, status: 'Paid' }]} cols={[{ key: 'd', label: 'Date' }, { key: 'b', label: 'Business' }, { key: 'cap', label: 'Capital', align: 'right', render: (r) => mk(r.cap) }, { key: 'int', label: 'Interest', align: 'right', render: (r) => mk(r.int) }, { key: 'status', label: 'Status', render: (r) => <Status s={r.status === 'Paid late' ? 'Overdue (paid late)' : r.status} /> }]} />
    case 'agreements': return <DataTable caption="agreements" filterKey="status" rows={investments.map((i) => ({ ref: 'AG-' + i.id.slice(4), biz: i.biz, amount: mk(i.amount), status: i.status === 'Active' ? 'Signed' : 'Completed' })).concat([{ ref: 'AG-pending', biz: 'Lilongwe Print House (new offer)', amount: mk(500_000), status: 'Awaiting signature' }])} cols={[{ key: 'ref', label: 'Agreement' }, { key: 'biz', label: 'Business' }, { key: 'amount', label: 'Amount', align: 'right' }, { key: 'status', label: 'Status', render: (r) => <Status s={r.status} /> }]} actions={(r) => r.status === 'Awaiting signature' ? <Btn size="sm" onClick={() => toast('Agreement signed')}>Review and sign</Btn> : <Btn size="sm" variant="outline" onClick={() => toast('Download started')}>Download</Btn>} />
    case 'documents': return <Docs rows={[{ name: 'National ID', type: 'Identity', date: '2025-03-02', status: 'Verified' }, { name: 'Investor agreement terms', type: 'Agreement', date: '2025-03-04', status: 'Signed' }, { name: 'Statement May 2025', type: 'Statement', date: '2025-06-01', status: 'Available' }, { name: 'Source of funds letter', type: 'Identity', date: '2025-05-20', status: 'Pending review' }]} />
    case 'notifications': return <Notifs items={[['New business matching your preferences', 'Shire Valley Agro (B) has been listed.', 'Today'], ['Repayment received', 'MK36,100 from Kasungu Tailoring.', '1 Jun'], ['Offer update', 'Your offer to Blantyre Logistics was accepted.', '11 Apr']]} />
    case 'profile': return <Profile />
    case 'settings': return <Settings />
    default: return <Help />
  }
}

function InvTable() {
  const [sel, setSel] = useState<(typeof investments)[number] | null>(null)
  return (
    <>
      <DataTable caption="investments" filterKey="status" rows={investments} cols={[{ key: 'biz', label: 'Business' }, { key: 'risk', label: 'Risk', render: (r) => <RiskBadge r={r.risk} /> }, { key: 'amount', label: 'Invested', align: 'right', render: (r) => mk(r.amount) }, { key: 'rate', label: 'Terms', render: (r) => `${r.rate}% · ${r.months} mo` }, { key: 'repaid', label: 'Repaid', render: (r) => <div className="w-28"><Progress value={r.repaid} label={`${r.biz} repaid`} /><span className="text-xs text-muted">{r.repaid}%</span></div> }, { key: 'out', label: 'Outstanding', align: 'right', render: (r) => mk(outstanding(r)) }]} actions={(r) => <Btn size="sm" variant="outline" onClick={() => setSel(r)}>History</Btn>} />
      <Modal open={!!sel} onClose={() => setSel(null)} title={sel ? `${sel.biz}: history` : ''}>
        {sel && <ul className="space-y-2 text-ink">{[['Offer accepted', '11 Apr 2025'], ['Agreement signed', '14 Apr 2025'], ['Funds transferred', '16 Apr 2025'], ['Repayment received', '15 May 2025'], ['Repayment received', '15 Jun 2025 (expected)']].map(([a, b]) => <li key={a + b} className="flex justify-between border-b border-line pb-2 text-sm"><span>{a}</span><span className="text-muted">{b}</span></li>)}</ul>}
      </Modal>
    </>
  )
}

function Marketplace({ open, setOpen }: { onOffer: (o: Opp) => void; open: Opp | null; setOpen: (o: Opp | null) => void }) {
  const offers = offerStore.use()
  const [q, setQ] = useState(''), [risk, setRisk] = useState('All'), [ind, setInd] = useState('All')
  const list = opps.filter((o) => (risk === 'All' || o.risk === risk) && (ind === 'All' || o.industry === ind) && (o.name + o.location + o.purpose).toLowerCase().includes(q.toLowerCase()))
  return (
    <div>
      <div className="mb-4 flex flex-wrap gap-2">
        <input aria-label="Search marketplace" placeholder="Search business, place or purpose" value={q} onChange={(e) => setQ(e.target.value)} className={cn(inputCls, 'min-w-[220px] flex-1')} />
        <select aria-label="Risk class" value={risk} onChange={(e) => setRisk(e.target.value)} className={cn(inputCls, 'w-auto')}>{['All', 'A+', 'A', 'B', 'C'].map((r) => <option key={r} value={r}>{r === 'All' ? 'All risk classes' : 'Class ' + r}</option>)}</select>
        <select aria-label="Industry" value={ind} onChange={(e) => setInd(e.target.value)} className={cn(inputCls, 'w-auto')}>{['All', ...new Set(opps.map((o) => o.industry))].map((r) => <option key={r} value={r}>{r === 'All' ? 'All industries' : r}</option>)}</select>
      </div>
      {list.length === 0 ? <Empty title="No businesses match" body="Try a different search or clear the filters." action={<Btn variant="outline" onClick={() => { setQ(''); setRisk('All'); setInd('All') }}>Clear filters</Btn>} /> : (
        <div className="grid gap-4 lg:grid-cols-2">
          {list.map((o) => {
            const f = fundedOf(o, offers), pct = (f / o.amount) * 100, full = f >= o.amount
            return (
              <article key={o.id} className="flex flex-col rounded-xl border border-line bg-white p-5">
                <div className="flex items-start justify-between gap-3"><div><h3 className="text-xl">{o.name}</h3><p className="text-sm text-muted">{o.industry} · {o.location}</p></div><RiskBadge r={o.risk} long /></div>
                <p className="mt-3 text-[15px]">{o.desc}</p>
                <p className="mt-1 text-sm"><span className="text-muted">Purpose:</span> {o.purpose}</p>
                <div className="mt-4"><Progress value={pct} label={`${o.name} funded`} /></div>
                <dl className="mt-3 grid grid-cols-3 gap-2 text-sm"><div><dt className="text-muted">Requested</dt><dd className="font-semibold">{mk(o.amount)}</dd></div><div><dt className="text-muted">Funded</dt><dd className="font-semibold">{mk(f)}</dd></div><div><dt className="text-muted">Remaining</dt><dd className="font-semibold">{mk(o.amount - f)}</dd></div><div><dt className="text-muted">Proposed rate</dt><dd className="font-semibold">{o.rate}% p.a.</dd></div><div><dt className="text-muted">Period</dt><dd className="font-semibold">{o.months} months</dd></div><div><dt className="text-muted">Status</dt><dd><Status s={full ? 'Fully funded' : o.status} /></dd></div></dl>
                <div className="mt-4 flex flex-1 items-end"><Btn disabled={full} onClick={() => setOpen(o)}>{full ? 'Fully funded' : 'Make an offer'}</Btn></div>
              </article>
            )
          })}
        </div>
      )}
      <p className="mt-4 text-xs text-muted">Risk classes are Banyira assessments, not guarantees. Proposed rates are not guaranteed returns.</p>
      {open && <OfferModal open opp={open} onClose={() => setOpen(null)} />}
    </div>
  )
}

// ================= BUSINESS =================
const BIZ_NAV: NavItem[] = [
  { id: 'overview', label: 'Overview', icon: 'home' }, { id: 'applications', label: 'Applications', icon: 'doc', badge: '1' }, { id: 'offers', label: 'Marketplace Offers', icon: 'store', badge: '3' }, { id: 'financing', label: 'Financing Management', icon: 'wallet' },
  { id: 'profile', label: 'Business Profile', icon: 'user' }, { id: 'documents', label: 'Documents', icon: 'doc' }, { id: 'messages', label: 'Messages', icon: 'mail' }, { id: 'notifications', label: 'Notifications', icon: 'bell' }, { id: 'support', label: 'Support', icon: 'help' }, { id: 'settings', label: 'Settings', icon: 'cog' },
]
export function Business({ page }: { page: string }) {
  const cur = BIZ_NAV.find((n) => n.id === page) || BIZ_NAV[0]
  return (
    <Shell nav={BIZ_NAV} base="/business" page={cur.id} who="Mzuzu Fresh Foods" sub="Business account" onExit={exit}>
      <PageHead title={cur.label} />
      <Async key={cur.id}><BizPage id={cur.id} /></Async>
    </Shell>
  )
}

function BizPage({ id }: { id: string }) {
  switch (id) {
    case 'overview': return <BizOverview />
    case 'applications': return <Applications />
    case 'offers': return <BizOffers />
    case 'financing': return <Financing />
    case 'profile': return <Profile biz />
    case 'documents': return <Docs rows={[{ name: 'Business registration certificate', type: 'Registration', date: '2025-05-02', status: 'Verified' }, { name: 'Bank statements (6 months)', type: 'Financial', date: '2025-05-20', status: 'Verified' }, { name: 'Stock list', type: 'Financial', date: '2025-06-03', status: 'Pending review' }, { name: 'Cold-room supplier quotation', type: 'Application', date: '2025-05-21', status: 'Verified' }]} />
    case 'messages': return <Chat who={['Investor 4821', 'Investor 2290', 'Banyira Credit Team']} docs="Stock-list.pdf" />
    case 'notifications': return <Notifs items={[['New investor offer', 'Investor 7713 offered MK500,000 at 22% for 24 months.', 'Today'], ['Information requested', 'Please upload your latest stock list.', '2 Jun'], ['Assessment complete', 'Your business was classified B and published.', '28 May']]} />
    case 'settings': return <Settings />
    default: return <Help biz />
  }
}

function BizOverview() {
  const offers = offerStore.use().filter((o) => o.oppId === 'o1' && o.investor !== 'You')
  const secured = offers.filter((o) => o.status === 'Accepted').reduce((a, o) => a + o.amount, 0)
  return (
    <div className="space-y-5">
      <Card><div className="flex flex-wrap items-center justify-between gap-3"><div><p className="text-sm text-muted">Application APP-2041: Cold-room equipment</p><p className="font-display text-2xl text-forest">Published on marketplace</p></div><RiskBadge r="B" long /></div><ol className="mt-5 grid gap-2 sm:grid-cols-5 text-sm">{['Submitted', 'Verified', 'Assessed', 'Published', 'Funded'].map((s, i) => <li key={s} className={cn('rounded-lg px-3 py-2 font-medium', i < 4 ? 'bg-forest text-white' : 'bg-[#ebe8dd] text-muted')}>{i < 4 && <Icon n="check" size={14} className="mr-1 inline" />}{s}</li>)}</ol></Card>
      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4"><Stat label="Financing received" value="MK2,000,000" note="Earlier loan, AG-0071" /><Stat label="Outstanding balance" value="MK1,120,000" /><Stat label="Secured this application" value={mk(secured)} note="of MK4,500,000" /><Stat label="Pending offers" value={String(offers.filter((o) => o.status === 'Pending').length)} /></div>
      <Notice tone="warn" title="Action needed">Banyira has asked for your latest stock list. <a href="#/business/applications" className="font-semibold underline">Respond now</a></Notice>
      <div className="grid gap-5 lg:grid-cols-2"><Card title="Upcoming repayments"><ul className="divide-y divide-line">{[['15 Jun 2025', 'MK112,000'], ['15 Jul 2025', 'MK112,000'], ['15 Aug 2025', 'MK112,000']].map(([d, a]) => <li key={d} className="flex justify-between py-2"><span>{d}</span><strong>{a}</strong></li>)}</ul></Card><Card title="Recent activity"><ul className="space-y-2 text-sm">{['Investor 7713 made an offer', 'Banyira requested more information', 'Business published to marketplace', 'Credit assessment completed: class B'].map((t) => <li key={t} className="flex gap-2"><Icon n="clock" size={16} className="mt-0.5 text-muted" />{t}</li>)}</ul></Card></div>
    </div>
  )
}

function Applications() {
  const [mode, setMode] = useState<'list' | 'new' | 'view'>('list')
  const [step, setStep] = useState(0)
  const [replied, setReplied] = useState(false)
  const [draft, setDraft] = useState(true)
  const [submit, setSubmit] = useState(false)
  const [done, setDone] = useState(false)
  const rules = useRules()
  const [amt, setAmt] = useState('')
  const steps = ['Financing needs', 'Business details', 'Documents', 'Review']
  if (done) return <div className="space-y-4"><Notice tone="ok" title="Application submitted">Reference APP-2057. A credit officer will verify your documents within 3 working days. Submission does not guarantee approval or funding.</Notice><Btn onClick={() => { setDone(false); setMode('list') }}>Back to applications</Btn></div>
  if (mode === 'new') {
    const a = +amt
    return (
      <div className="max-w-2xl">
        <Tabs tabs={steps} value={steps[step]} onChange={(v) => setStep(steps.indexOf(v))} />
        <form className="mt-5 space-y-4" onSubmit={(e) => e.preventDefault()}>
          {step === 0 && <><SelectField label="Financing product" options={['Short-Term Business Financing', 'Working Capital Financing', 'Business Expansion Financing', 'Long-Term Business Financing', 'Equipment and Machinery Financing']} /><TextField label="Amount needed (MK)" inputMode="numeric" value={amt} onChange={(e) => setAmt(e.target.value.replace(/\D/g, ''))} hint={a ? `Estimated service fee: ${feePct(rules, a)}% = ${mk((a * feePct(rules, a)) / 100)} (current rules ${rules.version}). Confirmed before you accept offers.` : 'Your service fee is shown here once you enter an amount.'} /><TextField label="Preferred repayment period (months)" inputMode="numeric" /><Field label="What will you use the funds for?">{(x) => <textarea {...x} rows={3} className={inputCls + ' py-2'} />}</Field></>}
          {step === 1 && <><TextField label="Monthly turnover (MK)" inputMode="numeric" /><TextField label="Number of employees" inputMode="numeric" /><SelectField label="Main customers" options={['Individuals', 'Other businesses', 'Government and NGOs', 'Mixed']} /></>}
          {step === 2 && <><p className="text-sm text-muted">Upload clear photos or PDFs. Each file up to 2 MB.</p>{['Registration certificate', 'Bank or mobile money statements', 'Quotation or invoice'].map((d) => <Field key={d} label={d}>{(x) => <input {...x} type="file" className={inputCls + ' py-2'} />}</Field>)}</>}
          {step === 3 && <Card title="Review"><p className="text-sm text-muted">Check your details. After submission, a Banyira credit officer will verify and assess your application. This does not guarantee approval or funding.</p></Card>}
          <div className="flex flex-wrap gap-2">
            {step > 0 && <Btn variant="outline" onClick={() => setStep(step - 1)}>Back</Btn>}
            <Btn variant="outline" onClick={() => { setDraft(true); toast('Draft saved') }}>Save draft</Btn>
            {step < 3 ? <Btn onClick={() => setStep(step + 1)}>Continue</Btn> : <Btn onClick={() => setSubmit(true)}>Submit for review</Btn>}
            <Btn variant="ghost" onClick={() => setMode('list')}>Cancel</Btn>
          </div>
        </form>
        <Confirm open={submit} onClose={() => setSubmit(false)} title="Submit your application?" body="You will not be able to edit it while it is being reviewed. We may ask for more information." confirm="Submit" onConfirm={() => { setDone(true); setMode('list') }} />
      </div>
    )
  }
  if (mode === 'view') {
    return (
      <div className="space-y-5"><Btn variant="ghost" onClick={() => setMode('list')}>&larr; All applications</Btn>
        <Card title="APP-2041: Cold-room equipment"><ol className="space-y-4">{[['Submitted', '20 May 2025', 'Complete'], ['Verification of identity and documents', '24 May 2025', 'Complete'], ['Information requested: stock list', '2 Jun 2025', replied ? 'Complete' : 'Action needed'], ['Credit assessment', '28 May 2025', 'Complete'], ['Decision: Approved, class B', '28 May 2025', 'Approved'], ['Published to marketplace', '29 May 2025', 'Published']].map(([a, b, c]) => <li key={a} className="flex items-start justify-between gap-3 border-l-2 border-forest pl-4"><div><p className="font-semibold">{a}</p><p className="text-sm text-muted">{b}</p></div><Status s={c === 'Action needed' ? 'Pending' : c} /></li>)}</ol></Card>
        {!replied ? <Card title="Respond to information request"><p className="mb-3 text-sm">Banyira asks: “Please upload your latest stock list.”</p><input aria-label="Upload stock list" type="file" className={inputCls + ' py-2'} /><div className="mt-3"><Btn onClick={() => { setReplied(true); toast('Response sent to the credit team') }}>Send response</Btn></div></Card> : <Notice tone="ok">Response sent. Thank you.</Notice>}
        <Notice tone="info" title="Assessment decision">Approved with risk class B (moderate risk). Class B financing is limited to {rules.bands.B.maxMonths} months under current rules. This rating is an assessment, not a guarantee.</Notice>
      </div>
    )
  }
  return (
    <div className="space-y-4">
      <Btn onClick={() => { setStep(0); setMode('new') }}><Icon n="plus" size={18} />New application</Btn>
      {draft && <Card title="Draft: Working capital (saved 3 Jun)" action={<Badge tone="amber">Draft</Badge>}><div className="flex flex-wrap gap-2"><Btn variant="outline" onClick={() => { setStep(1); setMode('new') }}>Continue draft</Btn><Btn variant="ghost" onClick={() => { setDraft(false); toast('Draft deleted') }}>Delete draft</Btn></div></Card>}
      <DataTable caption="applications" filterKey="status" rows={[{ id: 'APP-2041', p: 'Equipment and Machinery', a: mk(4_500_000), d: '20 May 2025', status: 'Published' }, { id: 'APP-1850', p: 'Short-Term Business', a: mk(2_000_000), d: '12 Jan 2025', status: 'Completed' }, { id: 'APP-1702', p: 'Working Capital', a: mk(8_000_000), d: '2 Nov 2024', status: 'Rejected' }]} cols={[{ key: 'id', label: 'Reference' }, { key: 'p', label: 'Product' }, { key: 'a', label: 'Amount', align: 'right' }, { key: 'd', label: 'Submitted' }, { key: 'status', label: 'Status', render: (r) => <Status s={r.status} /> }]} actions={() => <Btn size="sm" variant="outline" onClick={() => setMode('view')}>Track</Btn>} />
    </div>
  )
}

function BizOffers() {
  const all = offerStore.use()
  const rules = useRules()
  const offers = all.filter((o) => o.oppId === 'o1' && o.investor !== 'You')
  const [act, setAct] = useState<{ id: string; kind: 'Accepted' | 'Rejected' } | null>(null)
  const [sort, setSort] = useState('amount')
  const secured = offers.filter((o) => o.status === 'Accepted').reduce((a, o) => a + o.amount, 0)
  const sorted = useMemo(() => [...offers].sort((a, b) => (sort === 'rate' ? a.rate - b.rate : sort === 'months' ? a.months - b.months : b.amount - a.amount)), [offers, sort])
  const best = Math.min(...offers.filter((o) => o.status === 'Pending').map((o) => o.rate))
  const target = opps[0].amount
  const target_o = offers.find((o) => o.id === act?.id)
  return (
    <div className="space-y-5">
      <div className="grid gap-3 sm:grid-cols-3"><Stat label="Total secured" value={mk(secured)} note={`of ${mk(target)} requested`} /><Stat label="Still needed" value={mk(target - secured)} /><Stat label="Service fee on secured" value={mk((secured * feePct(rules, target)) / 100)} note={`${feePct(rules, target)}% band for ${mk(target)}`} /></div>
      <Progress value={(secured / target) * 100} label="Financing secured" />
      <div className="flex flex-wrap items-center gap-2"><label htmlFor="sort" className="text-sm font-semibold">Compare by</label><select id="sort" value={sort} onChange={(e) => setSort(e.target.value)} className={cn(inputCls, 'w-auto')}><option value="amount">Largest amount</option><option value="rate">Lowest interest rate</option><option value="months">Shortest period</option></select></div>
      {offers.length === 0 ? <Empty title="No offers yet" body="Investors will appear here once they make offers." /> : (
        <div className="grid gap-3 md:grid-cols-2">
          {sorted.map((o) => {
            const pay = monthly(o.amount, o.rate, o.months)
            return (
              <article key={o.id} className="rounded-xl border border-line bg-white p-5">
                <div className="flex items-center justify-between"><h3 className="text-lg">{o.investor}</h3><Status s={o.status} /></div>
                <dl className="mt-3 grid grid-cols-3 gap-2 text-sm"><div><dt className="text-muted">Amount</dt><dd className="font-display text-lg font-semibold">{mk(o.amount)}</dd></div><div><dt className="text-muted">Rate p.a.</dt><dd className="font-display text-lg font-semibold">{o.rate}% {o.status === 'Pending' && o.rate === best && <Badge tone="green">Lowest</Badge>}</dd></div><div><dt className="text-muted">Period</dt><dd className="font-display text-lg font-semibold">{o.months} mo</dd></div></dl>
                <p className="mt-3 text-sm text-muted">Monthly repayment about {mk(pay)}; total repaid about {mk(pay * o.months)}.</p>
                {o.status === 'Pending' && <div className="mt-4 flex gap-2"><Btn onClick={() => setAct({ id: o.id, kind: 'Accepted' })}>Accept</Btn><Btn variant="outline" onClick={() => setAct({ id: o.id, kind: 'Rejected' })}>Reject</Btn></div>}
              </article>
            )
          })}
        </div>
      )}
      <Confirm open={!!act} danger={act?.kind === 'Rejected'} onClose={() => setAct(null)} title={act?.kind === 'Accepted' ? 'Accept this offer?' : 'Reject this offer?'} confirm={act?.kind === 'Accepted' ? 'Accept offer' : 'Reject offer'}
        body={act?.kind === 'Accepted' && target_o ? `You agree to borrow ${mk(target_o.amount)} at ${target_o.rate}% a year for ${target_o.months} months. An agreement will be prepared for signing. This can’t be undone.` : 'The investor will be told the offer was not accepted.'}
        onConfirm={() => { offerStore.set((x) => x.map((o) => (o.id === act!.id ? { ...o, status: act!.kind } : o))); toast(act!.kind === 'Accepted' ? 'Offer accepted. Agreement is being prepared.' : 'Offer rejected') }} />
    </div>
  )
}

function Financing() {
  const [tab, setTab] = useState('Active agreements')
  const sched = Array.from({ length: 8 }, (_, i) => ({ n: i + 1, d: `15 ${['Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan'][i]} ${i < 7 ? 2025 : 2026}`, a: mk(112_000), bal: mk(Math.max(0, 1_120_000 - 112_000 * (i + 1) * 0.85)), status: i === 0 ? 'Due soon' : 'Scheduled' }))
  return (
    <div className="space-y-5">
      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4"><Stat label="Active agreements" value="1" /><Stat label="Outstanding balance" value="MK1,120,000" /><Stat label="Next payment" value="MK112,000" note="15 Jun 2025" /><Stat label="Paid to date" value="MK1,008,000" /></div>
      <Tabs tabs={['Active agreements', 'Repayment schedule', 'Payment records', 'Historical financing']} value={tab} onChange={setTab} />
      {tab === 'Active agreements' && <DataTable caption="agreements" searchable={false} rows={[{ id: 'AG-0071', inv: '3 investors', a: mk(2_000_000), t: '16% p.a., 18 months', out: mk(1_120_000) }]} cols={[{ key: 'id', label: 'Agreement' }, { key: 'inv', label: 'Investors' }, { key: 'a', label: 'Amount', align: 'right' }, { key: 't', label: 'Terms' }, { key: 'out', label: 'Outstanding', align: 'right' }]} actions={() => <Btn size="sm" variant="outline" onClick={() => toast('Statement downloaded')}><Icon n="down" size={16} />Statement</Btn>} />}
      {tab === 'Repayment schedule' && <><DataTable caption="repayment schedule" pageSize={5} rows={sched} cols={[{ key: 'n', label: '#' }, { key: 'd', label: 'Due date' }, { key: 'a', label: 'Instalment', align: 'right' }, { key: 'bal', label: 'Balance after', align: 'right' }, { key: 'status', label: 'Status', render: (r) => <Status s={r.status} /> }]} /><div className="mt-3"><Btn onClick={() => toast('Payment instructions sent by SMS')}>Make a payment</Btn></div></>}
      {tab === 'Payment records' && <DataTable caption="payments" filterKey="status" rows={[{ d: '15 May 2025', ref: 'PAY-5521', a: mk(112_000), status: 'Paid' }, { d: '15 Apr 2025', ref: 'PAY-5410', a: mk(112_000), status: 'Paid' }, { d: '15 Mar 2025', ref: 'PAY-5302', a: mk(112_000), status: 'Paid' }]} cols={[{ key: 'd', label: 'Date' }, { key: 'ref', label: 'Reference' }, { key: 'a', label: 'Amount', align: 'right' }, { key: 'status', label: 'Status', render: (r) => <Status s={r.status} /> }]} />}
      {tab === 'Historical financing' && <DataTable caption="historical financing" searchable={false} rows={[{ id: 'AG-0012', a: mk(1_000_000), p: 'Jan 2024 – Dec 2024', status: 'Completed' }]} cols={[{ key: 'id', label: 'Agreement' }, { key: 'a', label: 'Amount', align: 'right' }, { key: 'p', label: 'Period' }, { key: 'status', label: 'Status', render: (r) => <Status s={r.status} /> }]} actions={() => <Btn size="sm" variant="outline" onClick={() => toast('Statement downloaded')}>Statement</Btn>} />}
    </div>
  )
}
