# Implementation roadmap

Mapped to the prompt's twelve phases, with what is already done and what each remaining increment will do. Each increment ends with tests run, a summary and a commit; none touches live-money settings.

| Phase | Scope | State |
|---|---|---|
| 1 Audit & design | Inspect repo, requirements, architecture, risks | **Done** (this document set) |
| 2 Auth & permissions | Roles, Gates, separate dashboards, admin-created staff, forced first password change | Done |
| 3 Business onboarding | Profiles, applications, documents, tracking, sales-assisted flow | Done. Increment 3b: staged onboarding (trading name, ownership, registration status, authorised contact), in-app document-requirement settings |
| 4 Credit assessment | Queue, scoring, approver separation, audit | Done, including configurable risk bands A+/A/B/C (increment 4b, done) |
| 5 Marketplace | Listings, filters, privacy | Done |
| 6 Messaging & negotiation | Screening, appeals, moderation, versioned offers | Done |
| 7 Loan engine | Calculations, agreements, schedules, loan parts | Done. Increment 7b: offer expiry/withdrawal, exposure limits, campaign closure (needs decision D1) |
| 8 Payments & fees | Sandbox, ledger, reconciliation | Done. **Increment 8b: tiered, dated, approval-gated fee schedule (decision D2)** |
| 9 Notifications & SMS | In-app done. SMS provider interface, mock gateway, signed single-use action tokens, delivery log | Not started |
| 10 Reporting | Executive and investor reporting | Done. 10b: fee-schedule and campaign reporting |
| 11 Testing & security review | 131 tests. MFA for staff, upload scanning, dependency audit | Partial |
| 12 Documentation & deploy | README, docs, demo. Screenshots, deployment guide, clean-install check | Partial |
| Education module | Admin-managed articles, English first, Chichewa-ready | Not started (slotted after 9) |
| Gated: live money | Separate approval process | ⛔ |

## Proposed order
8b fee schedule (next, needs decision D2) → 7b offer rules → 9 SMS → education → 3b staged onboarding → 11/12 hardening and screenshots.
