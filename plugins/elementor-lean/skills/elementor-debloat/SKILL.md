---
name: elementor-debloat
description: Remove bloat from an existing Elementor site with reversible, measured steps. Use after elementor-audit, or when asked to speed up, clean, optimise or debloat an Elementor site.
---

# Elementor debloat

Fix the biggest finding first, measure, keep or undo. One change at a time.

## Read first

- `references/levers.md` (plugin root, two levels up): the ordered playbook — what each lever is
  worth, exact settings per cache plugin (LiteSpeed Cache, FlyingPress, Perfmatters, WP Rocket,
  W3TC), when it breaks and the exclusions that fix it, and the **never auto-enable** list.
- `references/breakage-checklist.md`: the harness to run after every change (screenshots, console
  errors, interaction smoke tests, tracking still fires, Lighthouse median-of-3). "Without breaking
  anything" means this checklist passed, not that the page looked fine once.

## Rules

- Audit first (`elementor-audit`). No audit, no changes.
- Staging if one exists. On production, only the reversible actions below.
- Before any change: run Lighthouse on the page you will judge by, and take a screenshot.
- After every change: clear Elementor cache (the script does it), re-run Lighthouse, screenshot again. If the score did not improve or the page looks different, `undo`.
- Never deactivate or delete a plugin, delete revisions, or change page content without the user's explicit OK in this conversation. Never touch Novamira itself.
- Record what was changed and the before values in Novamira memory (`novamira/memory-save`, key `elementor-lean-log`) so the next session can see it.

## Automatic actions (`scripts/debloat.php`)

Run with `novamira/execute-php`. Set `$actions` at the top of the script to the list you want.
Every action saves its before-value in the option `elementor_lean_undo`; `undo` restores all.

| Action | What it does | Reversible | Needs OK |
|---|---|---|---|
| `expired_transients` | deletes expired transients | n/a (they are expired) | no |
| `fa4_shim_off` | turns off the Font Awesome 4 compatibility CSS — only if no `fa fa-` icons are found | yes | no |
| `optimized_markup_on` | enables Elementor's reduced DOM output | yes | visual check |
| `element_cache_on` | sets element cache to 24h when it was disabled | yes | check dynamic widgets (bookings, filters) |
| `video_lazy` | sets lazy-load on every video widget; backs up each page's data first | yes | visual check |
| `litespeed_optimize` | LiteSpeed Cache: Google Fonts async, CSS/JS minify, localize, deferred JS; purges. Does not enable lazy images (caused CLS 0.107 on a test site) | yes | visual check (deferred JS) |
| `revisions_prune` | deletes revisions older than `$revision_days` (default 90) | **no** | **yes** |
| `undo` | restores every saved before-value and page backup | — | no |

## Manual fixes (the ones that usually matter most)

See `references/fixes.md` for the full list with risk notes. Headlines:

- **Hero image lazy-loaded** → in the widget, set Image → Lazy Load to No, or add `fetchpriority="high"` via the theme. One change, often +10 points.
- **Tag Manager / Facebook pixel** → one GTM container only; delete duplicate gtag tags; load pixel through GTM; delay non-essential tags (Perfmatters / FlyingPress "delay JS"). Biggest TBT win on shop sites.
- **Fonts** → max 2 families, 2–3 weights each; convert `.woff`/`.ttf` custom fonts to `.woff2`; set only the weights used in Google Fonts; keep `font-display: swap`.
- **Render-blocking plugin CSS** on pages that do not use it → with Perfmatters/FlyingPress/LiteSpeed, unload it per page (jQuery UI, booking calendar, WooCommerce on non-shop pages).
- **Animations above the fold** → remove entrance animations and motion effects from the first screen.
- **Duplicate plugins** → list them for the user with what each does; deactivate only on their OK.
- **3D / WebGL / background video in the hero** → replace with a poster image, load the heavy thing on click or below the fold.

## Report back

Before/after table (score, LCP, CLS, TBT, KB, requests) per page, list of changes applied,
list of changes proposed and waiting for OK, and the undo instruction.
