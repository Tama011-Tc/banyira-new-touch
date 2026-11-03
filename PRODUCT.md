# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users
- **Primary: Malawian SME owners** looking for a loan: traders, millers, farmers, transporters, builders, clinics, schools, guesthouses. Many visit on low-end Android phones with expensive, slow data; some have no smartphone and rely on SMS alerts and on a sales rep who applies with them.
- **Secondary: investors** with money to lend to vetted businesses, who need to understand the risk before they commit.
- Staff (sales, credit analysts, finance, executives, IT, support) use the signed-in app, not the public site.

## Product Purpose
A non-deposit-taking peer-to-peer lending platform: investors lend directly to vetted Malawian small businesses. Success is businesses getting fair credit they could not get from banks (target: 1,000+ SMEs, Blantyre first) and investors understanding exactly what they are funding.

## Positioning
- The owner is alerted by SMS when an offer arrives (amount, rate and term), then accepts or declines it on the platform, in their Banyira account. Offers are not accepted by SMS reply (owner decision, 2026-11-03), even though the codebase still contains an SMS-reply acceptance service.
- Judged on trading and repayment capacity, not collateral.
- Rate ceiling of 36% a year against 50–100% from informal lenders; one business fee of 4–15% by loan size, shown before acceptance; no early-repayment, monitoring or security fees; investors pay no platform fee.

## Operating Context
Eligibility: at least 1 year trading and 6 months of records. Required documents: owner national ID, business registration, bank statements (PDF/JPG/PNG up to 5 MB). Risk bands A+ to C; below C is not listed. Investors start from MK 50,000 per business and are repaid monthly to their bank. All negotiation stays on-platform; contact details are blocked in messages. Free bookkeeping and financial-management guides.

## Capabilities and Constraints
- Live terms (rate ceiling, max term, fee tiers, bands, minimum commitment, SMS acceptance limit) come from settings; public pages must read them rather than hard-code them.
- Not yet licensed (Microfinance Act 2010). The platform is a pilot with fictional data and simulated payments; the public site must say so.
- Undecided: collection-referral fee for missed payments; early-payoff quotes.

## Brand Commitments
Name "Banyira MicroCredit" (short form "Banyira"). Existing logo mark in `resources/js/components/pw/Logo.vue`. Voice: plain, honest, never promises returns; risk stated alongside every investment claim.

## Evidence on Hand
No real photos, testimonials, partner logos, press or lending statistics yet. The owner allowed made-up example stories for now; any such content must be labelled as fictional/illustrative and never presented as a real customer, figure or endorsement.

## Product Principles
1. Show the full cost before the yes.
2. Work for the owner with a basic phone and little data.
3. Say the risk in the same breath as the return.
4. Every public figure is a rule the platform actually enforces.

## Accessibility & Inclusion
Mobile-first for low-end Android on slow connections: light pages, no heavy imagery, readable at small sizes, large tap targets.
Bilingual from the start: the public site must be available in English and Chichewa, with a language switch visible on every page. Layouts must tolerate Chichewa copy, which often runs longer than English. Translations must be reviewed by a fluent speaker before launch (none supplied yet).
