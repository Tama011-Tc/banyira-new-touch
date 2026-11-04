import { useState, type ReactNode } from 'react'
import { Badge, Btn, Card, Icon, Logo, Notice, RiskBadge, TextField, Field, inputCls, toast, cn } from './ui'
import { productStore, RISK_INFO, useRules, feePct, mk, type Risk } from './store'

const NAV = [
  ['/', 'Home'], ['/products', 'Our Products'], ['/how-to-get-a-loan', 'How to Get a Loan'], ['/how-to-invest', 'How to Invest'], ['/about', 'About Banyira'], ['/contact', 'Contact'],
]
export const RISK_DISCLOSURE = "Investing involves financial risk. Investors may experience delayed repayments or lose some or all of their invested capital. Banyira's assessment and risk classifications do not guarantee repayment or returns. Investors are responsible for evaluating opportunities and making their own investment decisions."

export function PublicLayout({ path, children }: { path: string; children: ReactNode }) {
  const [open, setOpen] = useState(false)
  return (
    <div>
      <a href="#main" onClick={(e) => { e.preventDefault(); document.getElementById('main')?.focus() }} className="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-3">Skip to content</a>
      <header className="sticky top-0 z-40 border-b border-line bg-paper/95 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-2.5">
          <a href="#/" aria-label="Banyira home"><Logo /></a>
          <nav aria-label="Primary" className="hidden items-center gap-1 xl:flex">
            {NAV.map(([h, l]) => (
              <a key={h} href={'#' + h} aria-current={path === h ? 'page' : undefined} className={cn('rounded-md px-3 py-2 text-[15px] font-medium hover:bg-mint', path === h && 'text-forest underline decoration-maize decoration-2 underline-offset-8')}>{l}</a>
            ))}
          </nav>
          <div className="hidden items-center gap-2 xl:flex">
            <Btn variant="ghost" href="#/auth/login">Login</Btn>
            <Btn variant="primary" href="#/auth/register">Get Started</Btn>
          </div>
          <button aria-label="Menu" aria-expanded={open} onClick={() => setOpen(!open)} className="flex h-11 w-11 items-center justify-center xl:hidden"><Icon n={open ? 'x' : 'menu'} /></button>
        </div>
        {open && (
          <nav aria-label="Mobile" className="border-t border-line bg-paper px-4 pb-4 xl:hidden" onClick={() => setOpen(false)}>
            {NAV.map(([h, l]) => <a key={h} href={'#' + h} className="flex min-h-12 items-center border-b border-line/60 font-medium">{l}</a>)}
            <div className="mt-3 grid grid-cols-2 gap-2"><Btn variant="outline" href="#/auth/login">Login</Btn><Btn href="#/auth/register">Get Started</Btn></div>
          </nav>
        )}
      </header>
      <div id="main" tabIndex={-1} className="outline-none">{children}</div>
      <Footer />
    </div>
  )
}

function Footer() {
  return (
    <footer className="mt-20 bg-forest text-white">
      <div className="mx-auto grid max-w-6xl gap-8 px-4 py-12 md:grid-cols-4">
        <div className="md:col-span-1"><Logo light /><p className="mt-3 text-sm text-white/75">A peer-to-peer financing marketplace connecting Malawian SMEs with individual investors.</p></div>
        <div><h2 className="font-sans text-sm font-bold uppercase tracking-wide text-maize">Financing</h2><ul className="mt-3 space-y-2 text-sm text-white/85"><li><a href="#/products">Our Products</a></li><li><a href="#/how-to-get-a-loan">How to Get a Loan</a></li><li><a href="#/how-to-invest">How to Invest</a></li></ul></div>
        <div><h2 className="font-sans text-sm font-bold uppercase tracking-wide text-maize">Company</h2><ul className="mt-3 space-y-2 text-sm text-white/85"><li><a href="#/about">About Banyira</a></li><li><a href="#/contact">Contact</a></li><li><a href="#/design-system">Design system and prototypes</a></li></ul></div>
        <div><h2 className="font-sans text-sm font-bold uppercase tracking-wide text-maize">Legal</h2><ul className="mt-3 space-y-2 text-sm text-white/85"><li>Terms of Use</li><li>Privacy Policy</li><li>Investor Risk Disclosure</li><li>Complaints Procedure</li></ul></div>
      </div>
      <div className="border-t border-white/15">
        <div className="mx-auto max-w-6xl space-y-2 px-4 py-6 text-xs leading-relaxed text-white/70">
          <p><strong className="text-white">Risk disclosure.</strong> {RISK_DISCLOSURE}</p>
          <p>Applying for financing does not guarantee approval or funding. Interest rates and repayment terms are proposed by investors and agreed with businesses. Demonstration content for design purposes. © Banyira</p>
        </div>
      </div>
    </footer>
  )
}

