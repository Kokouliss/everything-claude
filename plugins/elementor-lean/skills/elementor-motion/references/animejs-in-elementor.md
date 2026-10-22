# anime.js in Elementor

anime.js is the fourth rung, between Elementor Interactions and GSAP. Use it when a section needs real
choreography (a heading, then cards staggered from the centre, as a timeline) and does not need pins,
horizontal scroll or SplitText. Scroll-scrubbed effects stay in CSS `view()`.

For the full API, load `anthropic-skills:animejs`, then apply the overrides on this page. That skill's
size table ("full bundle ~24.5 KB") is stale. So is its install advice (unpinned CDN UMD, npm into
React). Neither applies here.

## 1. Version and licence

- **Pin 4.5.0** (22 Jun 2026, MIT): `npm i -E animejs@4.5.0`. Commit `package-lock.json` and the
  built file.
- **5.0 is coming.** 5.0.0-beta.1 (5 Aug 2026) and beta.2 (17 Aug 2026) are on the `beta` tag.
  `/npm/animejs/` will move to 5.x on release, and the 5.0 beta UMD is 120 KB raw, so it is not
  smaller.
- **Breaking changes inside 4.x:**
  - **4.4.0:** transforms render in a fixed order whatever order you declare them in; `matrix` /
    `matrix3d` are no longer animatable; function-based values and `stagger` callbacks receive
    `(target, index, targets, prevTween)`.
  - **4.5.0:** pseudo-linear RGB blending, so colour tweens look different from earlier versions.
- **Licensing:** anime is MIT. GSAP uses a free, proprietary "no charge" licence that covers all of
  its plugins.

