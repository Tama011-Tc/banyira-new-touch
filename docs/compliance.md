# Compliance and limitations

This prototype does **not** assume that a peer-to-peer lending marketplace, an investment intermediary, a payment service or a pooled-funds arrangement may operate in Malawi without authorisation. Using a licensed payment gateway does not authorise lending or investment activity.

Before any real money is handled, a documented review by qualified Malawian legal and financial-services professionals is required, and the relevant authorities (including the Reserve Bank of Malawi and other competent regulators) must be consulted.

The app enforces this with an **operating mode** setting:
- `simulation` (default): sandbox only; everything is flagged simulated.
- `live`: refused unless **every** item in the go-live checklist is approved with evidence (Compliance → checklist) **and** a live provider is implemented. In this build the second condition is never true, so live mode cannot be enabled, and any money movement attempted in a forced "live" state fails closed.

Checklist items: legal opinion; regulator engagement; authorised payment provider and settlement; safeguarding of investor funds; KYC/AML/CFT; data protection; consumer and investor disclosures; credit-reporting position; tax; independent security review; operational readiness.

Designed for but not completed: beneficial-ownership verification, sanctions screening, suspicious-activity reporting workflow, regulatory reporting exports, credit-bureau reporting, record-retention jobs, investor eligibility rules.