const Section = ({ id, title, sub, children, tint }: { id?: string; title: string; sub?: string; children: ReactNode; tint?: boolean }) => (
  <section id={id} className={cn('py-14 md:py-20', tint && 'bg-[#efece1]')}>
    <div className="mx-auto max-w-6xl px-4">
      <h2 className="max-w-2xl text-3xl md:text-4xl">{title}</h2>
      {sub && <p className="mt-3 max-w-2xl text-lg text-muted">{sub}</p>}
      <div className="mt-8">{children}</div>
    </div>
  </section>
)
export const RiskDisclosure = () => (
  <Notice tone="risk" title="Risk disclosure">{RISK_DISCLOSURE}</Notice>
)

function Steps({ steps }: { steps: [string, string][] }) {
  return (
    <ol className="grid gap-3 md:grid-cols-2">
      {steps.map(([t, d], i) => (
        <li key={i} className="flex gap-4 rounded-xl border border-line bg-white p-4">
          <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-forest font-bold text-white">{i + 1}</span>
          <div><h3 className="font-sans text-base font-semibold">{t}</h3><p className="text-sm text-muted">{d}</p></div>
        </li>
      ))}
    </ol>
  )
}

const FAQ: [string, string][] = [
  ['Is financing guaranteed if I apply?', 'No. Every application is verified and assessed by Banyira staff. An application does not guarantee approval, and approval does not guarantee funding, because investors choose which businesses to finance.'],
  ['Who sets the interest rate?', 'Investors propose an annual interest rate within the maximum ceiling set by Banyira. Businesses compare offers and accept or reject them.'],
  ['What does a risk rating mean?', 'Ratings from A+ to C are Banyira’s assessment of a business at a point in time. They are not a guarantee that the business will repay.'],
  ['Can an investor lose money?', 'Yes. Repayments can be delayed and investors may lose some or all of their capital. Only invest what you can afford to lose.'],
  ['What fees apply to businesses?', 'A service fee applies, based on the financing amount. Current fee bands are shown in the loan journey and are confirmed before you accept any offer.'],
  ['Is my information secure?', 'Accounts are protected with verified identity, encrypted storage and monitored access. Staff use a separate, protected internal system.'],
]

