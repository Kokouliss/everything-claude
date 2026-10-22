---
name: elementor-motion
description: Adds premium motion to Elementor 4.x pages after they pass the static 95+ mobile PageSpeed gate, choosing the cheapest technique (CSS, Web Animations, Elementor Interactions, anime.js or GSAP), loading any library after LCP, and proving the score, CLS, INP and reduced-motion behaviour afterwards. Use when asked to animate, add animation, use GSAP or anime.js, add a scroll animation, parallax, a micro-interaction, a hover effect or a page transition, or to make it feel premium.
---

# Motion on lean Elementor sites

The owner wants hotels, villas, the water park and restaurants to feel alive, and they still have to
score 95–100 on mobile. The agency's own sites show what goes wrong:

- a fade-in on the hero h1 cost 7.8 s of render delay;
- GSAP loaded at page load cost 1.78 s of bootup and 358 ms TBT;
- a three.js hero cost 142 s TBT.

Motion is affordable only when it costs zero bytes and zero main-thread time before LCP.

## Read first

- `references/motion-recipes.md`: 16 copy-ready effects with code, cost and what not to do. Start
  here.
- `references/motion-budget.md`: measured costs per technique, per-page limits, the measurement
  recipe.
- `references/gsap-in-elementor.md`: licence, sizes, the PHP enqueue, the shared `motion-loader.js`,
  the GSAP `motion.js`, the conflict table.
- `references/animejs-in-elementor.md`: v4.5.0 modules, when to prefer it, the build, the anime
  `motion.js`.
- Plugin root: `references/levers.md` §7.1 and §7.6, `references/sites-in-the-wild.md` rules 3, 4,
  13 and 14, and `references/breakage-checklist.md`.

## The budget (never trade it)

- No animation above the fold at load, and none on the LCP element or its ancestors. An
  `elementor-invisible` LCP costs 3.6–7.8 s.
- The LCP is text or one `<img>` (rules 3–4).
- Third-party JS ≤ 150 KB before interaction. TBT < 300 ms. CLS ≤ 0.05.
- Animate `transform` and `opacity` only.
- Respect `prefers-reduced-motion`: CSS inside `@media (prefers-reduced-motion: no-preference)`, JS
  behind the same `matchMedia` check.
- JS animation libraries load after LCP, on the first scroll, wheel, touchmove or keydown. They are
  never in the load path.
- Never ScrollSmoother, Lenis or Locomotive on mobile. This plugin ships none.
- Content is visible without JS. CSS never pre-hides content. JS hides only off-screen elements it is
  about to animate.

When another skill or a brief conflicts with this list, the list wins. Offer the owner the
below-the-fold or desktop-only version.

## When motion is added

1. **Static gate first.** The page must already pass the `elementor-build` gate: score ≥ 95,
   LCP ≤ 2.0 s, CLS ≤ 0.05, zero render-blocking resources, LCP text or an `<img>`.
   - Record that run as the baseline (breakage-checklist §1): the Lighthouse median of 3 and its
     JSON, the LCP node and phases, the network log, screenshots at 390 and 1440 px, and CLS.
   - Motion never goes onto a failing page. Fix it in `elementor-build` / `elementor-design` first.
2. **Write a motion plan** from elementor-design's hand-off. For each effect, write down:
   - the element, by Global Class or attribute;
   - the recipe and its rung on the ladder below;
   - the trigger;
   - the behaviour on phones;
   - the behaviour under reduced motion.
3. **Check the plan against the limits** (motion-budget §2):
   - ≤ 20 animated elements, ≤ 2 Interactions, one mechanism per element, one library family per
     page;
   - nothing in the first top-level container, header or footer;
   - ≤ 1 pin, ≤ 3 split headings, ≤ 1 marquee.
4. **Build** the cheapest rung for each effect, then run the acceptance tests.

## Decision tree: effect → cheapest premium implementation

Go down the ladder and stop at the first rung that delivers the effect as designed.

1. **CSS (0 KB):**
   - transitions for hover, press and focus;
   - scroll-driven `view()` / `scroll()` inside `@supports` for reveals, parallax, curtains and
     progress;
   - `@starting-style` for panels and popups;
   - `@view-transition` for page-to-page.
2. **Web Animations API + IntersectionObserver (0 KB library):** eased reveals, staggers, counters and
   tab fades (`motion-lite.js`).
3. **Elementor Pro Interactions (v4):** ≤ 2 `scrollIn` fades on a page with no other motion JS.
   - Cost: 49.5 KB gz, about 54 KB with Pro triggers.
   - They ignore reduced motion, so use fade. Slide or scale only when the item excludes mobile.
   - Never `load`. Never "While scrolling" on mobile or relative to the viewport.
   - The item shape is in elementor-design `elementor-implementation.md` §10.
4. **anime.js 4.5.0 (14.4–18.9 KB):** choreography CSS cannot express, such as a timeline (heading,
   then items) or a stagger from the centre or across a grid.
5. **GSAP 3.15.0 (46.3 KB, 49.9 KB with SplitText):** pins, desktop horizontal scroll, scrubbed
   sequences, SplitText, `matchMedia` revert.

