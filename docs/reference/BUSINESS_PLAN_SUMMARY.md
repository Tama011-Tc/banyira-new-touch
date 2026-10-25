# Banyira MicroCredit: business plan summary

Source: the January 2025 Banyira MicroCredit business plan, as supplied by the product owner (sections 1.0 to 4.3). This is a faithful summary for engineers, with the product rules each point drives and where the code enforces it. Market statistics in the plan (FinScope 2011/2012) are historical and are not used by the software.

## What the plan says → what the software does

| Plan | Product rule | Where |
|---|---|---|
| Non-deposit-taking micro-finance institution, web-based peer-to-peer. Considering legal formation; "based on the Microfinance Act 2010"; **not chartered yet** | Never hold deposits or pooled funds. Real money stays disabled until licensing is confirmed with counsel and the regulator | `Platform` operating mode; compliance checklist item "Licensing as a non-deposit-taking microfinance institution" |
| Investors lend directly to SMEs, no middlemen | Marketplace of approved listings | `/marketplace` |
| Investors fund a small proportion, a **loan part**, by **bidding an interest rate**; spread across many businesses | Each accepted offer is its own agreement with its own rate and schedule; min/max investment; exposure caps | `OfferService`, listing limits, `investor.max_total_commitment` |
| Business identity double-checked; experienced staff review all businesses | KYC for businesses, documents, assessment by an analyst, decision by a different approver | `CreditService`, compliance verification |
| Target SMEs with **at least 6 months of financial records** and **at least 1 year of trading** | Screening criteria shown to credit staff; approving a business that misses them needs a written exception | `eligibility.*` settings, `CreditService::eligibility` |
| Risk bands **A+ (very low risk) to C (average risk)** | Four bands; a score below C is **not listable** | `credit.bands`, `NR` |
| No collateral as the main reason for funding | Collateral is not a required document; repayment capacity is assessed instead | document list, scoring |
| SMS to the owner on a bid, showing **amount, rate and repayment period**; owner **accepts or rejects by sending a code**; the platform interprets it | Outbound SMS with the terms and one-use reply codes; inbound reply handled only when sender, code and offer all match | `SmsInboundService`, `/webhooks/sms/inbound` |
| Owner can also accept or decline by logging in | Web acceptance, optionally with an SMS code step-up | `OfferService::accept` |
| Business takes the best rate offered | The owner accepts or declines each offer on its own terms | offers UI |
| Banyira manages **monthly** repayments back to investors using the **bank details each investor provides**; provides account management and reporting | Monthly default; investor payout details (encrypted, masked everywhere) and one payout per confirmed repayment, released by finance | `PayoutService`, `/finance`, portfolio and statements |
| Missed payments: a debt-collection agency chases at a **reasonable fee charged to the borrower** | Overdue and default workflow exists. The collection-referral fee is **not built**: it needs a contract term and a disclosure first | decision D7 |
| Revenue from a **service charge to the business**, **4% to 15% by amount**; **no hidden charges** such as monitoring, review, security or **early repayment** fees | One percentage fee for the business, between 4% and 15%, tiered by amount; investors pay nothing; no fixed or other fee types; early repayment carries no fee | `FeeScheduleService::validatePolicy` |
| Training in financial management and bookkeeping | Education module | `/learn` |
| Profitability after 18 months; finance 1,000+ SMEs; Blantyre first | Executive dashboard shows SMEs financed against the 1,000 target | `targets.smes_financed` |
| Later: Lilongwe/Mzuzu branches, credit reference bureau, debt-collection agency, government funding | Out of scope | none |

## Questions the plan does not answer, and how they were decided
Taken on the product owner's instruction ("use a suggestion"); each is a setting and can be revisited.

1. **Direction of the 4–15% scale.** Higher percentage for **smaller** loans, lower for larger ones (small loans cost more to serve). Demo: 15% under MK 1m, 10% under MK 5m, 4% above. The approved schedule is set by finance with a second approver.
2. **Investor bid ceiling.** Platform maximum **36% a year** (`loan.max_rate_bp`), against informal lenders at 50–100%, and the plan's promise of "very low" rates. Credit approvers set a lower ceiling per listing at approval.
3. **Band C ("average risk").** Tighter limits: at most **MK 3,000,000**, at most **12 months**, and **written conditions** required. Band B: at most 24 months. A+ and A: no extra limits. Limits are edited only through a two-person policy change.
4. **Not yet answered:** the debt-collection fee (D7) and early-payoff quotes (D3).