export function Landing() {
  const [faq, setFaq] = useState<number | null>(0)
  const products = productStore.use().filter((p) => p.active)
  return (
    <>
      <section className="bg-forest text-white">
        <div className="mx-auto grid max-w-6xl gap-10 px-4 py-14 md:grid-cols-[1.2fr_1fr] md:py-24">
          <div>
            <p className="mb-4 inline-flex rounded-full bg-white/10 px-3 py-1 text-sm font-semibold text-maize">Peer-to-peer financing for Malawi</p>
            <h1 className="text-4xl md:text-6xl">Capital for Businesses. Opportunities for Investors.</h1>
            <p className="mt-5 max-w-xl text-lg text-white/85">Banyira connects verified small and medium businesses with individuals who want to finance them. Businesses receive offers from several investors and choose the terms that suit them. Investors choose who to back, how much to offer and at what rate.</p>
            <div className="mt-8 flex flex-wrap gap-3">
              <Btn variant="accent" size="lg" href="#/auth/register/business">Find Business Financing <Icon n="arrow" size={18} /></Btn>
              <Btn variant="outline" size="lg" href="#/auth/register/investor" className="!border-white/60 !bg-transparent !text-white hover:!bg-white/10">Explore Investment Opportunities</Btn>
            </div>
          </div>
          <div className="self-center rounded-2xl bg-white p-5 text-ink" aria-label="Example marketplace listing">
            <p className="text-xs font-semibold uppercase tracking-wide text-muted">Example marketplace listing (illustrative)</p>
            <div className="mt-3 flex items-start justify-between"><div><h3 className="text-xl">Mzuzu Fresh Foods</h3><p className="text-sm text-muted">Food processing · Mzuzu</p></div><RiskBadge r="B" /></div>
            <p className="mt-3 text-sm">Purpose: cold-room equipment</p>
            <div className="mt-4 h-2 rounded-full bg-[#e7e3d6]"><div className="h-2 w-3/5 rounded-full bg-forest-2" /></div>
            <div className="mt-2 flex justify-between text-sm"><span>MK2,700,000 funded</span><span className="text-muted">of MK4,500,000</span></div>
            <p className="mt-4 text-xs text-muted">Rates shown are proposals, not guaranteed returns.</p>
          </div>
        </div>
      </section>

      <Section title="How Banyira works" sub="A clear process with Banyira verifying, assessing and administering every financing.">
        <Steps steps={[
          ['Businesses apply', 'Submit business details and financial records online.'],
          ['Banyira verifies and assesses', 'Our credit team checks documents and assigns a risk class from A+ to C.'],
          ['Approved businesses are listed', 'Only assessed businesses appear on the marketplace.'],
          ['Investors make offers', 'Several investors can each fund a portion, specifying amount, rate and period.'],
          ['Businesses choose', 'Compare offers and accept or reject them.'],
          ['Banyira administers', 'Agreements, repayments, records and reporting are managed on the platform.'],
        ]} />
      </Section>

      <Section tint title="Built for both sides of the marketplace">
        <div className="grid gap-5 md:grid-cols-2">
          <Card title="Benefits to SMEs"><ul className="space-y-2.5 text-[15px]">{['Apply online without queuing at a branch', 'Receive offers from multiple investors', 'Compare amounts, rates and repayment periods side by side', 'Clear service fee shown before you accept', 'Track applications and repayments in one place'].map((t) => <li key={t} className="flex gap-2"><Icon n="check" className="mt-0.5 text-ok" />{t}</li>)}</ul><div className="mt-5"><Btn href="#/how-to-get-a-loan">How to get a loan</Btn></div></Card>
          <Card title="Benefits to investors"><ul className="space-y-2.5 text-[15px]">{['Browse businesses assessed and classified by Banyira', 'Choose how much to offer and at what annual rate', 'Spread capital across several businesses', 'Track repayments and portfolio in your dashboard', 'Secure messaging and shared agreements'].map((t) => <li key={t} className="flex gap-2"><Icon n="check" className="mt-0.5 text-ok" />{t}</li>)}</ul><div className="mt-5"><Btn variant="outline" href="#/how-to-invest">How to invest</Btn></div></Card>
        </div>
      </Section>

      <Section title="Financing products" sub="Choose the product that matches how you plan to use the funds.">
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {products.map((p) => <ProductCard key={p.slug} p={p} />)}
        </div>
      </Section>

      <Section tint title="Industries we support">
        <ul className="flex flex-wrap gap-2">{['Agriculture and agro-processing', 'Retail and wholesale', 'Manufacturing', 'Transport and logistics', 'Hospitality and tourism', 'Fisheries', 'Printing and professional services', 'Construction supplies'].map((i) => <li key={i}><Badge tone="green">{i}</Badge></li>)}</ul>
      </Section>

      <Section title="How credit assessment works" sub="Every business is reviewed by a Banyira credit officer before it can be listed.">
        <div className="grid gap-6 lg:grid-cols-2">
          <ol className="space-y-3">{['Identity and business registration verified', 'Financial records and statements reviewed', 'Repayment capacity scored by a credit officer', 'Risk class assigned and decision approved', 'Approved business published to the marketplace'].map((t, i) => <li key={t} className="flex gap-3 rounded-lg border border-line bg-white p-3"><span className="font-display text-xl text-maize">{i + 1}</span>{t}</li>)}</ol>
          <div>
            <ul className="divide-y divide-line rounded-xl border border-line bg-white">{(Object.keys(RISK_INFO) as Risk[]).map((r) => <li key={r} className="flex items-center gap-4 p-3"><RiskBadge r={r} /><div><p className="font-semibold">{RISK_INFO[r].label}</p><p className="text-sm text-muted">{RISK_INFO[r].text}</p></div></li>)}</ul>
            <p className="mt-3 text-sm text-muted">Risk ratings are assessments, not guarantees of repayment.</p>
          </div>
        </div>
      </Section>

      <Section tint title="Trust and security">
        <div className="grid gap-4 md:grid-cols-4">
          {[['shield', 'Verified participants', 'Businesses and investors complete identity verification.'], ['lock', 'Protected accounts', 'Encrypted data and monitored sign-ins.'], ['doc', 'Documented agreements', 'Every financing is recorded in a formal agreement.'], ['list', 'Audit trail', 'Assessment decisions and rule changes are logged and approved.']].map(([i, t, d]) => <Card key={t}><Icon n={i} className="text-forest" /><h3 className="mt-3 text-lg">{t}</h3><p className="mt-1 text-sm text-muted">{d}</p></Card>)}
        </div>
      </Section>

      <Section title="Frequently asked questions">
        <div className="max-w-3xl divide-y divide-line rounded-xl border border-line bg-white">
          {FAQ.map(([q, a], i) => (
            <div key={q}>
              <h3 className="font-sans"><button aria-expanded={faq === i} onClick={() => setFaq(faq === i ? null : i)} className="flex min-h-14 w-full items-center justify-between gap-4 px-5 text-left text-base font-semibold">{q}<Icon n="plus" className={cn('transition-transform', faq === i && 'rotate-45')} /></button></h3>
              {faq === i && <p className="px-5 pb-5 text-muted">{a}</p>}
            </div>
          ))}
        </div>
        <div className="mt-8"><RiskDisclosure /></div>
      </Section>
    </>
  )
}

