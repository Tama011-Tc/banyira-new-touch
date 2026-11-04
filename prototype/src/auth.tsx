import { useState, type ReactNode } from 'react'
import { Btn, Icon, Logo, Notice, SelectField, TextField, Field, inputCls, toast, cn, Progress } from './ui'
import { createStore } from './store'

export const session = createStore<{ type: 'investor' | 'business' | null }>({ type: null })
type T = 'investor' | 'business'
const go = (h: string) => { location.hash = h }

function AuthFrame({ title, sub, children, back }: { title: string; sub?: string; children: ReactNode; back?: string }) {
  return (
    <div className="grid min-h-screen lg:grid-cols-[420px_1fr]">
      <aside className="hidden flex-col justify-between bg-forest p-10 text-white lg:flex">
        <a href="#/"><Logo light /></a>
        <div><h2 className="text-3xl">Capital for Businesses. Opportunities for Investors.</h2><ul className="mt-6 space-y-3 text-white/85"><li className="flex gap-2"><Icon n="shield" className="text-maize" /> Verified businesses and investors</li><li className="flex gap-2"><Icon n="lock" className="text-maize" /> Encrypted and monitored accounts</li><li className="flex gap-2"><Icon n="doc" className="text-maize" /> Clear agreements and records</li></ul></div>
        <p className="text-xs text-white/60">Investing involves risk, including loss of capital.</p>
      </aside>
      <main id="main" className="flex items-start justify-center px-4 py-8 sm:py-14">
        <div className="w-full max-w-md">
          <div className="mb-6 flex items-center justify-between lg:hidden"><a href="#/"><Logo /></a></div>
          {back && <a href={'#' + back} className="mb-4 inline-flex min-h-10 items-center gap-1 text-sm font-semibold text-forest">&larr; Back</a>}
          <h1 className="text-3xl">{title}</h1>
          {sub && <p className="mt-2 text-muted">{sub}</p>}
          <div className="mt-6">{children}</div>
        </div>
      </main>
    </div>
  )
}

function TypeCards({ base }: { base: string }) {
  return (
    <div className="space-y-3">
      {([['business', 'store', 'I am a business', 'Apply for financing and receive offers from investors.'], ['investor', 'wallet', 'I am an investor', 'Browse approved businesses and make offers.']] as const).map(([t, i, h, d]) => (
        <a key={t} href={`#${base}/${t}`} className="flex items-center gap-4 rounded-xl border border-line bg-white p-4 hover:border-forest">
          <span className="flex h-12 w-12 items-center justify-center rounded-lg bg-mint text-forest"><Icon n={i} /></span>
          <span className="flex-1"><span className="block font-semibold">{h}</span><span className="text-sm text-muted">{d}</span></span>
          <Icon n="chev" className="text-muted" />
        </a>
      ))}
    </div>
  )
}

export function Auth({ path }: { path: string }) {
  const [, , mode, type] = path.split('/') as [string, string, string, T | undefined]
  const t = type === 'business' || type === 'investor' ? type : undefined
  if (mode === 'login') return t ? <Login t={t} /> : <AuthFrame title="Welcome back" sub="First, tell us which account you use." back="/"><TypeCards base="/auth/login" /><p className="mt-6 text-sm text-muted">New to Banyira? <a className="font-semibold text-forest underline" href="#/auth/register">Create an account</a></p></AuthFrame>
  if (mode === 'register') return t ? <Register t={t} /> : <AuthFrame title="Create your account" sub="Choose the account type that fits you. You can’t change this later." back="/"><TypeCards base="/auth/register" /><p className="mt-6 text-sm text-muted">Already registered? <a className="font-semibold text-forest underline" href="#/auth/login">Log in</a></p></AuthFrame>
  if (mode === 'verify' && t) return <Verify t={t} />
  if (mode === 'onboarding' && t) return <Onboarding t={t} />
  if (mode === 'forgot' && t) return <Forgot t={t} />
  return <AuthFrame title="Page not found"><Btn href="#/auth/login">Go to login</Btn></AuthFrame>
}

