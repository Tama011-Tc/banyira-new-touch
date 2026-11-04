import { useSyncExternalStore } from 'react'

export function createStore<T>(init: T) {
  let s = init
  const l = new Set<() => void>()
  return {
    get: () => s,
    set: (n: T | ((p: T) => T)) => {
      s = typeof n === 'function' ? (n as (p: T) => T)(s) : n
      l.forEach((f) => f())
    },
    use: () =>
      useSyncExternalStore(
        (cb) => {
          l.add(cb)
          return () => l.delete(cb)
        },
        () => s,
      ),
  }
}

export type Risk = 'A+' | 'A' | 'B' | 'C'
export const RISK_INFO: Record<Risk, { label: string; text: string }> = {
  'A+': { label: 'Very low risk', text: 'Strongest assessed repayment capacity.' },
  A: { label: 'Low risk', text: 'Strong assessed repayment capacity.' },
  B: { label: 'Moderate risk', text: 'Adequate capacity with some watch points.' },
  C: { label: 'Average risk', text: 'Acceptable capacity with tighter limits applied.' },
}

// ---- Configurable financing rules (effective-dated, approval workflow, history) ----
export type Rules = {
  version: string
  effective: string
  feeTiers: { upTo: number | null; pct: number }[]
  ceiling: number
  bands: Record<Risk, { maxAmount: number | null; maxMonths: number | null }>
}
export type RuleRecord = { rules: Rules; status: 'Active' | 'Superseded' | 'Pending approval' | 'Rejected'; by: string; approvedBy?: string; note: string }

const baseRules: Rules = {
  version: 'R-2025.1',
  effective: '2025-01-01',
  feeTiers: [
    { upTo: 1_000_000, pct: 15 },
    { upTo: 5_000_000, pct: 10 },
    { upTo: null, pct: 4 },
  ],
  ceiling: 36,
  bands: {
    'A+': { maxAmount: null, maxMonths: null },
    A: { maxAmount: null, maxMonths: null },
    B: { maxAmount: null, maxMonths: 24 },
    C: { maxAmount: 3_000_000, maxMonths: 12 },
  },
}
export const ruleStore = createStore<RuleRecord[]>([
  { rules: baseRules, status: 'Active', by: 'IT Administrator', approvedBy: 'Executive Management', note: 'Initial demonstration rules' },
])
export const activeRules = (h: RuleRecord[]) => h.find((r) => r.status === 'Active')!.rules
export const useRules = () => activeRules(ruleStore.use())
export function feePct(rules: Rules, amount: number) {
  for (const t of rules.feeTiers) if (t.upTo === null || amount < t.upTo) return t.pct
  return rules.feeTiers[rules.feeTiers.length - 1].pct
}

// ---- Products (administrator-configurable) ----
export type Product = {
  slug: string; name: string; short: string; range: string; term: string; active: boolean
  uses: string[]; eligibility: string[]; requirements: string[]; steps: string[]
}
const commonReq = ['Business registration certificate', 'Owner national ID', 'Bank or mobile money statements (6 months)', 'Proof of business address']
export const productStore = createStore<Product[]>([
  { slug: 'short-term', name: 'Short-Term Business Financing', short: 'Immediate working capital and cash-flow needs.', range: 'MK300,000 – MK3,000,000', term: '1 – 12 months', active: true,
    uses: ['Bridging a delayed customer payment', 'Seasonal cash-flow gaps', 'Urgent supplier or utility payments'],
    eligibility: ['Operating for at least 6 months', 'Registered or formally recognised business', 'Verifiable sales through bank or mobile money'], requirements: commonReq,
    steps: ['Apply online', 'Verification', 'Credit assessment', 'Marketplace offers', 'Agreement and disbursement'] },
  { slug: 'working-capital', name: 'Working Capital Financing', short: 'Stock, suppliers and day-to-day operational requirements.', range: 'MK500,000 – MK5,000,000', term: '3 – 18 months', active: true,
    uses: ['Buying stock in bulk', 'Paying suppliers and wages', 'Covering operating costs between sales cycles'],
    eligibility: ['Operating for at least 12 months', 'Consistent monthly turnover', 'No unresolved defaults on record'], requirements: [...commonReq, 'Stock or sales records'],
    steps: ['Apply online', 'Verification', 'Credit assessment', 'Marketplace offers', 'Agreement and disbursement'] },
  { slug: 'expansion', name: 'Business Expansion Financing', short: 'New branches, production growth and market expansion.', range: 'MK2,000,000 – MK15,000,000', term: '12 – 36 months', active: true,
    uses: ['Opening a new branch', 'Increasing production capacity', 'Entering a new market or product line'],
    eligibility: ['Operating for at least 18 months', 'Clear expansion plan and budget', 'Financial statements or management accounts'], requirements: [...commonReq, 'Expansion plan and budget', 'Management accounts'],
    steps: ['Apply online', 'Verification', 'Credit assessment', 'Marketplace offers', 'Agreement and disbursement'] },
  { slug: 'long-term', name: 'Long-Term Business Financing', short: 'Larger, longer-term commercial requirements.', range: 'MK5,000,000 – MK30,000,000', term: '24 – 48 months', active: true,
    uses: ['Property improvements', 'Large commercial contracts', 'Refinancing costly short-term debt'],
    eligibility: ['Operating for at least 24 months', 'Audited or reviewed accounts preferred', 'Demonstrated multi-year repayment capacity'], requirements: [...commonReq, 'Annual financial statements', 'Tax compliance documents'],
    steps: ['Apply online', 'Verification', 'Credit assessment', 'Marketplace offers', 'Agreement and disbursement'] },
  { slug: 'equipment', name: 'Equipment and Machinery Financing', short: 'Heavy machinery, vehicles and productive assets.', range: 'MK1,000,000 – MK25,000,000', term: '12 – 48 months', active: true,
    uses: ['Milling and processing machinery', 'Delivery vehicles', 'Agricultural and construction equipment'],
    eligibility: ['Operating for at least 12 months', 'Supplier quotation for the asset', 'Asset is directly productive for the business'], requirements: [...commonReq, 'Supplier quotation or pro-forma invoice', 'Asset details and expected use'],
    steps: ['Apply online', 'Verification', 'Credit assessment', 'Marketplace offers', 'Agreement and disbursement'] },
])

