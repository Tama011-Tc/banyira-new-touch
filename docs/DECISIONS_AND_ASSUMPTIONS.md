# Decisions and assumptions

## Assumptions (unverified)
- **A1. Business plan not available.** The January 2025 plan could not be found, so its details (fee range 4–15%, 6 months of records, 1 year trading, bands A+/A/B/C, SMS for low-connectivity owners, Blantyre first) come from the prompt. They are configuration, not law, and should be confirmed against the plan.
- **A2. Currency** is Malawi kwacha, stored in tambala (integer minor units).
- **A3. Demo figures** (rates, terms, amounts) are illustrative.
- **A4. Product name.** The prompt says "Banyira MicroCredit"; the project and UI use "Banyira Micro Finance". The brand is one setting (`BANYIRA_BRAND`), so changing it is trivial. Needs confirmation.

## Decisions already taken (documented, reversible)
- **Loan parts.** Each accepted offer becomes its own agreement with its own rate, term and schedule. Rates are never averaged and schedules never merged. The business sees every obligation before accepting each offer.
- **Separation of duties.** Analyst ≠ approver; large amounts need an executive; listings need a second person to review; money release needs finance.
- **Fees** are disclosed per offer, snapshotted, and frozen on acceptance. Changes need two people.
- **Messages** are screened server-side; blocked messages can be appealed.
- **Real money is impossible** in this build (operating mode fails closed).
- **Admin oversight** is read-only and audited; documents and private conversations stay closed to IT.

## Open decisions needing your approval
- **D1. Campaign that does not reach its target.** Today a business can be paid for the loan parts that are funded even if the campaign target is missed. Options: (a) all-or-nothing: nobody is paid unless the target is met, otherwise investors are released; (b) a configurable minimum (for example 70%) after which the business may accept partial funding; (c) keep today's behaviour. **Recommended: (b), defaulting to 100% until the plan says otherwise.** This is a material business decision, so I have not changed it.
- **D2. Fee policy.** Should the platform charge nothing until a fee schedule is explicitly approved (the prompt's "unapproved by default")? Today the default is 2% borrower and 1% investor. **Recommended: yes, no fees until approved, tiered by amount, with effective dates.** The demo seeder would approve a clearly labelled demo schedule.
- **D3. Early repayment and restructuring:** allowed? Penalty? Needs policy and a legal view.
- **D4. Investor exposure limits:** per listing, per business, per sector, and who sets them.
- **D5. Offer lifetime** (default 7 days?) and whether an investor may withdraw after the business has accepted but before funding.
- **D6. SMS provider** and whether replies may confirm actions or only deliver one-time codes the user types into the web app. **Recommended: codes only.**
- **D7. Late fees:** none today. Allowed by law and contract?
