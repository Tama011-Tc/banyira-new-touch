---
name: Banyira MicroCredit (public site)
description: Peer-to-peer business loans for Malawian SMEs, told plainly on a white page with one green for action.
colors:
  ink: "#14212b"
  ink-body: "rgb(20 33 43 / 0.75)"
  ink-quiet: "rgb(20 33 43 / 0.65)"
  ink-hairline: "rgb(20 33 43 / 0.10)"
  leaf: "#0a7553"
  leaf-deep: "#085f43"
  lake: "#e6efee"
  maize: "#e8b931"
  paper: "#ffffff"
  unset-red: "#b23a2e"
typography:
  display:
    fontFamily: "Archivo, ui-sans-serif, system-ui, sans-serif"
    fontSize: "clamp(2.6rem, 6vw, 4.4rem)"
    fontWeight: 850
    lineHeight: 0.98
    letterSpacing: "-0.025em"
    fontVariation: "'wdth' 122"
  headline:
    fontFamily: "Archivo, ui-sans-serif, system-ui, sans-serif"
    fontSize: "2.6rem"
    fontWeight: 800
    lineHeight: 1.08
    letterSpacing: "-0.015em"
    fontVariation: "'wdth' 112"
  title:
    fontFamily: "Archivo, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 800
    lineHeight: 1.08
    letterSpacing: "-0.015em"
    fontVariation: "'wdth' 112"
  subtitle:
    fontFamily: "Archivo, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 700
    lineHeight: 1.5
  lead:
    fontFamily: "Archivo, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 400
    lineHeight: 1.625
  body:
    fontFamily: "Archivo, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.625
  data:
    fontFamily: "Archivo, ui-sans-serif, system-ui, sans-serif"
    fontSize: "15px"
    fontWeight: 600
    lineHeight: 1.5
    fontFeature: "'tnum' 1"
  label:
    fontFamily: "Archivo, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 600
    lineHeight: 1.4
rounded:
  sm: "4px"
  md: "8px"
  bubble: "12px"
  device: "2rem"
spacing:
  gutter-mobile: "16px"
  gutter: "24px"
  section: "56px"
  section-lg: "80px"
  section-hero: "96px"
  column-gap: "32px"
  ruled-gap: "20px"
components:
  button-primary:
    backgroundColor: "{colors.leaf}"
    textColor: "{colors.paper}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    padding: "12px 24px"
  button-primary-hover:
    backgroundColor: "{colors.leaf-deep}"
    textColor: "{colors.paper}"
  button-ink:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.paper}"
    rounded: "{rounded.md}"
    padding: "12px 24px"
  button-outline:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "10px 22px"
  button-outline-hover:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.paper}"
  button-on-ink:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "12px 24px"
  button-on-ink-hover:
    backgroundColor: "{colors.lake}"
    textColor: "{colors.ink}"
  link:
    textColor: "{colors.leaf}"
  data-panel:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "32px"
  language-switch-active:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.paper}"
    rounded: "{rounded.sm}"
    padding: "4px 10px"
  search-on-ink:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "14px 16px 14px 48px"
---

# Design System: Banyira MicroCredit (public site)

**Scope.** This file covers the public marketing site only: `SiteLayout`, the landing page, Products, How to get a loan, Support, About us, For investors, and the sign-in/register shell (`AuthSimpleLayout`). The signed-in app (`AppLayout` and the app pages) runs on a separate shadcn-based system set in Instrument Sans with the HSL tokens in `resources/css/app.css` `:root`; it is not described here and this system does not govern it.

## Overview

**Creative North Star: "The Signboard and the Statement"**

The site plays the category standard straight: a white page, dark ink, one leaf green for every action, and the cost of a loan shown as a plain ruled statement rather than as headline numbers. The only voice raised above that is the headline type. Archivo is widened (122% on display, 112% on headings) and set very heavy, so headlines read like a painted shop signboard while everything beneath them stays an ordinary, readable bank page. It works on a low-end Android phone first and then opens out to two columns on desktop.

Structure does the work that decoration would do on other sites. Sections take turns between white and a pale lake tint, and the investor band and the calculator sit on full-width ink. Content groups open with a heavy 3px ink rule instead of a card box. Figures sit in ruled definition lists with tabular numerals. The one moving element is the hero phone, which plays the SMS-to-acceptance exchange once and then stays still.

