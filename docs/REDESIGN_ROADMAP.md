# Banyira UI/UX redesign roadmap

Status: **started 2026-11-20.** This file turns the "Master prompt: world-class UI/UX and interaction redesign" into ordered, checkable work. Pick up at the first unchecked box.

The earlier Apple-inspired plan is in [`UI_PLAN.md`](../UI_PLAN.md). Its token, dark mode, type scale and motion work has shipped; this roadmap builds on it rather than replacing it.

## 0. Ground rules (from AGENTS.md, still binding)

- One accent (forest). System fonts. Hairline borders, no gradients. Shadows only on floating layers.
- Colours are tokens in `resources/css/app.css`; no hex values in components.
- Reuse our own components before adding a package. No UI kit installs (shadcn, etc.) without a clear win.
- Every change: `php artisan test`, `npm run typecheck`, `npm run build` pass. No console errors.
- Always honour reduced motion and keep contrast at 4.5:1 or better.
- Take principles from the reference sites, never their logos, images, copy or fonts.

## 1. Done so far

- [x] Light and dark mode: visitors choose Auto / Light / Dark on the public site (header sun/moon button, mobile menu, footer). Choice persists; Auto follows the device; the switch fades except with reduced motion.
- [x] Fixed lazy-loading warnings (`AgreementParty::agreement`, `Investment::schedule`).
- [x] Browser bar colour (`theme-color`) matches the current page background tokens.
- [x] README describing the system.

## 2. Research (not done yet: needs a dedicated session)

The prompt asks to visit and interact with each site on desktop and mobile, not rely on memory. That has **not** been done yet. For each site, record findings in `docs/research/<site>.md` with: page background and text colours, section spacing (px), max content width, nav height and behaviour on scroll, type sizes for hero / section title / body, button shapes, how motion is used, mobile menu pattern, one thing to adopt and one thing to avoid.

| Site | Main thing to study for Banyira | Notes file |
|---|---|---|
| [ ] apple.com | whitespace, product storytelling, sticky translucent nav | |
| [ ] stripe.com | trust in fintech, explaining money flows, pricing tables | |
| [ ] linear.app | density, precision, keyboard-first product UI | |
| [ ] airbnb.com | search and filters (→ investor marketplace), cards | |
| [ ] notion.com | information hierarchy, calm onboarding | |
| [ ] google.com | accessibility, familiar patterns, focus states | |
| [ ] microsoft.com | information architecture, enterprise navigation | |
| [ ] shopify.com | merchant dashboard (→ business dashboard), conversion | |
| [ ] samsung.com | premium product presentation | |
| [ ] revolut.com | fintech dashboards, money display, onboarding | |

Output of this phase: a short `docs/research/SUMMARY.md` listing the patterns to adopt, each mapped to a Banyira page.

**Note:** the pasted master prompt was cut off in section 2A ("How much space exists between…"). Paste the remainder so sections 2B onwards can be added here.

## 3. Foundations

- [ ] Spacing scale: confirm one vertical rhythm for public sections (e.g. 96/128px desktop, 64px mobile) and apply it in `Home.vue` and other public pages.
- [ ] Max content widths: one for reading text (~68ch), one for layouts (`max-w-6xl`). Audit pages that drift.
- [x] Sticky public header: translucent background with blur when scrolled (respect `prefers-reduced-transparency`).
- [ ] Money display component: one `<Money>` component for amount + currency, tabular numbers, consistent negative/positive colouring (Stripe/Revolut).
- [ ] Focus states: audit every interactive element for a visible focus ring (Google).
- [ ] Toast and Tooltip components (noted as missing in `UI_PLAN.md`).
- [x] `public/manifest.webmanifest` colours (`#fafaf8`, `#1d4a37`) predate the current tokens; align with `--bg` and `--tint`.

## 4. Public site and conversion

- [ ] Home hero: tighten copy, one primary call to action per audience, show a real-feeling example listing (exists; refine).
- [ ] "How it works" as a visual step story with scroll-triggered reveals (Apple/Samsung), reduced-motion safe.
- [x] Trust section: how money is protected (client accounts, maker-checker, audit), explained plainly (Stripe). Home page, "How money moves, and how it is protected."
- [ ] Products and fees: clear comparison table, worked example with fees (Stripe pricing).
- [ ] Mobile: check every public page at 390px; tap targets 44px.

## 5. Onboarding (Revolut, Notion)

- [ ] Registration wizard: progress indicator, save and resume, friendly errors under fields.
- [ ] Phone verification: auto-advance code input, paste support, resend timer.
- [ ] Identity verification: what is needed up front, status after submission.

## 6. Business dashboard (Shopify)

- [ ] Overview: next repayment, amount outstanding, application status as one clear "what to do next" card.
- [ ] Application: step list with progress, documents checklist.
- [ ] Offers: compare offers side by side (rate, term, total cost).
- [ ] Repayments: schedule timeline, pay now, statement download.

## 7. Investor experience (Airbnb, Revolut)

- [ ] Marketplace: search, filters (industry, risk class, term, rate), sort, saved filters, good empty state.
- [ ] Listing page: key facts first, risk explained, offer form with live return estimate (server-validated).
- [ ] Portfolio overview: balance-style header, charts using the chart tokens, recent activity feed.

## 8. Staff back office (Linear, Microsoft)

- [ ] Dense tables with sticky headers, keyboard navigation, bulk actions where safe.
- [ ] Command palette (Ctrl+K) for staff navigation.
- [ ] Consistent page headers, filters and empty states across Credit, Finance, Sales, IT, Executive.

## 9. Quality bar for every page

- [ ] Screenshots before/after at 1440px and 390px, both themes, in `ui-audit/`.
- [ ] Contrast 4.5:1 body text; icon-only buttons have `aria-label`.
- [ ] No layout shift on load; skeletons for slow data.
- [ ] Tests, typecheck and build pass.

## Suggested order for next sessions

1. Paste the rest of the master prompt; finish section 2 research (one or two sites per session).
2. Foundations (section 3).
3. Investor marketplace and business overview: the most-used screens.
4. Public site polish, then onboarding, then staff.
