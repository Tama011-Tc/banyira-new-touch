# Payment sandbox and webhook

`SandboxProvider` has its own books (`sandbox_transactions`). In the UI, "Approve payment" simulates the customer approving on their phone; the provider then calls the platform with a signed webhook.

`POST /webhooks/payments/sandbox`
- Header `X-Signature`: `HMAC-SHA256(body, secret)`; secret from `SANDBOX_WEBHOOK_SECRET` or derived from `APP_KEY`.
- Body: `{"event_id","type","ref","amount"}`.
- Responses: `200 {"outcome": "processed" | "duplicate" | "failed_recorded" | "unknown_payment"}`, `401` for a bad signature (logged for monitoring).

The platform **never** trusts the body or a browser return. It looks the payment up with the provider (`verify`) and checks the amount. Replays and a second event for the same payment change nothing. Initiation requires an idempotency key; reusing it returns the same attempt.

Swapping in a real gateway means implementing the same four operations (create, verify, signature check, settle) behind `PaymentService`, plus the approvals in `docs/SECURITY_AND_COMPLIANCE.md`.
