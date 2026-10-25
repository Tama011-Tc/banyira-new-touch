# Decisions and assumptions

## Assumptions (unverified)
- **A1. Business plan: now available.** It was supplied on request and is summarised in [reference/BUSINESS_PLAN_SUMMARY.md](reference/BUSINESS_PLAN_SUMMARY.md). Its rules are implemented as settings, not law.
- **A2. Currency** is Malawi kwacha, stored in tambala (integer minor units).
- **A3. Demo figures** (rates, terms, amounts) are illustrative.
- **A4. Product name.** The prompt says "Banyira MicroCredit"; the project and UI use "Banyira MicroCredit". The brand is one setting (`BANYIRA_BRAND`), so changing it is trivial. Needs confirmation.

## Decisions already taken (documented, reversible)
- **Loan parts.** Each accepted offer becomes its own agreement with its own rate, term and schedule. Rates are never averaged and schedules never merged. The business sees every obligation before accepting each offer.
- **Separation of duties.** Analyst ≠ approver; large amounts need an executive; listings need a second person to review; money release needs finance.
- **Fees** are disclosed per offer, snapshotted, and frozen on acceptance. Changes need two people.
- **Messages** are screened server-side; blocked messages can be appealed.
- **Real money is impossible** in this build (operating mode fails closed).
- **Admin oversight** is read-only and audited; documents and private conversations stay closed to IT.

## Decisions taken on the owner's instruction ("just decide")
- **D1. Campaigns.** The business is paid only once a configurable share of the target is funded (`campaign.min_funded_pct`, default **100%**). `banyira:close-campaigns` runs daily: past the deadline, a campaign at or above the minimum closes as *funded* and proceeds; below it, the campaign is *closed*, unpaid agreements are cancelled, and refunds for money already paid in are queued as reversal requests that a second finance person must approve. Nothing moves automatically.
- **D2. Fees (confirmed by the plan).** The plan fixes the model: one service charge to the business, 4% to 15% by amount, nothing else. The software enforces that range, no investor fee, no fixed charges. Below is the mechanism.
- **D2 mechanism.** None are charged unless an **approved fee schedule** is in force. A schedule is tiered by loan amount (percentage and/or fixed amount for the borrower and for the investor), dated, taxed per its own rate, and takes effect only after a second person approves it. Offers snapshot the fees; accepted agreements are frozen. The demo seeds an *illustrative* 4–15% schedule (borrower 15% under MK 1m, 10% under MK 5m, 4% above; investor 0%; provider 1.5% borne by the platform) through the same approval path. Who ultimately pays the plan's 4–15% is unverified.
- **D4. Product name:** **Banyira MicroCredit** (the most recent instruction), set in one place (`BANYIRA_BRAND`).
- **D5. Offers** lapse after 7 days (`offers.ttl_days`). A proposer may withdraw an unanswered proposal. Once both parties accept, the agreement is binding.
- **D6 (revised after reading the plan). SMS replies can accept or decline an offer.** The plan requires owners without internet to do this by SMS. It is allowed only for a **business owner**, only from their registered number, only with the one-use 6-digit code we texted them for that specific offer (valid 48 hours), only for offers up to MK 5,000,000 (larger ones go to the website), and with failed attempts rate-limited per number. The reply performs exactly what the website would, through the same checks, and never completes a deal by itself: the investor's side is still required. The earlier code-only rule remains for the optional web step-up.

## Still open
- **D3 (partly answered).** The plan says **no early-repayment fee**: so early repayment is allowed and free. A settlement-quote feature (reducing interest on early payoff) is not built; today extra payments are applied to later instalments as scheduled. Restructuring still needs a policy.
- **D7.** The plan allows a **debt-collection agency fee charged to the borrower** after missed payments. It must be disclosed in the agreement before acceptance. Not built; no late-payment penalty exists.
- Investor exposure beyond the per-investor caps (sector and business concentration limits).
