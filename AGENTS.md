# Banyira

Peer-to-peer SME financing marketplace for Malawi. VILT stack: Laravel 13, Inertia v3, Vue 3 + TypeScript, Tailwind CSS v4, MySQL 8.

## Run locally (Laragon on Windows)

```sh
composer install && npm install
cp .env.example .env && php artisan key:generate      # DB: banyira4 on 127.0.0.1, root, no password
php artisan migrate:fresh --seed                       # reference data + demo fixtures (local only)
npm run build                                          # or: npm run dev (Vite HMR)
php artisan serve                                      # http://localhost:8000
php artisan queue:work                                 # SMS jobs (optional in dev; SMS driver is "log")
php artisan schedule:work                              # rule activation, overdue flags, offer expiry, reminders
```

Tests use the `banyira4_test` MySQL database: `php artisan test`. Type-check Vue: `npm run typecheck`.

### Demo accounts (DemoSeeder, never in production)

- Business: `owner@mzuzufresh.example` (open listing with offers), `owner@lilongweprint.example` (disbursed, repaying) · password `demo1234`
- Investor: `investor@example.com` · `demo1234`
- Staff at `/internal-access` (not linked publicly) · password `Staff#Banyira2026` · MFA is enrolled at first sign-in with any authenticator app. `php artisan banyira:staff-code <email>` prints the current code locally.
  `tadala.phiri@` credit · `kondwani.mwale@` IT · `mercy.chirwa@` sales · `blessings.nkhoma@` and `chimwemwe.gondwe@` finance (two, for maker-checker) · `grace.kachale@` executive — all `@banyira.internal`.
- Phone verification codes appear on the page in local development (SMS driver `log`).

## Structure

- `app/Services/` — all business rules. Controllers stay thin.
  - `ApplicationWorkflow` — the only place application status changes; allowed transitions are listed there.
  - `AssessmentService` — weighted scoring against versioned criteria; class from active rule bands; assessor ≠ approver.
  - `MarketplaceService` — offers, acceptance with row locks (no overfunding), withdrawal, expiry, partial close.
  - `AgreementService` — agreement → signatures → investor funds → disbursement → investments + schedules.
  - `PaymentService` — repayments stay pending until confirmed (webhook or reconciliation); idempotent; per due date, interest first, shortfalls shared pro rata.
  - `LedgerService` — double entry, balanced, immutable; corrections by reversal. Account categories separate Banyira money from client money.
  - `ApprovalService` — maker-checker for disbursements, expenses, payroll.
  - `RulesService` — fee tiers, interest ceiling, risk bands; versioned, approved by someone other than the proposer, effective-dated.
- `app/Support/Money.php` — bcmath money helpers and amortisation. Never use floats for money.
- `app/Contracts/` + `app/Services/{Sms,Payments,Identity}` — provider abstractions. Defaults: `log` SMS, `demo` payments (no real money), manual identity review.
- Guards: `web` (customers) and `staff` (employees). Employee permissions are Gate abilities (`can:finance.approve`); see `ReferenceDataSeeder::ROLES`.
- `resources/js/Pages/{Public,Auth,Business,Investor,Customer,Staff}` — Inertia pages. Shared components in `resources/js/Components`, layouts in `resources/js/Layouts`.
- `prototype/` — the earlier React front-end prototype, kept for reference only. Not built or served.

## Conventions

- Money: `decimal(15,2)` columns, string values, `Money::*` for arithmetic.
- Every status change, approval and sensitive view goes through `AuditLogger`.
- Investors only ever receive `ListingResource` fields about a business.
- Design: system fonts, one green accent (`forest`), thin borders, no gradients or shadows. Status = coloured dot + text. Keep it that way.
- Tailwind v4 tokens live in `resources/css/app.css` (`@theme`). Global element styles go in `@layer base`.