| Effect | Rung | Recipe |
|---|---|---|
| Reveal on scroll | 1, with a 2 fallback | 1 |
| Staggered cards | 2 (CSS variant in 1) | 2 |
| Split-text heading below the fold | 5 | 3 |
| Parallax | 1 | 4 |
| Number counter | 2 | 5 |
| Marquee | 1 | 6 |
| Sticky stacking cards | 1 (no animation at all) | 7 |
| Horizontal scroll | 5, desktop only; phones get scroll-snap | 8 |
| Image mask reveal | 1 (transform curtain) | 9 |
| Card, button and link hover | 1 | 10 |
| Magnetic button / cursor follower | 2, desktop pointer only | 11 |
| Page transitions | 1 | 12 |
| SVG line draw | 1 (transform wipe) | 13 |
| Progress bar | 1 | 14 |
| Accordion, tabs, off-canvas | 1 (+2 for tabs) | 15 |
| Something premium in the hero | 1, after load + 1.5 s, never on the LCP | 16 |
| Choreographed section entrance | 4 | anime `sequence` |

Never use:

- Elementor entrance animations above the fold.
- Motion Effects on mobile, or Blur at all.
- An Animated Headline or typewriter in the hero.
- Lottie, except one lazy-loaded Lottie below the fold.
- `setInterval` marquees.
- Animation add-on packs or three.js heroes.
- Animation of `height`, `width`, `top`, `left`, `box-shadow`, `filter`, `background-position` or
  `stroke-dashoffset`.

## Loading rules

- **One script in the HTML.** `motion-loader.js` (a module under 1 KB) is the only motion script in
  the HTML.
  - It imports `motion-lite.js` and `motion.js` (anime or GSAP) on the first `scroll`, `wheel`,
    `touchmove` or `keydown`, only when the page has their markers.
  - It imports `motion-pointer.js` on the first `pointermove`, desktop pointer only.
  - Under reduced motion it imports nothing.
  - It sets `html.after-lcp` at `load` + 1.5 s for recipe 16.
- **Never an IntersectionObserver for the library import.** With a 300 px margin it fires inside the
  412×823 lab viewport. Never `pointerdown` or `click` either: the module's parse lands inside INP.
  Idle is not enough: on a 4× slowed CPU it can still fall inside the TBT window.
- **Gate cost on width and pointer, accessibility on reduced motion.** Lighthouse emulates a touch
  phone with no reduced-motion preference. `(pointer: fine) and (min-width: 1025px)` keeps desktop
  effects off phones and out of the lab.
- **Leave on-screen elements alone.** Elements already on screen when JS arrives stay static. Never
  snap visible content back to a start state.
- **Self-host and pin.** Use `assets/js/vendor/gsap-3.15.0/*.min.js`, injected as classic scripts, or
  an esbuild bundle of `animejs@4.5.0`. No CDN, no `all.min.js`, no npm root ESM files.

## Where the code lives

- **Files:** child theme `assets/js/motion-loader.js`, `motion-lite.js`, `motion.js`,
  `motion-pointer.js` and `vendor/`, plus CSS appended to the child theme's `site.css` (no second
  stylesheet). Write them with `novamira/write-file` (path relative to ABSPATH; check its arguments
  with `mcp-adapter-get-ability-info`).
- **Enqueue:** gsap-in-elementor §4, saved to `wp-content/novamira-sandbox/motion.php` (or
  `functions.php`).
  - Classic `wp_enqueue_script` plus `script_loader_tag`, which adds
    `type="module" nowprocket data-no-optimize="1" data-no-defer="1"`.
  - Not `wp_enqueue_script_module()`, which cannot carry those attributes.
- **No theme access:** one Pro Custom Code item (`<body> – End`, priority 10, Entire Site) that prints
  only the loader tag. Never put animation code in Custom Code, or `<script>` in an HTML widget.
- **Targeting Elementor markup:**
  - **CSS recipes:** V4 Global Classes, created with `novamira/elementor-create-global-class` (verify
    the arguments) and applied via `settings.classes`.
  - **JS hooks:** `data-anim`, `data-reveal` and `data-count`, written into the element's
    `attributes` (v4) or `_attributes: "data-anim|fade-up"` (v3 Pro). Copy the prop shape from an
    existing element with `elementor-get-content` (verify).
  - **Never select by** `.elementor-element-{id}`, `[data-id]` or a bare `.e-con` in shipped code. Ids
    change on import and duplicate, and `.e-con` also matches the hero. Use `data-id` only to find the
    node while writing.
  - Use `overflow: clip`, never `hidden`, on containers that hold `view()` timelines or sticky
    children.
  - Use Full-width containers where a recipe needs direct children. Boxed containers add
    `.e-con-inner`; verify.
- **After each write:**
  1. `elementor-clear-document-cache`, then `elementor-get-content`.
  2. If `interactions` changed, delete the `elementor-interactions-cache` postmeta and compare
     `#elementor-interactions-data` with the tree.
  3. Purge the page cache.

## Conflicts to clear

