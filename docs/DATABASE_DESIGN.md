# Database design

MySQL 8, integer tambala, foreign keys throughout, unique constraints for idempotency (`payment_attempts.idempotency_key`, `webhook_events(provider,event_id)`, `repayments.payment_attempt_id`, `offer_versions(offer_id,version)`, `loan_agreements.offer_version_id`).

**Record separation.** `offer_versions` (proposed) → `loan_agreements` (agreed, frozen) → `investment_commitments` (pledged/funded) → `payment_attempts` (unconfirmed) → `ledger_entries` (confirmed, append-only). Corrections are reversing entries.

**Tamper resistance.** `audit_logs`, `ledger_entries` and `message_moderation_events` reject UPDATE/DELETE through database triggers; audit rows are also hash-chained. Audit and ledger user columns deliberately have no foreign keys so history outlives accounts.

**Planned additions** (see roadmap): `fee_schedules` and `fee_schedule_tiers`, `risk_bands`, `sms_messages` and `sms_action_tokens`, `education_articles`, `offer_versions.expires_at`.

# Entity-relationship diagram

```mermaid
erDiagram
  users ||--o| investor_profiles : has
  users ||--o| businesses : owns
  businesses ||--o{ applications : submits
  applications ||--o{ application_documents : has
  applications ||--o{ credit_assessments : assessed_by
  applications ||--o{ credit_decisions : decided_by
  applications ||--o| listings : becomes
  listings ||--o{ conversations : enquiries
  users ||--o{ conversations : investor
  businesses ||--o{ conversations : counterparty
  conversations ||--o{ messages : contains
  messages ||--o{ message_moderation_events : moderated
  conversations ||--o| offers : negotiates
  offers ||--o{ offer_versions : versions
  offer_versions ||--o| loan_agreements : accepted_as
  loan_agreements ||--|| investment_commitments : pledged
  loan_agreements ||--o{ repayment_schedules : schedule
  loan_agreements ||--o| disbursements : released
  loan_agreements ||--o{ payment_attempts : paid_by
  loan_agreements ||--o{ platform_fees : charges
  payment_attempts ||--o| repayments : applied_as
  repayments ||--o{ repayment_allocations : allocated
  repayment_allocations }o--|| repayment_schedules : to
  payment_attempts ||--o{ reconciliation_records : checked
  ledger_entries }o--|| payment_attempts : posted_for
  users ||--o{ audit_logs : acts
  users ||--o{ complaints : files
  users ||--o{ watchlist_items : saves
  approval_requests }o--|| users : requested_by
```

Append-only tables (enforced by triggers): `audit_logs`, `ledger_entries`, `message_moderation_events`.