// ---- Marketplace + offers ----
export type Opp = { id: string; name: string; industry: string; location: string; desc: string; purpose: string; amount: number; funded: number; risk: Risk; rate: number; months: number; status: string }
export const opps: Opp[] = [
  { id: 'o1', name: 'Mzuzu Fresh Foods', industry: 'Food processing', location: 'Mzuzu, Northern Region', desc: 'Packs and distributes vegetables and fruit to supermarkets and lodges.', purpose: 'Cold-room equipment', amount: 4_500_000, funded: 700_000, risk: 'B', rate: 28, months: 18, status: 'Open' },
  { id: 'o2', name: 'Lilongwe Print House', industry: 'Printing and services', location: 'Lilongwe, Central Region', desc: 'Commercial printing for schools, NGOs and local businesses.', purpose: 'Digital press purchase', amount: 6_000_000, funded: 5_400_000, risk: 'A', rate: 22, months: 24, status: 'Almost funded' },
  { id: 'o3', name: 'Zomba Hardware & Supplies', industry: 'Retail', location: 'Zomba, Southern Region', desc: 'Building materials and hardware shop serving contractors.', purpose: 'Bulk stock purchase', amount: 2_000_000, funded: 400_000, risk: 'C', rate: 32, months: 9, status: 'Open' },
  { id: 'o4', name: 'Shire Valley Agro', industry: 'Agriculture', location: 'Nsanje, Southern Region', desc: 'Smallholder produce aggregation and storage.', purpose: 'Seasonal working capital', amount: 3_500_000, funded: 0, risk: 'B', rate: 30, months: 12, status: 'Open' },
  { id: 'o5', name: 'Blantyre Logistics Co.', industry: 'Transport', location: 'Blantyre, Southern Region', desc: 'Regional goods delivery for small retailers.', purpose: 'Two delivery vehicles', amount: 12_000_000, funded: 9_000_000, risk: 'A+', rate: 18, months: 36, status: 'Open' },
  { id: 'o6', name: 'Kasungu Tailoring Collective', industry: 'Manufacturing', location: 'Kasungu, Central Region', desc: 'Uniform manufacture for schools and security firms.', purpose: 'Industrial sewing machines', amount: 1_800_000, funded: 1_800_000, risk: 'B', rate: 26, months: 15, status: 'Fully funded' },
  { id: 'o7', name: 'Salima Lakeside Lodge', industry: 'Hospitality', location: 'Salima, Central Region', desc: 'Small lakeshore lodge with restaurant and conference room.', purpose: 'Room refurbishment', amount: 5_000_000, funded: 1_000_000, risk: 'A', rate: 24, months: 24, status: 'Open' },
  { id: 'o8', name: 'Mangochi Fish Traders', industry: 'Fisheries', location: 'Mangochi, Southern Region', desc: 'Fish drying, packing and trade with urban markets.', purpose: 'Drying racks and cold storage', amount: 2_500_000, funded: 750_000, risk: 'C', rate: 34, months: 12, status: 'Open' },
]
export type Offer = { id: string; oppId: string; investor: string; amount: number; rate: number; months: number; status: 'Pending' | 'Accepted' | 'Rejected' | 'Withdrawn'; date: string }
export const offerStore = createStore<Offer[]>([
  { id: 'F-1021', oppId: 'o1', investor: 'Investor 4821', amount: 1_000_000, rate: 24, months: 18, status: 'Pending', date: '2025-06-02' },
  { id: 'F-1022', oppId: 'o1', investor: 'Investor 2290', amount: 600_000, rate: 27, months: 12, status: 'Pending', date: '2025-06-03' },
  { id: 'F-1023', oppId: 'o1', investor: 'Investor 7713', amount: 500_000, rate: 22, months: 24, status: 'Pending', date: '2025-06-04' },
  { id: 'F-1020', oppId: 'o1', investor: 'Investor 1180', amount: 700_000, rate: 28, months: 18, status: 'Accepted', date: '2025-05-29' },
  { id: 'F-0991', oppId: 'o5', investor: 'You', amount: 2_000_000, rate: 18, months: 36, status: 'Accepted', date: '2025-04-11' },
  { id: 'F-1030', oppId: 'o2', investor: 'You', amount: 500_000, rate: 21, months: 24, status: 'Pending', date: '2025-06-01' },
])

export const mk = (n: number) => 'MK' + Math.round(n).toLocaleString('en-US')