- **Delay-JS** must leave the loader untouched; the per-plugin settings are in the gsap-in-elementor
  §8 table.
  - LiteSpeed skips `type="module"` and `data-no-optimize`. Keep JS Combine off.
  - WP Rocket needs `nowprocket`.
  - Perfmatters and FlyingPress match keywords: add `motion-loader` and `/js/vendor/gsap-`.
  - On the cached HTML, view-source must show no `rocketlazyloadscript`, `litespeed/javascript` or
    `data-src`.
- **UCSS/RUCSS:** safelist `@view-transition`, `::view-transition`, `.after-lcp`, `.cursor-dot`,
  `[data-reveal]` and `[data-anim]`. Recheck after every purge.
- **Elementor entrance animations:** `elementor-invisible` hides the element until Elementor JS runs,
  and reduced motion never removes it. Set Entrance Animation = None on every element that carries
  another mechanism, and on everything in the first container and the header.
- **Pro Interactions:** a page with `data-anim` uses its library for every reveal and carries no
  Interactions.
- **Motion Effects and Pro Sticky:** no transform, Motion Effect or `will-change` on an ancestor of a
  pin or a sticky element. Keep the sticky header outside pinned trees.
- **Popups and Loop Grid:** re-initialise on the window `elementor/popup/show` event, and refresh
  after "Load more".
- **Dynamic.ooo:** it ships anime v3 as a global `anime`. The ESM bundle defines no global; never load
  the v4 UMD next to it.

## Acceptance tests

Run these on every page that received motion, in both languages, after purging. Keep the motion only
if every test is green.

1. **Lighthouse median of 3, before and after**, serial, same URL, same thresholds (motion-budget §4).
   - Score ≥ 95, LCP ≤ 2.0 s, TBT < 300 ms, CLS ≤ 0.05.
   - Regression: the score drops by 3 or more with non-overlapping ranges, LCP rises ≥ 200 ms, TBT
     ≥ 100 ms or CLS ≥ 0.02 (breakage-checklist §8).
   - The lab network log shows only `motion-loader.js` from the motion files, and
     `non-composited-animations` is empty.
   - The LCP node is unchanged, with no `elementor-invisible`, and Render Delay is under 300 ms and
     under 40% of LCP.
2. **Reduced-motion check** (motion-budget §4.5): no running animation, no `elementor-invisible`, no
   faded target, and a screenshot equal to the static baseline.
3. **CLS observer:** the breakage-checklist §6 observer with a scroll pass reads ≤ 0.05 and ≤ 0.02
   above the baseline. Read the source of any shift.
4. **INP via web-vitals** (motion-budget §4.4): scroll, then tap the menu, accordion, tab and booking
   CTA. Every INP must be under 200 ms. The menu must open on the first tap, before any scroll.
5. **`references/breakage-checklist.md`:** console and network diff, interaction smoke tests (popups,
   forms, booking), screenshot diff, the Elementor editor loads and saves, tracking still fires,
   Lighthouse last.
6. **Desktop proof at 1440 px:** every planned effect plays, and the trace shows no style or layout
   long frames during tweens and no idle periodic tasks.

Record the before/after medians and the motion plan in the handover.

## Hand-off to the local motion-quality skills

They decide how motion should **feel**. This skill decides where it lives and whether the gate
passes. When they disagree with the budget, the budget wins.

- **`anthropic-skills:emil-design-eng`** is the value system. Use it to decide whether an effect
  should exist at all.
  - House tokens `--ease-out`, `--ease-in-out`, `--ease-drawer`. Press 100–160 ms, UI under 300 ms.
  - The frequency gate: things used often or by keyboard (booking form, date picker, checkout) stay
    still.
  - No `scale(0)`.
- **`anthropic-skills:animate`** turns one approved effect into a spec: property, distance, duration,
  easing, stagger. Before its step 1, ask: "Is it above the fold or on the LCP? Then no." Map the spec
  onto a recipe.
- **`anthropic-skills:review-animations`** is the critique gate before publishing.
  - It needs files: snapshot `site.css`, the `motion*.js` files and the page `tree.json` into a local
    folder.
  - A **Block** finding means do not publish.
- **`design-motion-principles:design-motion-principles`** sets the lens and runs the audit.
  - Lens: hotels, villas and restaurants are marketing pages (Jakub, then Jhey; Emil for forms and
    navigation). The water-park shop is e-commerce (Jakub and Emil; Jhey for product showcase).
  - Audit mode with terminal output is the anti-slop lint: any pulse, 3 or more identical hover
    scales, 2 or more staggered lists per view, blur everywhere.
- **Also useful:**
  - `anthropic-skills:animation-vocabulary` names what a client describes, so you can match it to a
    recipe.
  - `anthropic-skills:animejs` is the v4 API map. Ignore its sizes and its CDN install.
- **Not for this stack:**
  - `ecc:motion-foundations`, `ecc:motion-patterns`, `ecc:motion-advanced` (React only).
  - tasteskill's hero load sequence and its "under eight moving elements" fail.
  - ui-ux-pro-max `gsap.from` hero rows.
  - Any unpinned CDN script, Lenis or ScrollSmoother setup.
