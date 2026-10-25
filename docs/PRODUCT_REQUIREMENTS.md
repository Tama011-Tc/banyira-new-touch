# Product requirements and status

Source: the "Banyira MicroCredit" master prompt (peer-to-peer SME lending for Malawi) and the January 2025 business plan it cites. **The business plan was not available in the repository or on this machine**, so figures attributed to it (fee range 4–15%, six months of records, one year trading, risk bands A+/A/B/C) are taken from the prompt, recorded as *unverified assumptions* in [DECISIONS_AND_ASSUMPTIONS.md](DECISIONS_AND_ASSUMPTIONS.md), and implemented as configuration, not constants.

Legend: ✅ built and tested · 🟡 partly built · ⬜ not started · ⛔ deliberately not built (needs legal/provider approval).

## Product
A marketplace where verified investors fund SMEs. A business is assessed by credit staff, listed only after approval, and may be funded by several investors, each negotiating their own loan part. Banyira earns disclosed fees. Investors' capital is at risk and no return is guaranteed.

## Requirement matrix

| # | Area (prompt §) | Status | Notes |
|---|---|---|---|
| 4.1 | Investor: profile, KYC, browse/filter, watchlist, offers, portfolio, statements | ✅ | KYC is manual review; no vendor integration. Exposure limits: profile field exists, **not enforced** 🟡 |
| 4.2 | Business owner: apply, documents, status, respond, offers, repayments | ✅ | Single-page application form; staged onboarding 🟡. Bookkeeping resources ⬜ |
| 4.3 | Sales: leads, assisted registration with consent, upload on behalf, follow-ups, refer, owner invitation | ✅ | |
| 4.4 | Credit analyst: queue, assessment, scoring, info requests, case ownership | ✅ | Configurable weights and risk bands |
| 4.5 | Credit approver with separation of duties and executive threshold | ✅ | |
| 4.6 | Executive dashboards | ✅ | |
| 4.7 | Finance & compliance: reconcile, fees, reversals, disbursement approval | ✅ | Restructuring and recovery records ⬜ |
| 4.8 | IT admin without power to alter financial history; read-only oversight | ✅ | |
| 5 | Application lifecycle and documents | ✅ | Document requirements live in config, not editable in-app 🟡 |
| 6 | Credit framework, evidence, risk bands | ✅ | Bands A+/A/B/C with names, definitions and score thresholds, changed only by a two-person approval; existing assessments keep their band |
| 7 | Marketplace (search, filters, listing detail, privacy) | ✅ | |
| 8 | Multi-investor loan parts, offers, versions, limits | 🟡 | Each accepted offer is its own loan agreement with its own rate and schedule (no averaging). Offer expiry/withdrawal, investor exposure limits, campaign-deadline closure ⬜. Campaign minimum and daily closing implemented (decision D1) |
| 9 | Protected messaging, contact-detail screening, moderation, versioned offers | ✅ | |
| 10 | Loan engine (flat, reducing, grace), allocation, overdue, default | ✅ | Early repayment, restructuring, recoveries ⬜ |
| 11 | Configurable fee schedule, **unapproved by default**, tiers, effective dates | ✅ | Tiered (percentage and fixed), dated, tax, two-person approval; no fee without an approved schedule |
| 12 | Payments: provider-independent, signed webhooks, idempotency, reconciliation, reversals | ✅ | Sandbox only ⛔ for live |
| 13 | SMS: provider layer, mock, secure action confirmation | ⬜ | |
| 14 | Financial-education module | ⬜ | |
| 15 | Database, ledger, audit | ✅ | Append-only ledger and audit enforced by triggers |
| 16 | Security | 🟡 | Staff MFA ⬜; antivirus on uploads ⬜ |
| 17 | Compliance checklist and restricted demo mode | 🟡 | Checklist exists; responsible person, due dates, outstanding actions ⬜ |
| 18 | UI/branding (navy, emerald, gold; responsive) | ✅ | Chichewa strings ⬜ |
| 19 | Tests | ✅ | 131 tests; no browser end-to-end tests |
| 21 | Deliverables | 🟡 | Screenshots ⬜; API docs: webhook only |

## Non-goals (until approved)
Live money, escrow, wallets, pooled funds, automatic disbursement, and any claim of regulatory approval.
