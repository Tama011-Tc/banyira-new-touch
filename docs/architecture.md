# Architecture

Laravel 12 · Vue 3 + Inertia · Tailwind · MySQL. Domain logic lives in `app/Services`, not controllers.

| Service | Responsibility |
|---|---|
| `ApplicationWorkflow` | The state machine: allowed transitions, who may make each, submission completeness. |
| `CreditService` | Scoring suggestion, assessment, decision (separation of duties, thresholds, rate ceiling). |
| `ListingService` | Prepare → independent review → publish; the public (non-confidential) projection. |
| `MessagingService` + `Support\MessageModerator` | Participants, screening, appeals, moderator resolution, reports. |
| `OfferService` / `AgreementService` | Versioned offers, acceptance, agreement + schedule creation. |
| `Support\LoanCalculator`, `Support\Money`, `FeeService` | Decimal-safe maths. |
| `PaymentService` + `SandboxProvider` | Initiation, signed-webhook handling, verification, funding, disbursement, reversal. |
| `RepaymentService` | Allocation, schedule status, overdue/complete, contractual default. |
| `LedgerService` | Balanced, append-only postings and reversals. |
| `AuditLogger` | Hash-chained audit trail. |
| `ApprovalService` | Four-eyes requests (fee changes, reversals). |
| `Platform` | Operating mode; real-money operation fails closed. |
| `ReportingService` | Executive and investor reporting. |

**Access control.** `Role::ABILITIES` is the single permission map; each ability is registered as a Gate and applied with `can:` route middleware. Resources owned by a party are looked up through that party (applications via the owner's businesses, conversations via participation, agreements via investor/borrower), so another party's record is a 404, not a 403. Role and status are not mass-assignable.

**Money.** Integer tambala everywhere; rates in basis points; `bcmath` for fractions; round half up; no floats.

**Separation of records.** Proposed terms (`offer_versions`) → agreed contract (`loan_agreements`) → investor commitment (`investment_commitments`) → payment attempt (`payment_attempts`, unconfirmed) → confirmed movement (`ledger_entries`, only after verified).