The world was chosen against gimmick: no pattern fills, no gradients, no eyebrow labels above headings, no grids of icon tiles.

**Key Characteristics:**
- White ground, ink text, one green for action; lake for alternate sections; ink bands for the investor and calculator moments.
- Widened, heavy Archivo headlines over plain Archivo body at normal width.
- 3px ink top rules open columns, tables, FAQ groups and the auth card.
- Money shown as ruled label/value rows with tabular figures, never as big standalone stats.
- Underlined text links as the secondary action next to every primary button.
- Bilingual (English / Chichewa) with the switch always visible.

## Colors

A restrained palette: ink and white carry almost everything, leaf marks action, lake and ink set the rhythm of the sections, and maize appears only as a signal.

### Primary
- **Banyira Leaf** (`leaf`): Primary buttons, text links, the check marks on term lists, step numerals, the active-nav underline, text selection, range-slider accent, and the "You receive" figure. Hover deepens to **Leaf Deep** (`leaf-deep`).

### Secondary
- **Lake** (`lake`): Background of alternate full-width sections (cost, bands, contact, the auth page), dropdown row hover, and the hover state of white buttons on ink. Also the screen of the hero phone.

### Tertiary
- **Maize** (`maize`): Signal only. It is used for the unread dot on the SMS alert and for focus outlines on ink grounds (footer, `.on-ink` bands, the Support search, the calculator's repayment toggle). Nowhere else.

### Neutral
- **Ink** (`ink`): All headings and primary text, the investor band, calculator band, Support hero, footer, the language switch's active state, the 3px structural rules, and the focus outline on light grounds.
- **Ink Body** (`ink-body`, ink at 75%): Lead paragraphs and running body copy under headings.
- **Ink Quiet** (`ink-quiet`, ink at 65%): Captions, footnotes, secondary list text. This is the lightest ink allowed for text.
- **Ink Hairline** (`ink-hairline`, ink at 10%): Header bottom border, section dividers, the rows of every ruled list and table.
- **Paper** (`paper`): Page ground and data panels.
- **Unset Red** (`unset-red`): Only for a site setting that is not configured yet (support phone, hours or email shown as "[to confirm]"). It marks a fact the operator still has to supply. It is not an error or brand color.

Risk-band chips on For investors use their own four-step data scale (leaf, `#1f7a8c`, `#8a6400`, `#b23a2e`, fallback `#5b6670`). That scale encodes band order on that one list and does not belong to the brand palette.

### Named Rules
**The One Green Rule.** Leaf means "act" or "money to you". If something is green, it is a button, a link, a check against a term, or the amount received. Never use it as decoration.

**The Maize Is a Signal Rule.** Maize lights the unread SMS dot and focus on ink. It never fills a surface, a button or a headline.

**The Lake Then Ink Rhythm Rule.** Full-width sections alternate between white and lake. Ink is saved for the one or two moments per page that need a change of key (investors, the calculator, the Support search, the footer).

## Typography

**Display Font:** Archivo (variable, wdth 62–125, wght 400–900, loaded from Google Fonts in `app.blade.php`), with ui-sans-serif, system-ui
**Body Font:** Archivo at normal width (100%)

**Character:** One family at two widths. The widened heavy cuts give a confident, local-signboard voice, and normal-width Archivo keeps the body plain and quick to read on small screens.

### Hierarchy
- **Display** (850, wdth 122%, 2.6rem mobile → 3.75rem sm → 4.4rem lg on the home hero, line-height 0.98, -0.025em): One per page, as the page h1. Max about 14ch on the home hero, balanced wrap.
- **Headline** (800, wdth 112%, 1.875rem → 2.6rem, line-height 1.08, -0.015em): Section h2s. The "who we fund" statement also uses this face at 2.1rem/1.2 as a sentence-length headline.
- **Title** (800, wdth 112%, 1.5rem): Heads of ruled columns, product names, feature columns.
- **Subtitle** (700, 1.125rem): Step titles, contact column heads, panel titles (panel titles at 1rem bold).
- **Lead** (400, 1.125–1.25rem, line-height 1.625, ink-body): The paragraph under an h1 or h2, max 34rem–36rem.
- **Body** (400, 1rem, line-height 1.625, ink-body): Running copy. FAQ answers cap at 65ch, guide steps at 60ch.
- **Data** (600, 15px, tabular figures): Values in ruled lists. Live calculator outputs step up to 1.5rem/800, and the headline repayment to 3rem/800 with tight tracking.
- **Label** (600, 0.875rem): Nav items (at 1rem), language switch, form labels, slider range ends (0.75rem).
- **Step numerals** (headline face, 2.25rem–3rem, leaf): Number the process steps instead of icons.

### Named Rules
**The Two Widths Rule.** Only headings are widened (display at 122%, headings and the wordmark at 112%, buttons at 108%). Body, data and labels stay at 100%. Never widen running text.

**The Tabular Money Rule.** Every money figure, rate and count in a list or output is set with tabular figures.

## Layout

The page uses a single 80rem (`max-w-7xl`) container with 16px gutters on phones and 24px from 640px up. Reading pages narrow to 56rem (Support) or 48rem (loan guide steps). Sections are full-bleed bands with 56px vertical padding, rising to 80px from 1024px. The home page breathes a little more (64px → 96px).

From 1024px, the home hero is asymmetric (1.15fr text / 1fr phone, vertically centred); page heroes pair the h1 and lead with a checked rules list (1.2fr / 1fr, bottom-aligned). Content below runs as three equal ruled columns (`md:grid-cols-3`, 32px gap), as a 1fr / 1.4–1.5fr split of explanation and data, or as a four-up contact row. On phones everything stacks in reading order. The product comparison table becomes one ruled block per product, and each block gets a full-width Apply button.

The header is sticky, 64px tall (72px from 1024px), with a hairline bottom border. The full nav appears from 1024px; below that a menu button opens a full-height panel. The wordmark appears from 420px.

Breakpoints are Tailwind's defaults (640 / 768 / 1024 / 1280) plus a 420px step for the wordmark.

## Elevation & Depth

The site is flat. Depth comes from the ground changing (white, lake, ink) and from rules. Shadows appear only under things that float above the page: the phone in the hero, the white data panel on a lake section, the auth card, and the Products dropdown. All of them are soft, ink-tinted and pulled in with a negative spread so they read as lift, not outline. White panels on an ink band carry no shadow.

### Shadow Vocabulary
- **Device lift** (`box-shadow: 0 30px 60px -30px rgba(20,33,43,0.6)`): The hero phone only.
- **Panel lift** (`box-shadow: 0 20px 50px -30px rgba(20,33,43,0.4)`): A white data panel or the auth card sitting on lake.
- **Menu lift** (`box-shadow: 0 18px 40px -20px rgba(20,33,43,0.35)`): The Products dropdown.

### Named Rules
**The Flat Page Rule.** Sections, columns, lists and buttons never cast shadows. A shadow means "this object sits on top of the page", and there should be no more than one such object per section.

## Shapes

Corners are gently rounded and consistent: 8px (`rounded.md`, Tailwind `rounded-md` resolved through the shared `--radius` of 0.625rem minus 2px) on buttons, panels, the search field and the auth card; 4px on the language-switch segments and the risk-band chips; 12px on SMS bubbles; 2rem / 1.5rem on the phone body and screen. The direction contract named 6px radii; the build shipped 8px, and the build is the record.

The recurring form is the **3px ink top rule**: a heavy line that opens a column, a comparison-table head, an FAQ group, a contact column, the risks panel and the auth card. Everything inside is divided by 1px ink-hairline rows. Outline buttons use a 2px ink border. Lists of risks use a short 12×2px ink dash; lists of terms use a leaf check.

## Components

### Buttons
Solid, compact, slightly widened.
- **Shape:** gently rounded (8px).
- **Primary:** leaf ground, white bold 15px text at wdth 108%, 12px × 24px padding (the hero CTA 14px × 32px at 16px; the header CTA 8–10px × 14–20px at 14–15px). Hover goes to leaf-deep with a colour transition.
- **Ink:** ink ground and white text, with black on hover. Used when leaf would compete, for example on a lake panel next to an outline button.
- **Outline:** 2px ink border, ink text, 10px × 22px padding; fills ink with white text on hover. This is the secondary button for "Contact us", "Sign In" in the mobile menu, and "All risks".
- **On ink:** white ground and ink text, lake on hover. This is the single action on an ink band.
- **Focus:** 3px ink outline offset 2px on light grounds, 3px maize on ink grounds.

### Text links
Bold leaf text with a 2px underline at 40% leaf, offset 4px, which goes to full leaf on hover. On ink, links are white with a 40% white underline. Every primary button is paired with one text link as the quieter alternative ("Work out your repayments", "Read the full guide").

### Data panels / ruled statements (signature)
A white panel (8px radius, 24px padding, 32px from 640px) with a bold title and a definition list of label/value rows divided by ink hairlines. Values are right-aligned, tabular and semibold, with a 12px ink-quiet footnote below. This is how cost is shown everywhere: the worked example, the calculator result, investor terms and risk bands. Panel lift applies on lake only.

### Ruled columns
A 3px ink top rule, 20px top padding, a title-size widened head, body copy in ink-body and a text link. Three across from 768px. They replace cards: there is no box, background or icon.

### Navigation
A white sticky bar. Items are semibold 16px ink. Hover shows a 3px bottom border at 20% ink; the current page shows it in leaf. The Products menu is a 26rem white dropdown with menu lift, rows that hover to lake, and an investor cross-link under a hairline. On the right are the language switch (a bordered 8px segmented control whose active segment is ink with white text), Sign In as plain text, and the primary Apply Now button. On phones the switch becomes a single underlined link naming the other language, and the menu opens a stacked list ending in an outline Sign In and a full-width primary Apply.

### Inputs
- **Search on ink (Support):** a white field, no border, 8px radius, a leading 20px search icon in ink at 50%, placeholder in ink at 55%. Focus shows a 3px maize outline.
- **Calculator controls (on ink):** native range sliders with a leaf accent; values print beside each label in 1.5rem/800 tabular. The repayment toggle is a two-up segmented control with 8px segments and a 25% white ring; the checked segment is white with ink text, and focus is a 3px maize outline.

### Hero phone (signature)
An ink device (2rem radius, 12px bezel, device lift) holding a lake screen with three white or leaf bubbles: the SMS alert with its maize unread dot, the offer in the account as a ruled list with inert Accept/Decline, and a leaf confirmation. The bubbles play in once (`sms-in`: 0.45s, `cubic-bezier(0.2, 0.7, 0.2, 1)`, rising 10px, staggered 0.3s / 1.3s / 2.4s). Reduced motion collapses this to an instant state. The phone always carries a caption saying the example is fictional.

### Footer
An ink band with white text at 75%. Columns have bold white heads and 14px links that turn white and underline on hover. The legal block below a 15% white hairline is 12px at 60% white.

## Do's and Don'ts

### Do:
- **Do** use leaf for every primary action and pair it with one underlined text link as the secondary path.
- **Do** open content groups with a 3px ink top rule and divide their rows with ink-hairline (ink at 10%).
- **Do** show cost as a ruled statement of label/value rows with tabular figures, inside a white 8px data panel.
- **Do** alternate full-width sections between white and lake, and reserve ink bands for the investor moment, the calculator, the Support search and the footer.
- **Do** widen headings only (122% display, 112% headings) and keep body text at normal width.
- **Do** keep text at ink 65% or darker on light grounds.
- **Do** use a 3px ink focus outline on light grounds and a 3px maize one on ink.
- **Do** caption every illustrative example (the hero phone, worked costs) as fictional or illustrative.

### Don't:
- **Don't** put eyebrow labels or kickers above headings; the heading stands alone.
- **Don't** use gradients, pattern fills or textured grounds.
- **Don't** build grids of icon tiles or boxed cards; use ruled columns and step numerals.
- **Don't** use maize anywhere except the unread SMS dot and focus on ink.
- **Don't** cast shadows from sections, columns, lists or buttons; only a single floating object (phone, panel on lake, auth card, dropdown) gets lift.
- **Don't** use unset-red for anything except a site setting that has not been configured yet.
- **Don't** apply this system to the signed-in app, which keeps its own shadcn / Instrument Sans system.
