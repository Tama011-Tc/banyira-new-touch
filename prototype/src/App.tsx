import { About, Contact, HowInvest, HowLoan, Landing, ProductPage, Products, PublicLayout } from './public'
import { Auth, session } from './auth'
import { lazy, Suspense } from 'react'
import { Toaster, useHash, Btn, Logo } from './ui'

const Investor = lazy(() => import('./customer').then((m) => ({ default: m.Investor })))
const Business = lazy(() => import('./customer').then((m) => ({ default: m.Business })))
const Staff = lazy(() => import('./staff').then((m) => ({ default: m.Staff })))
const StaffGate = lazy(() => import('./staff').then((m) => ({ default: m.StaffGate })))
const Design = lazy(() => import('./design').then((m) => ({ default: m.Design })))

function NotFound() {
  return <div className="flex min-h-screen flex-col items-center justify-center gap-4 p-6 text-center"><Logo /><h1 className="text-3xl">Page not found</h1><Btn href="#/">Go home</Btn></div>
}

export default function App() {
  const h = useHash()
  const s = session.use()
  const [, a, b, c] = h.split('/')
  let view
  if (a === 'internal') view = b ? <Staff role={b} page={c} /> : <StaffGate />
  else if (a === 'auth') view = <Auth path={h} />
  else if (a === 'investor' || a === 'business') {
    view = s.type === a ? (a === 'investor' ? <Investor page={b} /> : <Business page={b} />) : <Auth path={`/auth/login/${a}`} />
  } else if (a === 'design-system') view = <Design />
  else {
    const path = '/' + (a || '')
    const body = !a ? <Landing /> : a === 'products' ? (b ? <ProductPage slug={b} /> : <Products />) : a === 'how-to-get-a-loan' ? <HowLoan /> : a === 'how-to-invest' ? <HowInvest /> : a === 'about' ? <About /> : a === 'contact' ? <Contact /> : null
    view = body ? <PublicLayout path={path}>{body}</PublicLayout> : <NotFound />
  }
  return <><Suspense fallback={<p className="p-8" role="status">Loading…</p>}>{view}</Suspense><Toaster /></>
}
