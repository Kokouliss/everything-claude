# Motion budget: what each technique costs

Motion is affordable on mobile only when it costs zero bytes and zero main-thread time before LCP.
The bytes matter less than when the code runs.

**Status labels:**

- *(measured, unreviewed)* is a local Lighthouse run on 2026-10-21 that no critic reviewed. Quote
  these numbers to a client only after re-running them on the client's own page.
- *(agency)* is the agency's own measured sites (levers.md 7.1/7.6, sites-in-the-wild).
- Everything else is from reviewed findings or vendor files measured with `gzip -9`.

**Lab conditions for the measured rows:**

- Lighthouse 12.8.2 CLI, mobile preset: 412×823, 4× CPU, simulated 150 ms RTT / 1.6 Mbps.
- Median of 3 runs, `benchmarkIndex` 2,200–3,000.
- The test page has 30 reveal cards. The bare page scores 100: LCP 751 ms, TBT 0, main thread 170 ms.

## 1. Cost per technique

| Technique | Bytes (gz) | Loads | LCP | TBT / main thread | INP | CLS |
|---|---|---|---|---|---|---|
| CSS transitions (hover, press, focus) | 0 | with CSS | none | none | none (no JS) | 0 (transform) |
| CSS scroll-driven `view()` / `scroll()` | 0 JS | with CSS | none below the fold. A fold-straddling subject is painted half-faded | off the main thread | none | 0 |
| `@starting-style` panels | 0 | with CSS | none if the element is hidden at load | none | none | 0 |
| Cross-document View Transitions | 0 | with CSS | unchanged (cold navigations never transition) | none | none | 0 |
| CSS transition + IntersectionObserver reveal, 30 cards | about 0.3 KB | at load | **751 ms = bare**, score 100 *(measured, unreviewed)* | TBT 0, main thread 164 ms *(measured, unreviewed)* | none | 0 |
| WAAPI + IO (`motion-lite.js`) | 0 library, about 0.5–1 KB own | first scroll | none | none in the lab | small | 0 |
| CSS keyframe marquee | 0 | with CSS | none | main thread 155 ms = bare *(measured, unreviewed)* | none | 0 |
| `setInterval` marquee writing `left` | about 0 | at load | none | TBT 0 but **+413 ms** main thread that never stops *(measured, unreviewed)* | worse | layout every tick |
| Elementor entrance animation (`elementor-invisible`) | 2.6 KB CSS (animations.min.css, 18.5 KB raw) | at load | **Render Delay 7,831 ms** of 8.95 s (voreniahorizon h1), 3,620 ms (cretaforums) *(agency)* | none | none | none |
| Elementor hover animations (`hover.min.css`) | 1.2 KB | only when used | none | none | none | none |
| Elementor V4 Interactions (Motion 11.13.5 + 2 handlers) | **49.5 KB** (42.2 + 4.4 + 3.0); Pro triggers about 54 KB | footer, only on pages with one | a `load` trigger flashes (visible, hidden, fade) | target bootup under 100 ms | "While scrolling" relative to the viewport is main-thread per frame | 0 |
| Pro Motion Effects | runtime in Pro `frontend.min.js` (24.6 KB, on every Pro page) | at load | none if below the fold | per-frame jQuery scroll + rAF | jank on mobile | none |
| anime.js 4.5.0, tree-shaken | **7.6** (waapi + stagger), **14.4** (animate + stagger), **18.9** (+ onScroll); full 40.5 | first scroll | none in the lab | target bootup under 80 ms | none | 0 |
| GSAP 3.15.0 core + ScrollTrigger | **46.3** (49.9 with SplitText) | first scroll | **751 ms = bare**, score 100 when loaded after `load` + 1.5 s or on first intent *(measured, unreviewed)* | none in the lab | none | 0 |
| The same GSAP as a sync `<script>` | 46.3 | at load | **1,563 ms**, score 99 *(measured, unreviewed)* | bootup 204 ms, library CPU 266 ms | none | 0 |
| The same GSAP with `defer` | 46.3 | at load | **1,709 ms** (Render Delay 1,157 ms): Lantern charges every script fetched before LCP *(measured, unreviewed)* | none | none | 0 |
| gsap + ScrollTrigger + SplitText at load (platonos) | about 50 | at load | none | **1.78 s bootup, TBT 358 ms, score 84** *(agency)* | none | 0 |
| Motion (motion.dev) 14.5.0 | 56.9 full, 24.6 (`animate` + `inView`), 3.4 (`motion/mini`) | not used | — | — | — | — |
| Lottie in the hero (lottie-web 5.13.0 + 65 KB JSON, autoplay) | 76.1 + JSON | at load | **2,312 ms**, score 95 (range 92–95) *(measured, unreviewed)* | TBT 85 ms, bootup 990 ms, library CPU 1,395 ms | loop drain | none |
| Elementor Pro Lottie (lottie-web 5.6.8) | 63.2 + JSON | lazy, on viewport | none below the fold | none | none | none |
| dotLottie web 0.81.0 | 32.9 + **496 KB WASM** | never | — | — | — | — |
| Lenis 1.3.27 | 5.4 | never on mobile | none | rAF every frame, lagged 43% behind wheel input *(measured, unreviewed)* | worse | none |
| ScrollSmoother | 5.5 + ScrollTrigger | never | none | JS-thread scrolling with `normalizeScroll` | worse | breaks `position: fixed` |
| three.js hero (limnoupolis) | — | at load | — | **TBT 142 s** *(agency)* | — | — |