function ProductCard({ p }: { p: ReturnType<typeof productStore.get>[number] }) {
  return (
    <a href={`#/products/${p.slug}`} className="group flex flex-col rounded-xl border border-line bg-white p-5 transition-colors hover:border-forest">
      <h3 className="text-xl">{p.name}</h3>
      <p className="mt-2 flex-1 text-[15px] text-muted">{p.short}</p>
      <dl className="mt-4 space-y-1 text-sm"><div className="flex justify-between"><dt className="text-muted">Amount</dt><dd className="font-medium">{p.range}</dd></div><div className="flex justify-between"><dt className="text-muted">Term</dt><dd className="font-medium">{p.term}</dd></div></dl>
      <span className="mt-4 inline-flex items-center gap-1 font-semibold text-forest">View details <Icon n="arrow" size={16} /></span>
    </a>
  )
}

export function Products() {
  const products = productStore.use().filter((p) => p.active)
  return (
    <PageTop title="Our Products" sub="Five financing products for different business needs. Amounts and terms are indicative and depend on assessment, your risk class and investor offers.">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">{products.map((p) => <ProductCard key={p.slug} p={p} />)}</div>
      <FeeTable />
    </PageTop>
  )
}
export function FeeTable() {
  const r = useRules()
  return (
    <div className="mt-12 rounded-xl border border-line bg-white p-5">
      <h2 className="text-2xl">Current fees and limits</h2>
      <p className="mt-1 text-sm text-muted">Set by Banyira and effective from {r.effective} (rules {r.version}). Subject to change with notice.</p>
      <div className="mt-4 grid gap-6 md:grid-cols-3 text-sm">
        <div><h3 className="font-sans font-semibold">Business service fee</h3><ul className="mt-2 space-y-1">{r.feeTiers.map((t, i) => <li key={i}>{t.upTo ? `Below ${mk(t.upTo)}` : `${mk(r.feeTiers[i - 1].upTo || 0)} and above`}: <strong>{t.pct}%</strong></li>)}</ul></div>
        <div><h3 className="font-sans font-semibold">Investor interest ceiling</h3><p className="mt-2">Maximum <strong>{r.ceiling}%</strong> per year.</p></div>
        <div><h3 className="font-sans font-semibold">Risk class limits</h3><ul className="mt-2 space-y-1">{(Object.keys(r.bands) as Risk[]).map((k) => { const b = r.bands[k]; return <li key={k}><strong>{k}:</strong> {b.maxAmount || b.maxMonths ? [b.maxAmount && `max ${mk(b.maxAmount)}`, b.maxMonths && `max ${b.maxMonths} months`].filter(Boolean).join(', ') : 'no additional limits'}</li> })}</ul></div>
      </div>
    </div>
  )
}