Sources: [jsDelivr versions](https://data.jsdelivr.com/v1/package/npm/animejs),
[5.0.0-beta.2 files](https://data.jsdelivr.com/v1/package/npm/animejs@5.0.0-beta.2/flat).

## 2. Modules and sizes (4.5.0, gzip)

| What you import | Gzip |
|---|---|
| Full UMD / ESM bundle | 40.5 / 40.8 KB (animejs.com quotes about 41 KB), **never ship** |
| `animate` + `stagger`, tree-shaken | **14.4 KB** |
| `animate` + `onScroll` + `stagger` | 18.9 KB (51.2 KB raw) |
| `waapi` (`animejs/waapi`) + `stagger` (`animejs/utils`) | **7.6 KB** (17.4 KB raw) |
| Reveal module (timer + animation + scroll + stagger) per animejs.com | about 15.5 KB |

The table is measured with esbuild and gzip -9, except the animejs.com figure.

The package exports these subpaths: `.`, `./animation`, `./timeline`, `./timer`, `./events`
(`onScroll`), `./waapi`, `./utils`, `./svg`, `./text` (`splitText`), `./scope`, `./layout`,
`./easings`, `./adapters/three` (checked in the 4.5.0 package.json). The `y` shorthand maps to
`translateY`. Eases are named `outExpo`, `inOutQuad` and so on. `stagger` accepts
`from: 'first' | 'center' | 'last'` or an index.

## 3. When to choose anime.js over GSAP

| Need | Choose |
|---|---|
| Reveal, fade or stagger that CSS `view()` or WAAPI can do | Neither: recipes 1–2 (0 KB) |
| Choreographed timeline: heading then items, from the centre, grid stagger | **anime.js** (14.4 KB) |
| Simple eased stagger only | anime `waapi.animate` (7.6 KB), or plain WAAPI in recipe 1 (0 KB) |
| Pin, horizontal scroll, scrub with a pinned section, robust line splitting | **GSAP** (46.3–49.9 KB) |
| Scroll-linked progress or parallax | CSS `view()` / `scroll()`. anime's `onScroll` is main-thread scroll tracking, so do not use it for long parallax pages |
| Same page already ships Elementor Interactions or GSAP | Do not add anime. One library family per page |

Other anime rules:

- **`waapi.animate`:** prefer it for plain reveals. On Safari, keep its eases as CSS `cubic-bezier()`
  strings, because Safari drops hardware acceleration for custom `linear()` easings. Check that
  anime's waapi passes a CSS string through as-is (**verify**).
- **`createDrawable()`:** it animates stroke dashes, which is paint and outside the transform/opacity
  rule. Use recipe 13 instead.
- **`splitText`:** it exists in v4 (`animejs/text`). Read its options in `anthropic-skills:animejs`
  before use (**verify**). For line reveals, the GSAP path in recipe 3 is the tested one.

## 4. Build and load

Source: `wp-content/themes/CHILD/src/motion.js` (below). Build it once per change:

```sh
npm i -E animejs@4.5.0
npx esbuild src/motion.js --bundle --minify --format=esm --target=es2020 --outfile=assets/js/motion.js
gzip -9 -c assets/js/motion.js | wc -c   # must stay <= 20000
```

- **Upload:** upload `assets/js/motion.js` with `novamira/write-file`. It is the same file name the
  GSAP tier uses, so `motion-loader.js` and the PHP enqueue in `gsap-in-elementor.md` §4 work
  unchanged.
- **Trigger:** the loader imports it on the first scroll, wheel, touchmove or keydown when the page has
  `[data-anim]`. Never put it in an HTML widget, and never load it from a CDN.
- **Globals:** the ESM bundle defines no global `anime`. Dynamic Content for Elementor (Dynamic.ooo)
  ships anime v3.2.2 as a global `anime`, and the v4 UMD would overwrite it. Check
  `grep -rl animejs wp-content/plugins/*/assets` before adding anime.
- **Cache-plugin exclusions:** the same as the GSAP table (`nowprocket`, `data-no-optimize`, the
  Perfmatters and FlyingPress keyword `motion-loader`).

## 5. `src/motion.js` (anime.js tier, complete)

The markup contract is shared with GSAP, so a page can switch tiers without touching Elementor:

- `data-anim="fade-up"` on one element;
- `data-anim="stagger"` on a container, staggering its children, with an optional
  `data-anim-items=".e-loop-item"` and `data-from="center"`;
- `data-anim="sequence"` on a container, where the first child (the heading) enters, then the rest
  stagger in.

```js
// src/motion.js: anime.js tier. Bundled by esbuild; imported by motion-loader.js after the first movement.
import { animate, createTimeline, stagger } from 'animejs';

const IN = { opacity: [0, 1], y: [24, 0], duration: 700, ease: 'outExpo' };
const parts = (el) => (el.dataset.anim === 'fade-up'
  ? [el]
  : [...(el.dataset.animItems ? el.querySelectorAll(el.dataset.animItems) : el.children)]);

const PLAY = {
  'fade-up': (el, targets) => animate(targets, IN),
  stagger: (el, targets) => animate(targets, { ...IN, delay: stagger(70, { from: el.dataset.from || 'first' }) }),
  sequence: (el, [head, ...rest]) => {
    const tl = createTimeline().add(head, IN);
    if (rest.length) tl.add(rest, { ...IN, delay: stagger(70) }, '-=500');
  },
};

if (!matchMedia('(prefers-reduced-motion: reduce)').matches) {
  const io = new IntersectionObserver((entries) => {
    for (const { target, isIntersecting } of entries) {
      if (!isIntersecting) continue;
      io.unobserve(target);
      PLAY[target.dataset.anim](target, parts(target));
    }
  }, { threshold: 0.15 });

  for (const el of document.querySelectorAll('[data-anim]')) {
    // On screen (or display:none) when the bundle arrives: leave it static, never snap it back.
    if (!PLAY[el.dataset.anim] || el.getBoundingClientRect().top < innerHeight) continue;
    for (const t of parts(el)) t.style.opacity = '0'; // hidden only by JS, only off screen
    io.observe(el);
  }
}
```

With this exact file, esbuild produces 40.0 KB raw / 15.8 KB gz. It ran in Chrome 148 with the shared
loader with no errors, ending every target at opacity 1 (local check on 2026-10-22).

How it behaves:

- It runs once, after the first movement.
- Elements already in view stay static.
- Elements below the fold are hidden inline and animate when 15% visible.
- Under reduced motion nothing is touched, so all content stays visible.
- With JS off, Delay-JS stuck or the module blocked, nothing was ever hidden.

## 6. Rules

- Use it only below the fold, never in the first top-level container, header or footer, and never on
  the LCP text or image.
- Use at most 20 animated elements per page and one mechanism per element (no `_animation`,
  Interactions or Motion Effects on the same node).
- Animate only `opacity` and `y` / `x` / `scale` / `rotate`.
- Never animate SVG attributes, colours or `filter` on many nodes.
- Use IntersectionObserver for triggers and CSS `view()` for scrubbing. Use `onScroll({ sync: true })`
  only for a short, desktop-only, below-fold effect.
- Pin the version, bundle per site and keep the bundle under 20 KB gz.

## 7. Verify

- `node -e "console.log(require('animejs/package.json').version)"` in the build folder prints `4.5.0`.
- The rendered HTML contains no `cdn.jsdelivr.net/npm/animejs` or `esm.sh/animejs`.
- In a mobile Lighthouse median of 3, `network-requests` has `motion-loader.js` only and
  `bootup-time` is unchanged.
- After one scroll in Puppeteer, `motion.js` loads once with `bootup-time` under 80 ms. A DevTools
  scroll trace shows no `scroll` handler over 4 ms.
- In a reduced-motion Playwright run, `document.getAnimations().length === 0` after scrolling, and
  the screenshot equals the static baseline.

Sources: [onScroll](https://animejs.com/documentation/events/onscroll),
[WAAPI](https://animejs.com/documentation/web-animation-api),
[hardware-accelerated animations](https://animejs.com/documentation/web-animation-api/hardware-accelerated-animations).
