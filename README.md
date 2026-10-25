# Banyira MicroCredit

A database-backed prototype of a **lending marketplace for Malawian SMEs**: businesses apply, credit staff assess, approved businesses are listed, verified investors negotiate versioned offers in protected messaging, and the platform generates loan agreements, repayment schedules, fees and a tamper-evident ledger.

> **This is a simulation.** No real money moves. Payments run through a clearly labelled sandbox provider, every ledger entry is flagged `simulated`, and *live* operation is blocked by design until legal, regulatory and payment-provider approvals are verified (see [docs/SECURITY_AND_COMPLIANCE.md](docs/SECURITY_AND_COMPLIANCE.md)). Businesses, investors and figures in the demo are fictional. Returns are never guaranteed and the UI says so.

The brand is configurable (`BANYIRA_BRAND` in `.env`); the name was provisional.

## Run it

Requires PHP 8.3+ (with `bcmath`), Composer, Node 20+, MySQL 8.

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
mysql -uroot -e "CREATE DATABASE banyira CHARACTER SET utf8mb4; CREATE DATABASE banyira_test CHARACTER SET utf8mb4;"
php artisan migrate --seed          # demo data (fictional)
npm run dev                         # or: npm run build
php artisan serve --port=8001       # http://127.0.0.1:8001
```

Redis is optional (queues/cache use the database driver by default). Nothing here needs it.

## Demo accounts (password `password`, local only)

| Role | Login | What to try |
|---|---|---|
| Business owner | `business@banyira.test` | Add an application, upload documents, submit |
| Sales rep | `sales@banyira.test` | See leads, register or invite a business owner, upload documents on their behalf, log follow-ups |
| Credit analyst | `analyst@banyira.test` | Screen, calculate score, record assessment |
| Credit approver | `approver@banyira.test` | Approve/reject; prepare a listing |
| Executive | `executive@banyira.test` | Review a listing, publish; dashboards |
| Investor | `investor@banyira.test` | Browse, message, negotiate, fund (sandbox) |
| Investor (unverified) | `investor3@banyira.test` | See the KYC and risk gates |
| Finance | `finance@banyira.test`, `finance2@…` | Approve disbursement, reconcile, two-person approvals |
| Compliance | `compliance@banyira.test` | Go-live checklist, KYC review, moderation |
| IT admin | `admin@banyira.test` | Users, roles, settings, audit, health |
| Support | `support@banyira.test` | Moderation queue, complaints |

### The recruiter journey (about 5 minutes)

1. **Business** (`business@…`): *My business* is pre-filled → *Applications → New application* → add the three required documents → **Submit**.
2. **Analyst**: open it in the queue → *Start screening* → *Start assessment* → review the calculated score → **Record assessment**.
3. **Approver** (a *different* person): **Record decision** → *Listings → Prepare listing*.
4. **Executive** (a *different* person again): **Review**, then **Publish**. The listing is now on the marketplace.
5. **Investor** (`investor@…`): open the opportunity → *Ask a question or make an offer* → try pasting a phone number (it is blocked) → **Propose terms**.
6. **Business**: *Messages* → counter-propose → the investor **Accepts** → a simulated agreement and **repayment schedule** appear.
7. **Investor** pays in the sandbox → **Finance** approves the release → **Business** repays instalments → watch balances, the ledger and the audit log.

## How accounts are created

- **Investors and business owners** sign up on the public Register page. That form cannot create any other role.
- **Staff** (sales, credit analyst, credit approver, executive, finance, compliance, support, other IT admins) are created only by an **IT administrator** in *Admin → Users*. Leave the password blank to generate a strong one; it is shown once and never stored in plain text or logged. The new person must replace it at first sign-in, and an admin can reset a forgotten password the same way.
- **The very first administrator** is created from the console, because nobody can create them in the app yet:

```bash
php artisan banyira:create-admin you@example.com --name="Your Name"   # prints a temporary password once
```

**What the admin can see.** The IT administrator's window is read-only oversight of everything operational: the executive overview, all applications and their credit assessments, listings, loans and schedules, payments, fees and the ledger, moderation and complaints, the audit log and system health. It cannot change a credit decision, a loan, a payment, a fee or the ledger, and confidential documents, registration numbers and private conversations stay closed to it. Each admin view of an application or loan is itself written to the audit log.

Customers and staff are separate: a role can never be moved from one group to the other.

## What it does

| Area | Notes |
|---|---|
| Roles | Investor, business owner, sales, credit analyst, credit approver, executive, finance, compliance, IT admin, support. Least-privilege Gates; server-side on every route. |
| Sales window | A rep sees only their own leads. They register a business with the owner's recorded consent (or just invite an owner), prepare the application and upload documents on the owner's behalf, log calls, meetings and follow-ups, and refer a complete application to credit. The owner gets a single-use link (3 days) to choose their **own** password, then signs in to follow progress. The rep never knows that password and cannot assess, approve or promise funding. |
| Credit specialist | Submitting an application notifies every active analyst. The analyst who starts screening owns the case (shown in the queue, with a *My cases* filter). Applicants and the rep who helped are notified when more information is needed, and on approval, rejection, publication and funding. |
| Application lifecycle | Draft → submitted → screening → assessment → (info requested) → approved/rejected → listing prepared → published → funding → funded → repayment active → completed / overdue / restructured / defaulted. Plus withdrawn, expired, suspended, cancelled. Explicit state machine; every change audited. |
| Credit | Configurable, explained score (repayment capacity, history, documents, debt, sector) → *suggests* A–D. Analysts may adjust ±15 with written reasons. A **different** approver decides; large amounts need an executive. No protected characteristics exist in the data model. |
| Listings | Prepared → independently reviewed → published. Text is screened for contact details. Public view exposes only approved, non-confidential fields. |
| Messaging | Server-side screening for phone numbers, emails, URLs, handles, messaging apps, payment instructions; off-platform requests go to review. Appeal, moderator release/uphold, reports, rate limits, all logged. |
| Offers | Immutable versions with a content hash and a fee snapshot. Terms limited to the approved ceiling. An agreement exists only when both parties accept the same latest version. |
| Loans | `bcmath` integer maths. Flat and reducing-balance, weekly/monthly, grace period, last instalment absorbs rounding. |
| Fees | Borrower origination (deducted from disbursement), investor service (added to funding payment), provider processing (borne by the platform), optional tax. Disclosed up front, snapshotted, changes need two people. |
| SMS | Mock provider. The owner is texted the amount, rate and period of each offer with a reply code and can reply **Y code** / **N code** to accept or decline (up to MK 5,000,000), from their registered number only. Status updates and repayment reminders are texted too. Nothing is actually sent in this build. |
| Payments | Sandbox provider with signed webhooks, provider-side verification, idempotency keys, duplicate-event protection, reconciliation, failure handling, two-person reversals. |
| Ledger | Double-entry, append-only (also enforced by database triggers). Corrections are reversing entries. |
| Audit | Hash-chained, append-only; chain verification shown to staff. |
| Controls | Dual approval for fee changes, reversals and disbursement release; MFA marker for staff (TOTP is a documented gap). |

## Tests

```bash
php artisan test
```

**207 tests, all passing** at the time of writing (run `php artisan test` to confirm on your machine). They cover calculation accuracy, message screening (including false-positive cases), the role matrix, document and agreement access (IDOR), the full application path, offer versioning and tamper detection, payment verification, forged and replayed webhooks, duplicate checkout, repayment allocation (partial, over- and under-payment), overdue and default rules, reversals, fee-change approval, immutability of the ledger and audit log, and rendering of every screen for every role. There are no browser end-to-end tests yet.

## Documentation

- [Product requirements & status](docs/PRODUCT_REQUIREMENTS.md) · [System architecture](docs/SYSTEM_ARCHITECTURE.md) · [Database design & ER diagram](docs/DATABASE_DESIGN.md)
- [Security & compliance](docs/SECURITY_AND_COMPLIANCE.md) · [Implementation roadmap](docs/IMPLEMENTATION_ROADMAP.md) · [Decisions & assumptions](docs/DECISIONS_AND_ASSUMPTIONS.md)
- [Workflows](docs/workflows.md) · [Business rules & calculations](docs/business-rules.md) · [Payment sandbox & webhook API](docs/payments-sandbox.md)

## Not built / externally dependent

- **Live payments, escrow, wallets, pooled funds** — deliberately absent. They need licences and a contracted provider.
- **Staff MFA (TOTP)** — a marker column exists; enrolment and enforcement are not implemented.
- **Emailed invitations** — temporary passwords are handed over by the admin; no invite email is sent.
- **KYC document capture / sanctions screening / credit-bureau lookups** — manual review UI only; no vendor integrations.
- **Chichewa translations** — language switch and fallback exist; the interface and lessons are English only until a translator supplies text.
- **Real SMS and email delivery** — a mock SMS gateway and in-app notices exist; a real SMS adapter and mail settings are needed.
- **Restructuring workflow and recovery tracking** after default — states exist; no UI.
- **Screenshots** — not generated in this build.
- Redis queues and a scheduler for overdue sweeps (`refreshStatus` runs on payment events; add a nightly command before production).