function Login({ t }: { t: T }) {
  const [err, setErr] = useState('')
  const [show, setShow] = useState(false)
  const [busy, setBusy] = useState(false)
  return (
    <AuthFrame title={`${t === 'business' ? 'Business' : 'Investor'} login`} back="/auth/login">
      <form className="space-y-4" onSubmit={(e) => {
        e.preventDefault()
        const f = new FormData(e.currentTarget)
        if (!String(f.get('email')).includes('@') || String(f.get('pw')).length < 1) return setErr('Check your email and password, then try again.')
        setBusy(true); setTimeout(() => { session.set({ type: t }); go(`/${t}/overview`) }, 500)
      }}>
        {err && <Notice tone="warn" title="Could not sign in">{err}</Notice>}
        <TextField label="Email address" name="email" inputMode="email" autoComplete="email" defaultValue={t === 'business' ? 'owner@mzuzufresh.example' : 'investor@example.com'} />
        <Field label="Password">{(a) => <div className="relative"><input {...a} name="pw" type={show ? 'text' : 'password'} defaultValue="demo-password" autoComplete="current-password" className={cn(inputCls, 'pr-20')} /><button type="button" onClick={() => setShow(!show)} className="absolute right-1 top-1 min-h-9 px-3 text-sm font-semibold text-forest">{show ? 'Hide' : 'Show'}</button></div>}</Field>
        <div className="text-right"><a className="text-sm font-semibold text-forest underline" href={`#/auth/forgot/${t}`}>Forgot password?</a></div>
        <Btn type="submit" full disabled={busy}>{busy ? 'Signing in…' : 'Log in'}</Btn>
      </form>
      <p className="mt-6 text-sm text-muted">No account? <a className="font-semibold text-forest underline" href={`#/auth/register/${t}`}>Register as {t === 'business' ? 'a business' : 'an investor'}</a></p>
    </AuthFrame>
  )
}

function Register({ t }: { t: T }) {
  const [errs, setErrs] = useState<Record<string, string>>({})
  const biz = t === 'business'
  return (
    <AuthFrame title={biz ? 'Register your business' : 'Register as an investor'} sub="Step 1 of 3: create your account." back="/auth/register">
      <form noValidate className="space-y-4" onSubmit={(e) => {
        e.preventDefault()
        const f = Object.fromEntries(new FormData(e.currentTarget)) as Record<string, string>
        const er: Record<string, string> = {}
        if (!f.name?.trim()) er.name = biz ? 'Enter the business name.' : 'Enter your full name.'
        if (!f.email?.includes('@')) er.email = 'Enter a valid email address.'
        if (!/^\+?\d{9,13}$/.test((f.phone || '').replace(/\s/g, ''))) er.phone = 'Enter a phone number, e.g. 0999 000 000.'
        if ((f.pw || '').length < 8) er.pw = 'Use at least 8 characters.'
        if (!f.terms) er.terms = 'You must accept the terms to continue.'
        if (!biz && !f.risk) er.risk = 'Please confirm you understand the investment risks.'
        setErrs(er)
        if (!Object.keys(er).length) go(`/auth/verify/${t}`)
      }}>
        <TextField label={biz ? 'Registered business name' : 'Full name'} name="name" error={errs.name} />
        {biz && <SelectField label="Business type" options={['Sole trader', 'Partnership', 'Private limited company', 'Cooperative']} />}
        {biz && <SelectField label="Industry" options={['Agriculture', 'Retail', 'Manufacturing', 'Transport', 'Hospitality', 'Services']} />}
        {!biz && <SelectField label="Investor type" options={['Individual', 'Joint account', 'Family trust']} />}
        <TextField label="Email address" name="email" inputMode="email" error={errs.email} autoComplete="email" />
        <TextField label="Mobile phone" name="phone" inputMode="tel" error={errs.phone} hint="Used for verification codes." autoComplete="tel" />
        <TextField label="Create password" name="pw" type="password" error={errs.pw} hint="At least 8 characters." autoComplete="new-password" />
        <label className="flex items-start gap-3 text-sm"><input type="checkbox" name="terms" className="mt-1 h-5 w-5" />I accept the Terms of Use and Privacy Policy.</label>
        {errs.terms && <p className="text-sm text-danger">{errs.terms}</p>}
        {!biz && <><Notice tone="risk">Investing involves financial risk. You may lose some or all of your capital. Ratings do not guarantee repayment.</Notice><label className="flex items-start gap-3 text-sm"><input type="checkbox" name="risk" className="mt-1 h-5 w-5" />I understand the risks and will make my own decisions.</label>{errs.risk && <p className="text-sm text-danger">{errs.risk}</p>}</>}
        <Btn type="submit" full>Continue</Btn>
      </form>
    </AuthFrame>
  )
}

