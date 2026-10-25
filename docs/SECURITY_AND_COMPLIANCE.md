# Security

- Server-side Gates on every sensitive route; ownership checks return 404 for other parties' records (IDOR).
- Role and status are not mass-assignable; public sign-up cannot create staff; staff passwords require 12+ mixed characters.
- Confidential documents live on the private disk under random names, are never publicly linked, are downloaded through an authorised and audited route, and are hashed (SHA-256) on upload. Type, size and MIME are validated.
- National IDs and registration numbers are encrypted at rest (Laravel `encrypted` cast); never echoed back.
- CSRF on all browser routes; webhooks use HMAC instead. Rate limits on login, messaging, uploads, payments and sensitive actions.
- Audit log is append-only and hash-chained; ledger, audit and moderation tables reject UPDATE/DELETE at the database layer.
- Suspended users are signed out on their next request and their sessions are deleted.
- No secrets in Git; `.env.example` contains none.

**SMS.** Inbound replies are accepted only through a signed gateway callback, only from a business owner's registered number, only with a single-use code issued for one specific open offer, only up to a configurable amount, and with per-number rate limiting. SMS is spoofable and SIM-swap is possible, so these are layered controls, not proof of identity; the investor's side and all platform checks still apply, and large offers need the website.

**Known gaps:** staff MFA is not enforced; no antivirus scan on uploads; no field-level encryption of documents at rest beyond the disk; no dependency/pen-test review yet.
# Compliance and limitations

This prototype does **not** assume that a peer-to-peer lending marketplace, an investment intermediary, a payment service or a pooled-funds arrangement may operate in Malawi without authorisation. Using a licensed payment gateway does not authorise lending or investment activity.

Before any real money is handled, a documented review by qualified Malawian legal and financial-services professionals is required, and the relevant authorities (including the Reserve Bank of Malawi and other competent regulators) must be consulted.

The app enforces this with an **operating mode** setting:
- `simulation` (default): sandbox only; everything is flagged simulated.
- `live`: refused unless **every** item in the go-live checklist is approved with evidence (Compliance → checklist) **and** a live provider is implemented. In this build the second condition is never true, so live mode cannot be enabled, and any money movement attempted in a forced "live" state fails closed.

Checklist items: legal opinion; regulator engagement; authorised payment provider and settlement; safeguarding of investor funds; KYC/AML/CFT; data protection; consumer and investor disclosures; credit-reporting position; tax; independent security review; operational readiness.

Designed for but not completed: beneficial-ownership verification, sanctions screening, suspicious-activity reporting workflow, regulatory reporting exports, credit-bureau reporting, record-retention jobs, investor eligibility rules.
