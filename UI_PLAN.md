# UI_PLAN.md — Apple-inspired redesign (draft, awaiting approval)

## 1. What exists today

- Stack: Laravel 13, Inertia v3, Vue 3 + TS, Tailwind v4 (tokens in `resources/css/app.css` `@theme`), `lucide-vue-next`.
- 100 pages: Public (8), Auth (7), Business (7), Investor (9), Customer (5), Staff (~35), Error.
- 9 layouts (`AppShell`, `Business/Investor/Customer/Staff/Public/Auth/Wizard`) and 40 shared components, including hand-built Btn, Card, DataTable, Modal, Tabs, Badge/Status, Field/TextInput, Skeleton, EmptyState, PageHead, Pagination.
- A redesign already shipped in the last two commits: semantic tokens, light and dark mode, forest accent, `.t-display/.t-title/.t-lead/.t-label`, spring/smooth easing, reduced-motion handling, one-question-per-screen sign-up.

## 2. Conflicts with the brief that need your decision

| Brief says | Project rules say | My proposal |
|---|---|---|
| Accent `#0071E3` "unless the project has a brand colour" | Brand accent is forest `#1f5a43` | Keep forest (the brief allows this) |
| Page bg `#F5F5F7`, text `#1D1D1F`, cool greys, dark bg `#000` | Warm Muji paper `#f6f4ef`; memory: "no vibecoded look" | Move neutrals to Apple's cooler values, keep forest accent. Dark surfaces `#000` / `#1C1C1E`. **Needs your OK.** |
| Soft shadows on cards and bars | AGENTS.md: "no gradients or shadows" | Borders first; shadow only on floating layers (modal, sheet, dropdown). **Needs your OK.** |
| Install shadcn-vue (reka-ui) and replace components | AGENTS.md: add a package only when it clearly beats our own code; we already have the components | Do **not** install shadcn-vue wholesale. Extend our own components. Add Sheet and Tooltip (the only missing ones), and a toast. |
| Add `@lucide/vue` and remove other icon sets | Only `lucide-vue-next` is installed, in one `Icon.vue` wrapper | Already a single icon set. Set stroke to 1.5 in `Icon.vue`. No new package. |
| Inter Variable via `@fontsource-variable/inter` | "System fonts" rule | Add Inter as fallback after `system-ui` (self-hosted, so no CDN). Apple devices still get the system font. **Needs your OK.** |
| Type scale 12/14/16/20/24/32/48, weights 400/500/600 | Pentagram-style `.t-*` classes | Map the existing `.t-*` classes onto that scale. Cap weights at 600. |
| Radius 12 cards / 8 controls | Check current values in `app.css` | Align. |
| Flutter section | No `pubspec.yaml` | Skip. |
| Playwright MCP screenshots | Not available in this session | See section 4. |

## 3. Apple principles to apply (to be confirmed from the live sites)

Large whitespace; one large title per page; thin hairline dividers; 12px cards on a tinted page; a single accent; translucent sticky bar with blur; inset grouped lists; restrained motion (150–250ms, opacity/transform only); minimum 44px tap targets.
I will open apple.com, /iphone and the HIG once a browser tool is available, and record exact measurements here before coding. Principles only. No Apple images, logos, copy or fonts.

## 4. Tooling gap

I have no Playwright MCP in this session, so I cannot take the `/ui-audit/before` and `/ui-audit/after` screenshots or browse apple.com. Options:
a) You enable the Playwright MCP and I continue.
b) I install `playwright` as a dev dependency and script the screenshots myself (adds a package plus a browser download, ~150 MB).
c) You review each page by eye and I skip screenshots.

## 5. Work order (after approval)

1. Tokens and base styles in `app.css` (neutrals, radius, shadow scale, type scale, Inter fallback, tabular-nums utility for money/IDs/counts).
2. App shell: translucent sticky top bar, sidebar that becomes a slide-over sheet under 1024px (`AppShell`, role layouts).
3. Shared components: Btn, Card, DataTable (sticky header, right-aligned numbers, light dividers), Badge/Status pills, Field/inputs (visible labels, error under field), Modal, new Sheet/Tooltip/Toast, EmptyState, Skeleton, PageHead.
4. Pages, most-used first: Business Overview, Investor Overview and Marketplace, Applications, Staff Credit Queue and Finance Overview, then the rest, then Public and Auth.
5. After each page: screenshots at 1440 and 390, compare, fix, commit.

Out of scope: routes, controllers, models, validation, migrations, business logic.

## 6. Done criteria

After-screenshots for every page at both widths; body contrast 4.5:1 or better; visible focus rings; every icon-only button has an `aria-label`; `npm run build`, `npm run typecheck` and `php artisan test` pass; no console errors.