function Verify({ t }: { t: T }) {
  const [code, setCode] = useState('')
  const [err, setErr] = useState('')
  return (
    <AuthFrame title="Verify your phone" sub="Step 2 of 3: we sent a 6-digit code by SMS. For this demonstration, use 123456.">
      <form className="space-y-4" onSubmit={(e) => { e.preventDefault(); if (code !== '123456') return setErr('That code is not correct. Check the SMS and try again.'); toast('Phone verified'); go(`/auth/onboarding/${t}`) }}>
        <TextField label="Verification code" inputMode="numeric" maxLength={6} value={code} onChange={(e) => setCode(e.target.value)} error={err} autoComplete="one-time-code" />
        <Btn type="submit" full>Verify</Btn>
        <button type="button" onClick={() => toast('A new code was sent')} className="min-h-11 w-full text-sm font-semibold text-forest underline">Resend code</button>
      </form>
    </AuthFrame>
  )
}

function Onboarding({ t }: { t: T }) {
  const [step, setStep] = useState(0)
  const biz = t === 'business'
  const steps = biz ? ['Business details', 'Owner identity', 'Bank or mobile money'] : ['Identity', 'Funding source', 'Investment preferences']
  const body = biz
    ? [<><TextField label="Business registration number" /><TextField label="Physical address" /><SelectField label="Years operating" options={['Under 1', '1 to 2', '2 to 5', 'Over 5']} /></>, <><TextField label="Owner national ID number" /><Field label="Upload ID photo" hint="JPG or PDF, up to 2 MB.">{(a) => <input {...a} type="file" className={inputCls + ' py-2'} />}</Field></>, <><SelectField label="Account type" options={['Bank account', 'Mobile money']} /><TextField label="Account number" inputMode="numeric" /></>]
    : [<><TextField label="National ID number" /><Field label="Upload ID photo" hint="JPG or PDF, up to 2 MB.">{(a) => <input {...a} type="file" className={inputCls + ' py-2'} />}</Field></>, <><SelectField label="Source of funds" options={['Salary', 'Business income', 'Savings', 'Other']} /><SelectField label="Payout account" options={['Bank account', 'Mobile money']} /><TextField label="Account number" inputMode="numeric" /></>, <><SelectField label="Industries of interest" options={['All industries', 'Agriculture', 'Retail', 'Transport', 'Hospitality']} /><SelectField label="Preferred risk classes" options={['All classes', 'A+ and A only', 'A+ to B']} /></>]
  return (
    <AuthFrame title="Complete your profile" sub={`Step 3 of 3: ${steps[step]} (${step + 1}/${steps.length})`}>
      <Progress value={((step + 1) / steps.length) * 100} label="Onboarding progress" />
      <form className="mt-5 space-y-4" onSubmit={(e) => { e.preventDefault(); if (step < 2) return setStep(step + 1); session.set({ type: t }); toast('Verification submitted. We will review it within 2 working days.'); go(`/${t}/overview`) }}>
        {body[step]}
        <div className="flex gap-2">{step > 0 && <Btn variant="outline" onClick={() => setStep(step - 1)}>Back</Btn>}<Btn type="submit" className="flex-1">{step < 2 ? 'Save and continue' : 'Submit for verification'}</Btn></div>
        <button type="button" className="min-h-11 w-full text-sm font-semibold text-forest underline" onClick={() => { session.set({ type: t }); go(`/${t}/overview`) }}>Finish later</button>
      </form>
    </AuthFrame>
  )
}

function Forgot({ t }: { t: T }) {
  const [st, setSt] = useState<'ask' | 'sent' | 'reset' | 'done'>('ask')
  return (
    <AuthFrame title="Reset your password" back={`/auth/login/${t}`}>
      {st === 'ask' && <form className="space-y-4" onSubmit={(e) => { e.preventDefault(); setSt('sent') }}><p className="text-muted">Enter your email and we will send a reset link.</p><TextField label="Email address" inputMode="email" /><Btn type="submit" full>Send reset link</Btn></form>}
      {st === 'sent' && <div className="space-y-4"><Notice tone="ok" title="Check your email">If an account exists for that address, a reset link is on its way. It expires in 30 minutes.</Notice><Btn full variant="outline" onClick={() => setSt('reset')}>Demo: open the reset link</Btn></div>}
      {st === 'reset' && <form className="space-y-4" onSubmit={(e) => { e.preventDefault(); setSt('done') }}><TextField label="New password" type="password" hint="At least 8 characters." /><TextField label="Confirm new password" type="password" /><Btn type="submit" full>Set new password</Btn></form>}
      {st === 'done' && <div className="space-y-4"><Notice tone="ok" title="Password updated">You can now log in with your new password.</Notice><Btn full href={`#/auth/login/${t}`}>Go to login</Btn></div>}
    </AuthFrame>
  )
}