Notes on the table:

- **Motion (motion.dev):** do not add it as a second engine. Elementor Interactions already ship
  Motion 11 on pages that use them.
- **Lottie:** prefer recipe 13 or an animated AVIF/WebP `<img>`.

Sizes:

- GSAP and anime.js files were measured from jsDelivr (gsap 3.15.0, animejs 4.5.0).
- Elementor runtimes were measured from limnoupolis.gr (Elementor 4.3.5, Pro 4.3.1).
- Lottie, dotLottie, Motion and Lenis sizes are *(measured, unreviewed)*.

## 2. Per-page limits

| Limit | Value | Source |
|---|---|---|
| Animated elements (`elementor-invisible`, motion-fx, `data-anim`, `data-reveal`, `.reveal`, Interactions) | ≤ 20, none in the first top-level container, header or footer | rule 13 |
| Elementor Interactions | ≤ 2, `scrollIn` only, never `load` | levers 7.6 |
| Library families per page | 1 (Interactions' Motion, anime.js or GSAP) | GSAP and anime findings |
| Motion JS before interaction | 0 bytes; all motion JS after first movement ≤ 50 KB gz | budget (unreviewed) |
| anime.js bundle | ≤ 20 KB gz | anime finding |
| Pins | ≤ 1, desktop only | GSAP finding |
| SplitText headings | ≤ 3, h2/h3 below the fold | GSAP finding |
| Marquees | ≤ 1, pausable, below the fold | WCAG 2.2.2 |
| Lottie | ≤ 1, below the fold, Lazy Load on, JSON ≤ 50 KB with no embedded bitmaps | anime finding |
| One mechanism per element | entrance animation **or** Interaction **or** `data-anim` **or** Motion Effect | GSAP finding |

## 3. Lighthouse facts that decide motion

- **Scoring:** weights are TBT 30, LCP 25, CLS 25, FCP 10, SI 10. The TBT p10 is 200 ms. 95+ needs
  TBT ≈ 0 and CLS = 0 (breakage-checklist §8).
- **What the mobile lab emulates:** 412×823, DPR 1.75, a touch device (`pointer: coarse`,
  `hover: none`). It reports `prefers-reduced-motion: no-preference`, has no Save-Data and never
  scrolls or clicks.
  - So width and pointer gates protect the **score**, and reduced motion protects **users**.
  - A movement-gated loader executes nothing in the lab.
  - Lighthouse does not measure INP; use the probe in §4.
- **LCP candidates** ([web.dev LCP](https://web.dev/articles/lcp)):
  - An element at `opacity: 0` is not a candidate, so a CSS-hidden hero makes LCP wait for the script.
  - A transform-only start does not move LCP but flashes.
  - Size and position changes never create new candidates.
- **CLS:** composited `transform` animations do not count toward CLS. Any box change (`height`,
  `display`, inserted banners) outside a 500 ms input window does
  ([web.dev animations](https://web.dev/articles/animations-guide)).

## 4. Measurement recipe

Run every step on the static page (the baseline) and again after motion, with the same URL and the
same thresholds.

**1. Lighthouse, 3 serial runs.** Run nothing else on the box. Discard runs with
`benchmarkIndex` < 1,000.

```sh
URL="https://example.gr/page/"
for i in 1 2 3; do
  npx -y lighthouse@13 "$URL" --only-categories=performance --output=json \
    --output-path="run-$i.json" --chrome-flags="--headless=new" --quiet
done
jq -s '[.[] | {s: (.categories.performance.score * 100), lcp: .audits["largest-contentful-paint"].numericValue,
  tbt: .audits["total-blocking-time"].numericValue, cls: .audits["cumulative-layout-shift"].numericValue,
  bench: .environment.benchmarkIndex}]
  | {score: (map(.s) | sort | .[1]), lcp: (map(.lcp) | sort | .[1] | round), tbt: (map(.tbt) | sort | .[1] | round),
     cls: (map(.cls) | sort | .[1]), bench: map(.bench)}' run-1.json run-2.json run-3.json
```

**2. Motion files in the lab run.** Only `motion-loader.js` may appear. Also check the composited
animations.

```sh
jq -c '{motion: [.audits["network-requests"].details.items[].url
  | select(test("gsap|animejs|/motion(-lite|-pointer)?(\\.min)?\\.js|lottie|lenis|three|swiper"))],
  noncomposited: (.audits["non-composited-animations"]?.details?.items // [] | length)}' run-2.json
```

- `motion` must be `[]` and `noncomposited` must be 0.
- The `non-composited-animations` id exists in 12.8.2; check that it still exists on your Lighthouse
  13 build (**verify**).
- For the LCP node and its phases, use the breakage-checklist §8 jq (`lcp-breakdown-insight`): Render
  Delay < 300 ms and < 40% of LCP, and no `elementor-invisible`.

**3. Bytes of a file.**

```sh
curl -sL https://cdn.jsdelivr.net/npm/gsap@3.15.0/dist/gsap.min.js | gzip -9 | wc -c
curl -sL "https://example.gr/wp-content/themes/CHILD/assets/js/motion.js" | gzip -9 | wc -c
```

**4. INP probe after a scroll and taps.**

- Lighthouse cannot measure INP, so use Playwright with the web-vitals attribution build:
  `npm i -E playwright web-vitals`. These field names were checked in web-vitals 6.2.3.
- Tap what users tap on that page: menu burger, accordion, booking CTA, a tab.
- Pass: every INP value < 200 ms. The `motion` list proves the library loaded only after the scroll.
- Field: CrUX INP p75 after 28 days.

```js
// inp-probe.mjs: node inp-probe.mjs <url> "<tap selector>" ["<tap selector>" ...]
import { chromium, devices } from 'playwright';

const [url, ...taps] = process.argv.slice(2);
const browser = await chromium.launch();
const context = await browser.newContext({ ...devices['Pixel 7'] });
await context.addInitScript({ path: 'node_modules/web-vitals/dist/web-vitals.attribution.iife.js' });
await context.addInitScript(() => {
  window.__inp = [];
  webVitals.onINP((m) => window.__inp.push({
    value: m.value,
    target: m.attribution.interactionTarget,
    input: m.attribution.inputDelay,
    processing: m.attribution.processingDuration,
    presentation: m.attribution.presentationDelay,
  }), { reportAllChanges: true });
});
const page = await context.newPage();
await page.goto(url, { waitUntil: 'load' });
await page.waitForTimeout(3000);
for (let i = 0; i < 6; i++) { await page.mouse.wheel(0, 700); await page.waitForTimeout(500); }
for (const sel of taps) { await page.tap(sel); await page.waitForTimeout(600); }
const result = await page.evaluate(() => ({
  inp: window.__inp,
  motion: performance.getEntriesByType('resource').map((r) => r.name).filter((n) => /gsap|anime|motion/.test(n)),
}));
console.log(JSON.stringify(result, null, 2));
await browser.close();
```

**5. Reduced-motion pass.** Pass: `running` is 0, `invisible` is 0, `hidden` is 0, and the screenshot
equals the static baseline (breakage-checklist §2). Elementor Interactions ignore reduced motion; this
pass is where that shows.

```js
// reduced-motion.mjs: node reduced-motion.mjs <url>
import { chromium, devices } from 'playwright';

const browser = await chromium.launch();
const context = await browser.newContext({ ...devices['Pixel 7'], reducedMotion: 'reduce' });
const page = await context.newPage();
await page.goto(process.argv[2], { waitUntil: 'load' });
for (let i = 0; i < 8; i++) { await page.mouse.wheel(0, 700); await page.waitForTimeout(400); }
const r = await page.evaluate(() => ({
  running: document.getAnimations().filter((a) => a.playState === 'running').length,
  invisible: document.querySelectorAll('.elementor-invisible').length,
  hidden: [...document.querySelectorAll('[data-anim], [data-reveal], .reveal, [data-interactions]')]
    .filter((el) => getComputedStyle(el).opacity !== '1').length,
}));
await page.screenshot({ path: 'reduced-motion.png', fullPage: true });
console.log(r);
await browser.close();
```

**6. CLS during scroll.**

- Use the breakage-checklist §6 observer, installed before navigation, with a viewport-step scroll
  pass and the session-window maths.
- Pass: CLS ≤ 0.05 and a rise of no more than 0.02 over the static baseline. Read `sources` for the
  culprit node.

**7. Desktop proof that the motion exists.**

- At 1440 px with a mouse, scroll the page and record a DevTools Performance trace.
- Every planned effect plays.
- No frame over 16.7 ms is attributed to Recalculate Style or Layout during the tweens.
- No `scroll` handler runs over 4 ms.
- The idle page shows no periodic JS tasks.

Sources: [scroll-driven animations](https://developer.chrome.com/docs/css-ui/scroll-driven-animations),
[Lighthouse constants.js](https://raw.githubusercontent.com/GoogleChrome/lighthouse/main/core/config/constants.js),
[scheduler.yield](https://developer.chrome.com/blog/use-scheduler-yield),
[long animation frames](https://developer.chrome.com/docs/web-platform/long-animation-frames),
[motion.dev performance](https://motion.dev/docs/performance).