const PageTop = ({ title, sub, children }: { title: string; sub?: string; children: ReactNode }) => (
  <>
    <div className="border-b border-line bg-[#efece1]"><div className="mx-auto max-w-6xl px-4 py-12"><h1 className="text-4xl md:text-5xl">{title}</h1>{sub && <p className="mt-3 max-w-2xl text-lg text-muted">{sub}</p>}</div></div>
    <div className="mx-auto max-w-6xl px-4 py-10">{children}</div>
  </>
)

export function ProductPage({ slug }: { slug: string }) {
  const p = productStore.use().find((x) => x.slug === slug && x.active)
  const rules = useRules()
  if (!p) return <PageTop title="Product not available"><p>This product is not currently offered.</p><div className="mt-4"><Btn href="#/products">See all products</Btn></div></PageTop>
  const List = ({ t, items }: { t: string; items: string[] }) => <Card title={t}><ul className="space-y-2 text-[15px]">{items.map((i) => <li key={i} className="flex gap-2"><Icon n="check" className="mt-0.5 text-ok" />{i}</li>)}</ul></Card>
  return (
    <PageTop title={p.name} sub={p.short}>
      <p className="mb-6 text-sm"><a href="#/products" className="text-forest underline">All products</a> / {p.name}</p>
      <div className="mb-6 grid gap-3 sm:grid-cols-3">
        <div className="rounded-xl bg-forest p-4 text-white"><p className="text-sm text-white/70">Indicative amount</p><p className="font-display text-xl">{p.range}</p></div>
        <div className="rounded-xl bg-forest p-4 text-white"><p className="text-sm text-white/70">Repayment period</p><p className="font-display text-xl">{p.term}</p></div>
        <div className="rounded-xl bg-forest p-4 text-white"><p className="text-sm text-white/70">Service fee example (MK2m)</p><p className="font-display text-xl">{feePct(rules, 2_000_000)}% of amount</p></div>
      </div>
      <div className="grid gap-5 md:grid-cols-2">
        <List t="Intended uses" items={p.uses} />
        <List t="Eligibility" items={p.eligibility} />
        <List t="Application requirements" items={p.requirements} />
        <Card title="The financing process"><ol className="space-y-2 text-[15px]">{p.steps.map((s, i) => <li key={s} className="flex gap-3"><span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-mint text-sm font-bold text-forest">{i + 1}</span>{s}</li>)}</ol></Card>
      </div>
      <div className="mt-6"><Notice tone="info">Applying does not guarantee approval or funding. Final amount, rate and term depend on assessment and investor offers.</Notice></div>
      <div className="mt-6 flex flex-wrap gap-3"><Btn size="lg" href="#/auth/register/business">Apply for this financing</Btn><Btn size="lg" variant="outline" href="#/how-to-get-a-loan">See the full journey</Btn></div>
    </PageTop>
  )
}

export function HowLoan() {
  const rules = useRules()
  return (
    <PageTop title="How to Get a Loan" sub="From registration to repayment, here is the complete journey for a business.">
      <Steps steps={[
        ['Register a business account', 'Create your account with your business email and phone.'],
        ['Complete business verification', 'Confirm identity and registration documents.'],
        ['Provide business information', 'Describe operations, owners, purpose and amount needed.'],
        ['Submit financial records', 'Upload statements and supporting documents.'],
        ['Complete credit assessment', 'A Banyira credit officer reviews your application.'],
        ['Receive your outcome and risk class', 'Approved businesses receive a class from A+ to C.'],
        ['Enter the marketplace', 'If approved, your listing is published to investors.'],
        ['Receive investor offers', 'Investors propose amounts, rates and periods.'],
        ['Compare offers and select terms', 'Accept the offers that suit you and reject the rest.'],
        ['Complete financing agreements', 'Review and sign agreements for each accepted offer.'],
        ['Receive financing', 'Funds are paid through the approved disbursement process.'],
        ['Track and fulfil repayments', 'Follow schedules, make payments and download statements.'],
      ]} />
      <div className="mt-6"><Notice tone="warn" title="No guarantee">Submitting an application does not guarantee approval or funding. Even approved businesses are funded only if enough investors make offers you accept.</Notice></div>
      <FeeTable />
      <p className="sr-only">{rules.version}</p>
      <div className="mt-8"><Btn size="lg" variant="accent" href="#/auth/register/business">GET STARTED WITH A LOAN</Btn></div>
    </PageTop>
  )
}
export function HowInvest() {
  return (
    <PageTop title="How to Invest" sub="Back verified Malawian businesses with the amount, rate and period you choose.">
      <div className="mb-8"><RiskDisclosure /></div>
      <Steps steps={[
        ['Register an investor account', 'Sign up with your email and phone.'],
        ['Complete investor verification', 'Provide ID and funding source details.'],
        ['Browse approved businesses', 'Search the marketplace by industry, risk and location.'],
        ['Review profiles and risk assessments', 'Read the business profile and its A+ to C class.'],
        ['Evaluate financing requirements', 'Check amount, purpose, funded portion and period.'],
        ['Submit an investment offer', 'Propose your amount, annual rate and repayment term.'],
        ['Communicate with potential borrowers', 'Ask questions through secure messaging.'],
        ['Complete agreements', 'Sign agreements for accepted offers.'],
        ['Monitor repayments', 'See schedules and repayments received.'],
        ['Manage portfolio performance', 'Review distribution and outstanding balances.'],
      ]} />
      <div className="mt-8"><Btn size="lg" variant="accent" href="#/auth/register/investor">GET STARTED WITH INVESTING</Btn></div>
    </PageTop>
  )
}
export function About() {
  return (
    <PageTop title="About Banyira" sub="We exist to make business finance in Malawi clearer, fairer and more accessible.">
      <div className="grid gap-5 md:grid-cols-3">
        {[['Transparency', 'Fees, limits, risk classes and offers are shown plainly.'], ['Trust', 'Every business is verified and assessed by our team.'], ['Growth', 'Capital reaches productive SMEs; investors diversify across them.']].map(([t, d]) => <Card key={t} title={t}><p className="text-muted">{d}</p></Card>)}
      </div>
      <div className="mt-6 max-w-3xl space-y-3 text-lg text-muted"><p>Banyira is a marketplace operator. We verify businesses, assign risk classes, manage agreements and repayments, and keep records and reporting. Investors, not Banyira, provide the financing capital.</p><p>Our financing rules are set by authorised staff, take effect on a published date, and are approved and recorded before they apply.</p></div>
    </PageTop>
  )
}
export function Contact() {
  const [sent, setSent] = useState(false)
  const [err, setErr] = useState('')
  return (
    <PageTop title="Contact" sub="Questions about financing or investing? Send us a message.">
      <div className="grid gap-8 md:grid-cols-2">
        {sent ? <Notice tone="ok" title="Message sent">Thank you. A member of our team will reply by email within two working days.</Notice> : (
          <form className="space-y-4" onSubmit={(e) => { e.preventDefault(); const f = new FormData(e.currentTarget); if (!String(f.get('email')).includes('@')) return setErr('Enter a valid email address.'); setSent(true); toast('Message sent') }}>
            <TextField label="Full name" name="name" required autoComplete="name" />
            <TextField label="Email" name="email" type="text" inputMode="email" error={err} autoComplete="email" />
            <Field label="I am a">{(a) => <select {...a} className={inputCls}><option>Business owner</option><option>Investor</option><option>Other</option></select>}</Field>
            <Field label="Message">{(a) => <textarea {...a} rows={4} required className={inputCls + ' py-2'} />}</Field>
            <Btn type="submit">Send message</Btn>
          </form>
        )}
        <Card title="Other ways to reach us"><ul className="space-y-3 text-[15px]"><li>Email: support@banyira.example</li><li>Phone / WhatsApp: +265 000 000 000</li><li>Hours: Monday to Friday, 08:00 to 17:00</li><li>Lilongwe, Malawi</li></ul></Card>
      </div>
    </PageTop>
  )
}
