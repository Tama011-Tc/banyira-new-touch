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
- `Content/` (in `app/Services`) — public website pages. `BlockCatalogue` is the one definition of every section type (validation and the editor come from it; each type also needs a component in `Components/Blocks` and an entry in `BlockRenderer.vue`). `ContentService` owns draft, publish, restore. Default wording is in `ContentSeeder`, which never overwrites an existing page.
- `StaffAccessService` — employees and roles. A role that administers the platform never holds approval powers.
- `Backups/` (in `app/Services`) — database dumps behind the `DatabaseDumper` contract.
- `app/Support/TableQuery.php` — search, filter and sort for staff tables, allow-listed. Pair with `TableToolbar.vue` and `lib/table.ts`.
- `app/Support/Money.php` — bcmath money helpers and amortisation. Never use floats for money.
- `app/Contracts/` + `app/Services/{Sms,Payments,Identity}` — provider abstractions. Defaults: `log` SMS, `demo` payments (no real money), manual identity review.
- Guards: `web` (customers) and `staff` (employees). Employee permissions are Gate abilities (`can:finance.approve`); see `ReferenceDataSeeder::ROLES`.
- `resources/js/Pages/{Public,Auth,Business,Investor,Customer,Staff}` — Inertia pages. Shared components in `resources/js/Components`, layouts in `resources/js/Layouts`.
- `prototype/` — the earlier React front-end prototype, kept for reference only. Not built or served.

## Conventions

- Money: `decimal(15,2)` columns, string values, `Money::*` for arithmetic.
- Every status change, approval and sensitive view goes through `AuditLogger`.
- Investors only ever receive `ListingResource` fields about a business.
- Page content is plain text: never render it with `v-html`. Links pass `AppRulesSafeLink`.
- New permissions go to existing installations through a migration; re-running `ReferenceDataSeeder` resets role permissions.
- Design: system fonts, one green accent (`forest`), thin borders, no gradients or shadows. Status = coloured dot + text. Keep it that way.
  Five references, one job each: Muji for palette and restraint, Linear for product UI density, Pentagram for typography (`.t-display`, `.t-title`, `.t-lead`, `.t-label`), Apple for motion (`--ease-smooth`, `--ease-spring`; always honour reduced motion), Duolingo for sign-up (one question per screen: `WizardLayout` + `useWizard()` + `ChoiceCards`).
- Tailwind v4 tokens live in `resources/css/app.css` (`@theme`). Global element styles go in `@layer base`.

## Engineering standards

Work like a senior engineer: correctness, security, maintainability and performance come before "it works". Read the existing code first and reuse what is there.

- **Principles:** SOLID, DRY, KISS, YAGNI, separation of concerns. Small functions, descriptive names, no dead code, no speculative features.
- **Layers:** controllers validate (Form Requests or `$request->validate`), authorise and delegate. Business rules live in `app/Services`. Data access stays in models, scopes and relationships. Vue pages present data; shared UI goes in `Components`, shared logic in `resources/js/lib` (e.g. `useCan()` for permissions, `format.ts` for money and dates).
- **No hardcoded values:** colours are tokens in `app.css`, rules (fees, ceilings, bands) come from `RulesService`, and permission slugs use the `Permission` type, kept in sync with `ReferenceDataSeeder::PERMISSIONS`.
- **Security:** every route is guarded (`auth:*`, `can:*`, policies). The UI hiding a link is never the authorisation. Validate all input, never trust client-side money maths, and audit sensitive actions through `AuditLogger`.
- **Database integrity:** foreign keys and unique constraints in migrations, indexes on filtered or joined columns, `DB::transaction` plus row locks for multi-row money changes. Ledger entries are immutable.
- **Tests:** new behaviour ships with feature tests. `php artisan test` and `npm run typecheck` must pass before a change is done.
- **Dependencies:** add a package only when it clearly beats a few lines of our own code. No workarounds or quick fixes; fix the cause.
