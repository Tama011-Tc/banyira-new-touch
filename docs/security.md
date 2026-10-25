# Security

- Server-side Gates on every sensitive route; ownership checks return 404 for other parties' records (IDOR).
- Role and status are not mass-assignable; public sign-up cannot create staff; staff passwords require 12+ mixed characters.
- Confidential documents live on the private disk under random names, are never publicly linked, are downloaded through an authorised and audited route, and are hashed (SHA-256) on upload. Type, size and MIME are validated.
- National IDs and registration numbers are encrypted at rest (Laravel `encrypted` cast); never echoed back.
- CSRF on all browser routes; webhooks use HMAC instead. Rate limits on login, messaging, uploads, payments and sensitive actions.
- Audit log is append-only and hash-chained; ledger, audit and moderation tables reject UPDATE/DELETE at the database layer.
- Suspended users are signed out on their next request and their sessions are deleted.
- No secrets in Git; `.env.example` contains none.

**Known gaps:** staff MFA is not enforced; no antivirus scan on uploads; no field-level encryption of documents at rest beyond the disk; no dependency/pen-test review yet.
