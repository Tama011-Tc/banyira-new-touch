# Business rules and calculations

**Units.** Amounts are integer tambala. Rates are annual basis points.

**Flat.** `interest = principal × annual rate × months/12`, split evenly over all instalments; principal split evenly over non-grace instalments; the last instalment takes the rounding remainder.

**Reducing balance.** Periodic rate `r = annual/periods per year`. Level payment `P·r / (1 − (1+r)^−n)` rounded half up; each period `interest = round(balance × r)`; the last instalment clears the balance. Zero rate splits evenly.

**Grace.** The first *N* instalments (max 3) are interest-only.

**Weekly** schedules use `floor(months × 52/12)` instalments.

**Fees** (defaults, configurable with two-person approval; snapshotted on every offer version and frozen on the agreement):
- Borrower origination 2.00% of principal, deducted from the amount released.
- Investor service 1.00% of principal, added to the funding payment.
- Provider processing 1.50% of each payment, borne by the platform.
- Tax on platform fees: 0% by default; set per tax advice before any live use.

Rounding for fees: half up to the tambala, tax computed on each rounded fee.

**Repayment allocation.** Oldest instalment first; interest before principal; excess prepays later instalments; anything beyond the total owed is held as `unapplied` and shown in the ledger.

**Overdue.** An unpaid instalment whose due date has passed. **Default** requires at least 90 days past due (configurable) *and* a named credit approver with a written reason; it is never automatic.

**Discounts.** None are offered in this product; the equivalent is negotiation of rate and fees within the approved ceiling.

**Revenue vs cash.** Platform revenue is fee income; investor interest is not platform revenue.
