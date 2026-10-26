# Breakage checklist — proving nothing broke

One lever at a time. Before it: record the exact before-values and a baseline (screenshots, console, CLS, tracking, Lighthouse). After it: purge, run the gates below in order (cheapest and most decisive first), keep the step only if every gate is green, and on any red run the undo for that one step — which only works because the step recorded its before-values first. "Without breaking anything" means this chain passed on the pages where the change bites, not that the home page looked fine once. Staging first where one exists, then production with the same recorded key set.

Gate order per step: purge → console/network diff → interaction smoke tests → accessibility → CLS → screenshot diff → Elementor editor → tracking → Lighthouse (last, alone).

## 1. Before any change — what to record

**Page set** (`pages.json`, fixed for the engagement): home, one room/product/bookable page, the cart or booking step, one blog/forum page, contact, one home per language, plus any page whose template the step touches. A set that omits the page where the change bites proves nothing.

**Settings before-values**, as exact serialized values, never from memory:

- Every option the step touches: `wp option get <name> --format=json`; all Elementor options with `wp option list --search='elementor_*' --format=json` ([WP-CLI option](https://developer.wordpress.org/cli/commands/option/)). Experiments are raw strings (`active`, `inactive`, absent = default) — record the raw value, not the CLI's binary status.
- LiteSpeed: `wp litespeed-option export --filename=<dir>/lscwp-step<n>.txt` (restored with `import`) ([LiteSpeed CLI](https://docs.litespeedtech.com/lscache/lscwp/cli/)). `wp litespeed-option get guest` must be `false` before any measurement; runs whose Guest Mode values differ are never compared ([LiteSpeed General](https://docs.litespeedtech.com/lscache/lscwp/general/)).
- W3TC: `wp w3-total-cache export <file>` / `import` ([Cli.php](https://github.com/BoldGrid/w3-total-cache/blob/master/Cli.php)); the import does not touch `.htaccess`, so staging exports are not portable to production.
- Perfmatters: `wp option get perfmatters_options --format=json` is the authoritative diff; `wp perfmatters enable|disable <key>` toggles one feature per step (`get-options` lists the keys). Script Manager rules are per post ID, excluded from the export, and need the UI with Testing Mode ([import/export](https://perfmatters.io/docs/import-export/)).
- FlyingPress (the CretaForce fork keeps everything in option `FLYING_PRESS_CONFIG`; confirm with `wp option list --search='flying*'`): `wp option get <row> --format=json`. No export/import exists; the CLI only purges and preloads ([FlyingPress WP-CLI](https://docs.flyingpress.com/en/articles/11406137-flyingpress-wp-cli-commands)).
- `wp option get elementor_element_cache_ttl` (`disable` = off, otherwise hours); `wp elementor flush-css` also deletes the `_elementor_element_cache` meta for all posts ([manager.php](https://github.com/elementor/elementor/blob/main/core/files/manager.php)).
- `wp db export` before the only irreversible action, `revisions_prune` ([db export](https://developer.wordpress.org/cli/commands/db/export/)).

`scripts/debloat.php` already does this for its own actions (option `elementor_lean_undo`, page data in `_elementor_data_lean_bak`). Write one ledger entry per step `{step, timestamp, commands_run, before:{option:value}, export_files:[…]}` to the scratch dir and to Novamira memory key `elementor-lean-log`.

**Cache ownership.** Exactly one plugin per function (page cache, minify, defer, delay, lazy-load, fonts); three audited sites run two optimisers with overlapping features ([compatibility](https://docs.flyingpress.com/en/articles/11423987-compatibility-with-other-optimization-plugins)). Record the HIT footprint required before every measurement: `x-litespeed-cache: hit`, `X-Flying-Press-Cache: HIT` ([FlyingPress check](https://docs.flyingpress.com/en/articles/11403256-how-to-check-if-flyingpress-is-working-on-your-site)), W3TC's footer comment.

**Lighthouse baseline.** `scripts/lighthouse.sh` per page, 3 serial runs minimum (rules.md), 5 when a keep/undo decision will hinge on fewer than 3 points; report median, range and `benchmarkIndex` per run. Facts that are not optional:

- Discard runs with a non-empty `runWarnings` (a page that never goes quiet yields TBT like 142,018 ms, seen on limnoupolis.gr), TBT ≥ 10,000 ms, or `benchmarkIndex` below about 1,000. Serial, never concurrent, Playwright finished first ([scoring and variability](https://developer.chrome.com/docs/lighthouse/performance/performance-scoring)).
- Mobile only, on the final URL: the CLI does not follow a 3xx (pallada.gr `/` → `/en/` adds ~1.1 s and a warning; PSI resolves it first) ([PSI release notes](https://developers.google.com/speed/docs/insights/release_notes)).
- One warming GET and a HIT header before the first run.
- CLI simulates with `cpuSlowdownMultiplier` 4; PSI's Lightrider config uses 1.2 ([lr-mobile-config.js](https://raw.githubusercontent.com/GoogleChrome/lighthouse/main/core/config/lr-mobile-config.js)). LCP/FCP/CLS verdicts are portable; TBT verdicts need PSI or a multiplier calibrated from the box's benchmarkIndex ([calculator](https://lighthouse-cpu-throttling-calculator.vercel.app/)).
- One Lighthouse version for the engagement. `lighthouse.sh` is pinned to 12 and its diagnostics jq reads legacy ids that 13 removed; the insight ids in section 8 exist in 12.8.2 and 13.5.0 ([Lighthouse 13](https://developer.chrome.com/blog/lighthouse-13-0)).
- Lighthouse never answers the banner or interacts; on Delay-JS sites the delayed payload is judged by the Playwright gates after a tap ([user flows](https://github.com/GoogleChrome/lighthouse/blob/main/docs/user-flows.md)).

**Console/network baseline** (section 3) per page × viewport, consent accepted, read after a scroll and a tap.

**Interactive inventory** per page, with the matched selector in the ledger: menu toggle (`.elementor-menu-toggle` on 4.2.x, `.e-n-menu-toggle` for the 4.3 Mega Menu), forms (`.elementor-widget-form`), popups, sliders (`.swiper-slide`), booking widgets (`.yith-wcbk*`, `.wc-bookings-booking-form`), language switcher (`.wpml-ls`, Polylang's `pll_the_languages()` output), filters (`jet-smart-filters`), add-to-cart buttons, Theme Builder locations (`data-elementor-type` / `data-elementor-id` pairs), tracking hosts after consent.

**Field data** at the start: PSI `loadingExperience` for the exact URL or the CrUX API ([CrUX API](https://developer.chrome.com/docs/crux/api)); low-traffic sites return NOT_FOUND and have no field verdict.

## 2. Visual regression

Playwright Test `toHaveScreenshot()`: defaults already disable CSS/Web animations and hide the caret; `threshold` (per-pixel YIQ distance, default 0.2) and `maxDiffPixelRatio` are the knobs ([PageAssertions](https://playwright.dev/docs/api/class-pageassertions)). Ratios above ~0.02 hide a missing icon font or a broken menu; 0 fails on anti-aliasing. Use 0.005 above the fold (hero, fonts, icons) and 0.01 full-page. Baseline and comparison must use the same Chromium and OS (same `CHROME_PATH` or one pinned Playwright image).

Stabilisers Elementor needs: `prefers-reduced-motion` only cancels `.animated` keyframes, smooth scroll and Swiper autoplay — it never removes `elementor-invisible` (68 such elements on cretaforums.gr), so the stylesheet override is required ([entrance animations](https://elementor.com/help/entrance-animations/)). Fix the clock for countdown/booking widgets, mask carousels, video and map iframes, wait for `document.fonts.ready` and `networkidle`, scroll once for lazy images and return to top, block third-party hosts in this project only. On LiteSpeed sites shoot the second load (Guest Mode swaps the DOM by AJAX). Consent: a setup project accepts once and saves `storageState`; re-run it whenever the CMP plugin or its settings change (Complianz bumps its banner version and the saved state is then ignored).

```js
// playwright.config.js   (npm i -D @playwright/test@1.64; CHROME_PATH optional)
const { defineConfig, devices } = require('@playwright/test');
module.exports = defineConfig({
  timeout: 120000, workers: 1,
  snapshotPathTemplate: '{testDir}/__shots__/{arg}-{projectName}{ext}',
  expect: { toHaveScreenshot: { threshold: 0.2, maxDiffPixelRatio: 0.01, animations: 'disabled', caret: 'hide', scale: 'css' } },
  use: { baseURL: process.env.SITE, storageState: 'playwright/.auth/consent.json',
         launchOptions: { executablePath: process.env.CHROME_PATH || undefined } },
  projects: [
    { name: 'setup', testMatch: /setup\.spec\.js/, use: { storageState: { cookies: [], origins: [] } } },
    // browserName forces Chromium: the iPhone descriptor defaults to WebKit
    { name: 'mobile',  use: { ...devices['iPhone 13'], browserName: 'chromium' }, dependencies: ['setup'] },
    { name: 'desktop', use: { ...devices['Desktop Chrome'], viewport: { width: 1366, height: 768 } }, dependencies: ['setup'] },
  ],
});
```

```js
// setup.spec.js — accept the banner once, save the state
const { test, expect } = require('@playwright/test');
test('consent', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('button', { name: /accept|αποδοχ/i })
    .or(page.locator('.cmplz-accept, #cookiescript_accept, .cc-allow')).first().click({ timeout: 10000 });
  await expect(page.locator('.cmplz-cookiebanner, #cookiescript_injected, [class*=fazcookie]')).toBeHidden();
  await page.context().storageState({ path: 'playwright/.auth/consent.json' });
});
```

```js
// shots.spec.js
const { test, expect } = require('@playwright/test');
const pages = require('./pages.json'); // [{slug:'home',url:'/'}, ...]
const FREEZE = `.elementor-invisible{visibility:visible!important;opacity:1!important;animation:none!important}
.animated{animation:none!important} .swiper-wrapper{transform:none!important}
video,iframe{visibility:hidden!important} *{transition:none!important;scroll-behavior:auto!important}`;
const MASK = '.swiper-slide, .elementor-widget-video, .elementor-widget-google_maps, iframe';
test.use({ reducedMotion: 'reduce' });
for (const p of pages) test(`shot ${p.slug}`, async ({ page, context }) => {
  await context.route(/googletagmanager|google-analytics|facebook\.net|doubleclick|hotjar/, r => r.abort());
  await page.clock.setFixedTime(new Date('2026-01-01T12:00:00Z'));
  await page.goto(p.url, { waitUntil: 'networkidle' });
  if (process.env.LITESPEED) await page.goto(p.url, { waitUntil: 'networkidle' }); // Guest Mode: judge the 2nd load
  await page.addStyleTag({ content: FREEZE });
  await page.evaluate(async () => { await document.fonts.ready;
    for (let y = 0; y < document.body.scrollHeight; y += innerHeight) { scrollTo(0, y); await new Promise(r => setTimeout(r, 300)); }
    scrollTo(0, 0); });
  await expect(page).toHaveScreenshot(`${p.slug}-fold.png`, { maxDiffPixelRatio: 0.005, mask: [page.locator(MASK)] });
  await expect(page).toHaveScreenshot(`${p.slug}-full.png`, { fullPage: true, mask: [page.locator(MASK)] });
});
```

Ad-hoc comparison of two saved PNGs (step n vs n−1) with pixelmatch 8 / pngjs; odiff 4.5 (`odiff-bin`) is the faster alternative ([odiff](https://github.com/dmtrKovalenko/odiff)):

```js
// diff.mjs — node diff.mjs before.png after.png diff.png ; exit 1 when ratio > 0.01
import fs from 'node:fs'; import { PNG } from 'pngjs'; import pixelmatch from 'pixelmatch';
const [a, b, out] = process.argv.slice(2), A = PNG.sync.read(fs.readFileSync(a)), B = PNG.sync.read(fs.readFileSync(b));
if (A.width !== B.width || A.height !== B.height) { console.log('size differs'); process.exit(2); }
const D = new PNG({ width: A.width, height: A.height });
const n = pixelmatch(A.data, B.data, D.data, A.width, A.height, { threshold: 0.2 });
fs.writeFileSync(out, PNG.sync.write(D)); const ratio = n / (A.width * A.height);
console.log(JSON.stringify({ diffPixels: n, ratio })); process.exit(ratio > 0.01 ? 1 : 0);
```

Calibrate before the first lever: the matrix passes twice in a row with zero diffs (add masks or FREEZE rules until it does), then hide the menu button with one injected CSS line and confirm the diff catches it. Baselines are never updated automatically; after a human "approve", `npx playwright test --update-snapshots` makes the kept step the next baseline. Reduced motion also hides a real animation regression (an entrance animation that never finishes) — the console gate plus one unmasked shot per site after a scroll covers that.

## 3. Console and network errors

Delay/defer breakage shows first as `jQuery is not defined`, not as pixels. Collect per page × viewport, consent accepted: `pageerror`, console `error` messages, `requestfailed` with `errorText`, and every response ≥ 400 — 4xx/5xx complete as `requestfinished`, so they need the `response` hook ([Page](https://playwright.dev/docs/api/class-page)); since 1.56 `page.pageErrors()` / `page.consoleMessages()` hold the last 200. Delay-JS only runs after a gesture: wheel, click, wait 3 s, then read. Run the collector again during the smoke flows and on the second, cached load of each page (Element Cache nonce staleness only shows on the cached copy).

```js
async function collect(page, run) {
  const out = { pageErrors: [], consoleErrors: [], failed: [], http: [] };
  page.on('pageerror', e => out.pageErrors.push(e.message));
  page.on('console', m => m.type() === 'error' && out.consoleErrors.push(m.text()));
  page.on('requestfailed', r => out.failed.push(`${r.url()} ${r.failure()?.errorText}`));
  page.on('response', r => r.status() >= 400 && out.http.push(`${r.status()} ${r.url()}`));
  await run();
  await page.mouse.wheel(0, 800); await page.mouse.click(10, 10); await page.waitForTimeout(3000); // wake delay-JS
  return out;
}
const norm = s => s.replace(/[?&](ver|v|t|_)=[^&\s]+/g, '');
const regressions = after.map(norm).filter(x => !before.map(norm).includes(x));
```

**Regression** = any entry in `after \ before` (URLs normalised for `?ver=` and cache busters), plus — always — any failed request or 4xx/5xx for a first-party URL (same origin, uploads, fonts). Signatures: `jQuery is not defined` / `$ is not a function` (defer or delay ahead of an inline HTML-widget script), `elementorFrontend is not defined`, `wp.hooks`/`wp.i18n` errors (delay-all without `hooks.min.js`/`i18n.min.js` exclusions), 404 on `/cache/min/` or `/wp-content/litespeed/css|js/` combined files, 403 on `?wc-ajax=` / "security check failed" (nonce older than the session), `<name> is not defined` after a Script Manager unload. Cross-origin iframes (gateways, maps) are invisible here; the smoke tests cover them. Calibrate on staging: disable `jquery-core` on one page in Script Manager and confirm the gate turns red ([Perfmatters console errors](https://perfmatters.io/docs/javascript-console-errors/)).

## 4. Interaction smoke tests

Role-based locators first, the audited sites' class names as fallbacks; assert DOM or network outcomes. Every flow passes on the untouched site first and its matched selector goes into the ledger. First attempts run from a cold profile **without any prior mouse movement** — that exposes Delay-JS (calendar, filter or add-to-cart that only wake on interaction). Never create real orders or bookings on production: test product, cart emptied through the Store API, payment steps on staging only ([Store API](https://developer.woocommerce.com/2023/09/20/tutorial-placing-an-order-using-the-store-api/)).

```js
// menu + popup + booking (fragments; cfg comes from smoke.json)
await page.getByRole('button', { name: /menu/i }).or(page.locator('.elementor-menu-toggle, .e-n-menu-toggle')).first().click();
await expect(page.getByRole('navigation').getByRole('link').first()).toBeVisible();

const shown = page.evaluate(() => new Promise(res => jQuery(document).one('elementor/popup/show', (e, id) => res(id))));
await page.locator(cfg.popupTrigger).first().click();
expect(await shown).toBeTruthy(); await expect(page.locator('.elementor-popup-modal').first()).toBeVisible();

await page.goto(cfg.bookingUrl, { waitUntil: 'networkidle' });      // no wheel/click before this click
await page.locator(cfg.dateField).first().click();
await expect(page.locator('.ui-datepicker, .wc-bookings-date-picker, .yith-wcbk-booking-form').first()).toBeVisible();
```

| Site type | Flows | Pass means |
|---|---|---|
| Brochure (hotel/villa without booking widget) | Mobile menu, sub-menu / mega-menu panel, off-canvas; slider "next"; scroll 600 px; an anchor link; the test form; a popup; a map/YouTube facade | Nav links visible; `.swiper-slide-active` changes; `.elementor-sticky--active` appears and is styled (RUCSS signature); anchor lands on its section (lazy-render signature); the form's Webhook action reaches the test endpoint (pipeline, not SMTP); `elementor/popup/show` fires and the modal is styled; facade click loads the iframe |
| Hotel with booking widget (YITH, WC Bookings, search form) | Calendar opens and a date is selectable with no prior interaction; search form submits; calendar still loads on a page served from cache ≥ 13 h after purge (or TTL 1 h); booking → cart on staging only | Date picker visible; `?wc-ajax=…` 200 (WC Bookings `wc_bookings_find_booked_day_blocks`), no 403; cart/checkout/account carry `no-cache` ([LiteSpeed cache](https://docs.litespeedtech.com/lscache/lscwp/cache/)) |
| WooCommerce shop | Archive add-to-cart; variation select; gallery lightbox; cart → checkout; country select; JetSmartFilters first click; a brochure page revisited with items in cart | `GET /wp-json/wc/store/v1/cart` item count rises; checkout has `payment_method_*` radios; filter results change, URL updates, reload keeps state ([JetSmartFilters](https://crocoblock.com/knowledge-base/jetsmartfilters/filter-apply-types-ajax-mixed-page-reload/)); Menu Cart count right on the next load ([cart fragments](https://developer.woocommerce.com/2023/06/16/best-practices-for-the-use-of-the-cart-fragments-api/)); then `DELETE` the cart items |
| Multilingual (Polylang/WPML) | Switch language from each page; `/` with `Accept-Language: el` and `en`; second load per language; add to cart in the second language (WCML) | `html[lang]` changes, 200, HIT on the second load per language; `/` is not an uncacheable 302 unless intended ([WP Rocket multilingual](https://docs.wp-rocket.me/article/76-translation-multilingual-plugins)); with Guest Mode, `html[lang]` asserted after the AJAX swap; fragments return in the chosen language |

Markup differs between Elementor 4.2.3 (`elementor-nav-menu--dropdown`) and 4.3.x (nested `e-n-menu`), so locators are per site. Confidence medium on the booking fallback classes — confirm during the baseline run.

**Accessibility gate.** Unused-CSS removal drops `:focus-visible`, skip-link and `.elementor-screen-only` rules, because they match only after a key press. Delay-JS can leave the menu toggle dead until a mouse or touch event (verify each optimiser's trigger events). Before and after each step, per page (tested on a fixture with Lighthouse 12: an added image without alt shows as `image-alt …`, an improvement prints `[]`):

```sh
npx lighthouse "$url" --only-categories=accessibility --form-factor=mobile \
  --chrome-flags="--headless=new --no-sandbox" --output=json --output-path="$out/$name.a11y.json" --quiet
jq -c '[.audits|to_entries[]|select(.value.score==0)|.key as $id|.value.details.items[]?|"\($id) \(.node.selector)"]|sort' \
  "$out/$name.a11y.json" > "$out/$name.a11y.$step.set"
jq -n --slurpfile b "$out/$name.a11y.$prev.set" --slurpfile a "$out/$name.a11y.$step.set" '$a[0]-$b[0]'   # must print []
```

- A changed selector shows as one new and one fixed entry. Read both lists before calling it a regression. A second failure on an identical selector does not show; the count in the `.set` file does.
- Then run the ux-reviewer's keyboard walk (its step 3a). The count of ring-less stops must not rise, and the skip link stays at stop 0.
- From a cold load with no mouse movement, Tab to the menu toggle and open it with Enter.

## 5. Tracking still fires

Two states, never mixed, each from a fresh context ([GA4 troubleshooting](https://developers.google.com/analytics/devguides/collection/ga4/troubleshoot)):

1. **No consent, no interaction** (what PSI sees). Record the site's consent mode and assert the same after the step: in "basic" mode only `gtm.js` and the CMP load — no `/g/collect`, `facebook.com/tr` or `googleads.g.doubleclick.net`; in "advanced" mode the first `/g/collect` carries `gcs=G100` and a `gcd=` parameter. `dataLayer` holds exactly one consent `default`, pushed before `gtm.js` ([Consent Mode](https://developers.google.com/tag-platform/security/guides/consent), [Complianz](https://complianz.io/consent-mode/)). The banner is visible at first paint without a gesture: a CMP in the delay list is a breakage ([Complianz on Delay-JS](https://complianz.io/javascript-delay-in-wp-rocket-and-other-caching-and-optimization-plugins/)).
2. **Consent accepted, after interaction** (wheel, click, wait ≥ 5 s and longer than the Delay-JS timeout, or "missing" is a false alarm): `gtm.js?id=GTM-` requested exactly once; `/g/collect` 200 or 204 with `gcs=G111`; `facebook.com/tr` ≥ 1 where a pixel exists (one `fbevents.js`, no `signals/config`/`openbridge3` when autoConfig is off — [Meta Pixel](https://developers.facebook.com/docs/meta-pixel/advanced)); `dataLayer` has the `gtm.js` event and one consent `update`. Compare the set and count of tag hosts with the baseline; a `gtag/js?id=` or `recaptcha__en.js` loaded twice, or any `UA-` request, is a finding (rules.md 6).

```js
const reqs = [], resps = [];
page.on('request', r => reqs.push(r.url())); page.on('response', r => resps.push({ u: r.url(), s: r.status() }));
await page.goto('/', { waitUntil: 'networkidle' });
await page.mouse.wheel(0, 600); await page.mouse.click(10, 10); await page.waitForTimeout(Math.max(5000, DELAY_TIMEOUT_MS));
const gtm = reqs.filter(u => /googletagmanager\.com\/gtm\.js\?id=GTM-/.test(u));
const collect = resps.filter(r => /\/g\/collect/.test(r.u) && [200, 204].includes(r.s)).map(r => new URL(r.u).searchParams.get('gcs'));
const fb = reqs.filter(u => /facebook\.com\/tr/.test(u)).length;
const dl = await page.evaluate(() => ({ booted: !!window.dataLayer?.some(e => e.event === 'gtm.js'),
  consent: (window.dataLayer || []).filter(e => e && e[0] === 'consent').map(e => e[1]) }));  // expect ['default','update']
```

Caveats: ad-blocking DNS on the runner silently blocks `facebook.net`; set `serviceWorkers: 'block'` if network events go missing; no route blocking in this project. Lab-side, after a delay or consent change `third-parties-insight` and `bootup-time` must no longer list googletagmanager.com / connect.facebook.net before interaction; price a vendor with `lighthouse --blocked-url-patterns` instead of guessing ([third-party facades](https://developer.chrome.com/docs/lighthouse/performance/third-party-facades)).

## 6. Layout stability and LCP element

**CLS gate** (catches the LiteSpeed lazy-load 0 → 0.107 regression a single Lighthouse run misses): a buffered `layout-shift` observer installed with `context.addInitScript` *before* navigation; wait 3 s after load, scroll in viewport-height steps with 500 ms pauses, compute the largest session window (gaps < 1 s, window ≤ 5 s) in Node; `hadRecentInput` excludes shifts within 500 ms of discrete input, not scroll ([web-vitals](https://github.com/GoogleChrome/web-vitals)). Fail at CLS > 0.1 or a rise > 0.02 over the step baseline; keep `sources` so the culprit node is in the report. FREEZE disables smooth scroll; an animating sticky header counts as shifts.

```js
await context.addInitScript(() => { window.__ls = [];
  new PerformanceObserver(l => l.getEntries().forEach(e => { if (!e.hadRecentInput)
    window.__ls.push({ v: e.value, t: e.startTime, src: e.sources?.map(s => s.node?.outerHTML?.slice(0, 120)) }); }))
  .observe({ type: 'layout-shift', buffered: true }); });
// ... goto, wait 3 s, scroll pass ...
const cls = (await page.evaluate(() => window.__ls)).sort((a, b) => a.t - b.t).reduce((acc, e) => {
  if (acc.cur && e.t - acc.prev < 1000 && e.t - acc.start < 5000) acc.cur += e.v; else { acc.cur = e.v; acc.start = e.t; }
  acc.prev = e.t; acc.max = Math.max(acc.max, acc.cur); return acc; }, { max: 0, cur: 0, start: 0, prev: 0 }).max;
```

**LCP element** (rules.md 3): text or an `<img>` with `fetchpriority="high"`, `srcset`/`sizes`, width/height, no `loading="lazy"`. Blockers: `<video>`, a container with a background image or video, `.swiper-slide*` / `.elementor-background-slideshow*`, a selector matching `cookie|cmplz|Cybot|iubenda|faz` (the banner was the LCP on pallada, cretaforums and onar — hero levers cannot move LCP there), an animated-headline span, `h1.elementor-invisible` (voreniahorizon, LCP at 8.95 s). Read the node from `lcp-breakdown-insight` — the legacy `largest-contentful-paint-element` table is unreliable for text LCP on 12.8.2 and gone on 13 — and check `lcp-discovery-insight` (`priorityHinted`, not lazy, discoverable) ([lcp-discovery-insight.js](https://raw.githubusercontent.com/GoogleChrome/lighthouse/main/core/audits/insights/lcp-discovery-insight.js)).

**Render-delay share**: `elementRenderDelay` under 40 % of LCP (rules.md 13) and under 500 ms; more than that after a lever points at first-screen animations, blocking fonts or CSS, not the image. Phases sum to the LCP value within 1 ms.

After any hero or image lever also: no asset of an `elementor-hidden-mobile` tree in the mobile `network-requests` (Hide on Mobile is `display:none` only; eager images and autoplay video still download — [show/hide per device](https://elementor.com/help/show-or-hide-columns-per-device/)); the hero `<img>` carries `fetchpriority=high` from exactly one source; with Lazy Load Background Images on, a background poster sits in the first two top-level containers or carries `e-no-lazyload`.

## 7. Elementor editor and admin

**Editor loads and saves.** A dedicated editor-role test user limited to one hidden test page (never a production admin; password outside the repo); a setup project logs in at `/wp-login.php` and saves `playwright/.auth/editor.json`. Test: `goto('/wp-admin/post.php?post=' + ID + '&action=elementor')`; `body` has class `elementor-editor-active`; `#elementor-panel-state-loading` hidden within 90 s (slow hosts exceed 30 s); `#elementor-panel-content-wrapper` visible; `#elementor-preview-iframe` loaded; `page.pageErrors()` empty ([can't edit](https://elementor.com/help/cant-edit-with-elementor/)). Then change a heading on the test page, click the update button (`getByRole('button', { name: /update|publish|δημοσίευση|ενημέρωση/i })` — confirm the locator in the baseline; confidence medium) and verify `wp post get <ID> --field=post_modified` advanced. Known causes of a stuck spinner: Delay/Defer JS applied to logged-in users (Perfmatters), LiteSpeed "Optimize for Guests Only" off with JS defer, CSS/JS Combine, W3TC Minify "Auto" + Combine, Cloudflare Rocket Loader. Safe Mode (Elementor > Editor > Tools in 4.x) isolates only the canvas, not the panel, so "still broken in Safe Mode" is inconclusive for panel failures — attribute with the off-switches in section 10 ([Safe Mode](https://elementor.com/help/what-is-safe-mode/)).

**Theme Builder templates still apply.** On every page in the set the `data-elementor-type` / `data-elementor-id` pairs (header, footer, single, archive) equal the baseline and header and footer are visible; a missing pair means a condition or template stopped applying (page cache after a template edit, Element Cache, stale object cache). A changed Global Color shows on the front end after purge, or the object cache is stale.

**Popups still trigger.** Per popup: its trigger → `elementor/popup/show` fires and `.elementor-popup-modal` is visible *and styled* (RUCSS/UCSS strip CSS absent at first render; compare the modal's computed background and position with the baseline). Popups built with V4 atomic elements are the higher-risk class under any RUCSS older than Perfmatters 2.6.9 or the FlyingPress fork's local engine ([Perfmatters changelog](https://perfmatters.io/docs/changelog/)).

**Element Cache.** `wp post meta get <ID> _elementor_element_cache` is empty right after `wp elementor flush-css` and non-empty after one GET; form/filter flows pass on both states ([document.php](https://github.com/elementor/elementor/blob/main/core/base/document.php)). **Admin:** `/wp-admin/` and Elementor > Settings return 200 with no `Warning:`/`Fatal error` in the body; `wp elementor` responds.

## 8. Lighthouse comparison

Protocol per URL: warming GET → HIT header → N serial runs with `lighthouse.sh` (N = 3; 5 when the delta is borderline), nothing else on the box, Playwright finished (lock file), `benchmarkIndex`, `os.cpus().length` and load average logged. Discard runs per section 1. Representative run as Lighthouse picks it — smallest `(medianFCP−FCP)² + (medianTTI−TTI)²` ([median-run.js](https://raw.githubusercontent.com/GoogleChrome/lighthouse/main/core/lib/median-run.js)) — or `lhci collect -n 5` with `aggregationMethod: 'median-run'` (defaults: 3 runs, `optimistic`) ([LHCI configuration](https://github.com/GoogleChrome/lighthouse-ci/blob/main/docs/configuration.md)). Store `{score, lcp, cls, tbt, fcp, si, benchmarkIndex}` for every run.

Noise floor first: two blocks on the unchanged URL agree within 2 points and within the metric thresholds below; a spread above 6 points inside a block means the host or page is too noisy — repeat later or use PSI. Before/after `benchmarkIndex` within 20 %.

- **Regressed**: after-median score ≤ before-median − 3 **and** the ranges do not overlap, **or** a metric median moves past its threshold the wrong way: LCP +200 ms, TBT +100 ms (indicative on a shared host), CLS +0.02.
- **Improved**: the mirror image (≥ +3 with non-overlapping ranges, or the targeted metric past its threshold the right way).
- **Unchanged**: everything else; 1–2 points is noise. Keep an unchanged step only if the targeted diagnostic moved (render-blocking items 3 → 0) and every other gate is green.

Weights for reading a delta: TBT 30, LCP 25, CLS 25, FCP 10, SI 10; control points (p10/median) FCP 1800/3000 ms, SI 3387/5800, LCP 2500/4000, TBT 200/600, CLS 0.10/0.25. All five "good" thresholds give exactly 90; 95+ needs TBT ≈ 0 and CLS = 0. The targeted metric's points-lost must drop while the other four stay within ±1.

Audits to re-check after every lever (ids present in 12.8.2 and 13.5.0; `render-blocking-resources`, `third-party-summary`, `largest-contentful-paint-element`, `dom-size` are gone on 13):

```sh
jq -c '{lh:.lighthouseVersion, score:(.categories.performance.score*100|round), bench:.environment.benchmarkIndex, warn:.runWarnings,
 lcp:.audits["largest-contentful-paint"].numericValue, tbt:.audits["total-blocking-time"].numericValue, cls:.audits["cumulative-layout-shift"].numericValue,
 lcp_node:([.audits["lcp-breakdown-insight"].details.items[]?|select(.type=="node")|{selector,snippet}]|.[0]),
 lcp_phases:([.audits["lcp-breakdown-insight"].details.items[]?|select(.type=="table")|.items[]|{(.subpart):(.duration|round)}]|add),
 render_blocking:[.audits["render-blocking-insight"].details.items[]?|{url,wastedMs}],
 third_party_ms:([.audits["third-parties-insight"].details.items[]?.mainThreadTime]|add//0|round),
 requests:(.audits["network-requests"].details.items|length),
 fonts:([.audits["network-requests"].details.items[]|select(.resourceType=="Font")]|length),
 cls_culprits:[.audits["cls-culprits-insight"].details.items[0].items[]?|select(.node.type=="node")|.node.selector]}' run.json
```

- `render-blocking-insight`: 0 items; any item over 150 ms is a finding (rules.md 9).
- TBT attribution: `third-parties-insight` has no blocking time on 13, so join `bootup-time` items with `network-requests[].entity` (the `entity` field, not the host) to tell GTM/Facebook from first-party Elementor/add-on JS.
- Request count and Font count; `unused-javascript` / `unused-css-rules` savings; LCP node and phases, `lcp-discovery-insight`, `cls-culprits-insight`, `font-display-insight`, `dom-size-insight`.

Optional diagnostics, tracked separately and never reported as "the score": a consented run with `--extra-headers '{"Cookie":"…"}'` (sent to every host), a `--blocked-url-patterns` run per vendor, a Puppeteer user-flow timespan for interaction cost.

## 9. Per-lever: what to look at

| Lever applied | Click / inspect | Known breakage signatures |
|---|---|---|
| Page cache on / TTL | HIT on second GET of home, shop, product; `no-cache` on cart, checkout, account, `?add-to-cart=`, `wc-ajax`, Store API; bookable page after the TTL; `/` with two `Accept-Language` values | Stale availability, 403 / "security check failed" when TTL > WooCommerce session (2 days); cached add-to-cart or wc-ajax; translated slug (`/kalathi`) not excluded; wrong language from cache |
| Minify / Combine CSS-JS | Console gate; editor loads; `frontend.min.js`, `webpack.runtime.min.js`, `post-*.css` still separate files (Combine is never auto-enabled) | W3TC "Auto" + "Combine & Minify" breaks Elementor; combined files 404 after host cleanup; script order lost ([pageopt](https://docs.litespeedtech.com/lscache/lscwp/pageopt/)) |
| Defer JS | Console on home, product, booking page; scroll 600 px → `.elementor-sticky--active` fixed and styled; maps and calendar initialise | `jQuery is not defined` from HTML-widget inline scripts (up to 251 per site); sticky header never fixes; inline init runs before its library |
| Delay JS — specified scripts | Banner at first paint with no gesture; tracking states 1 and 2; GTM appears after the timeout without touching | CMP or consent-default delayed (banner only after touch, tags without consent mode); no timeout = bounce visitors lost ([Delay all JS](https://docs.flyingpress.com/en/articles/11406701-delay-all-javascript)) |
| Delay JS — all | Previous row plus: hero, menu, slider, popup render before any interaction at 390 px; first filter/add-to-cart/calendar click works cold; `type="module"` loaders untouched; URL Inspection rendered HTML | `elementor-invisible` never removed; menu needs a double tap; calendar/filters dead until mouse move; `hooks.min.js`/`i18n.min.js` errors; delayed facade loader = blank desktop hero; Googlebot never interacts, so delayed content leaves the index ([Perfmatters Delay JS](https://perfmatters.io/docs/delay-javascript/)) |
| Remove Unused CSS / UCSS+CCSS | Side by side with the off-switch URL: header, opened popup, open mobile menu, sticky state, tabs/accordion, Woo variation form, V4 elements; fold shot at 0.005 | Unstyled elements after interaction; popup CSS missing; banner unstyled or invisible (consent impossible); LiteSpeed's bundled safelist has zero Elementor entries; V4 per-document classes stripped |
| Lazy-load images (any owner) | LCP node without `loading="lazy"`, with `fetchpriority="high"`; CLS gate; Swiper/AJAX-grid images load; one lazy attribute source (`data-lazyloaded` xor `loading="lazy"`) | CLS 0 → 0.107 (LiteSpeed `media-lazy` without VPI / Add Missing Sizes); hero lazy-loaded (+4.3 s on limnoupolis); carousel images never load; two lazy-loaders stack ([FlyingPress lazy load](https://docs.flyingpress.com/en/articles/11406804-lazy-load-images-videos-and-iframes)) |
| Lazy render (`content-visibility`) | Every anchor/menu link targeting a section; form, map, calendar, filter results below the fold initialise; no lazy-render wrapper on them | Wrong anchor offset; widgets initialise at zero height; below-fold HTML hidden from crawlers; the FlyingPress fork defaults `js_lazy_render` to true |
| Local Google Fonts / subsetting | Zero `fonts.googleapis.com`/`gstatic` requests; `@font-face` with `unicode-range` covering U+0370–03FF; a Greek heading on a device; one `@font-face` set; ≤ 4 font files (rules.md 11) | Greek glyphs silently fall back (Perfmatters "Limit Subsets" defaults to Latin); two local copies when Elementor's "Load Google Fonts Locally" and the optimiser both run ([Elementor local fonts](https://elementor.com/help/load-google-fonts-locally/)) |
| Inline Font Icons / `fa4_shim_off` / dashicons off | No icon-font files in `network-requests` (rules.md 12); every icon renders in fold and full shots | Blank squares where `fa fa-*` icons remain (the script skips the shim if any document uses them); a plugin front end needing dashicons |
| Element Cache on (`element_cache_on`) | Prices, availability, cart count, filter results and form submit on the second (cached) load | Stale prices/availability on dynamic widgets; expired nonce breaks forms/filters until the TTL re-warms |
| Optimized Markup (`optimized_markup_on`) | Pages with Custom CSS; all shots | Custom CSS targeting removed wrapper divs stops applying |
| Optimized Image Loading / Lazy Load Background Images | Hero `<img>` has `fetchpriority="high"` from exactly one source; background poster painted at FCP; `lcp-discovery-insight` passes | Hidden desktop hero steals the high-priority slot (first eligible `<img>` in source order wins); 3rd+ container background suppressed on an 823 px viewport; the global toggle off removes `fetchpriority` everywhere (the Image widget has no Lazy Load control in 4.3.5) |
| Per-page script unloading (Script Manager, Bloat settings, Woo dequeue snippet) | Testing Mode: admin vs incognito; every template: popups with forms, filters, add-to-cart on landing pages, calendar, maps; console gate | `X is not defined`; slider/menu/form dead after `jquery`/`elementor-frontend`/`swiper` unloaded; booking widget unloaded where a popup embeds it; Woo CSS missing under a `[products]` shortcode on a brochure page |
| Cart fragments / Order Attribution / Geolocation off | Fresh window on a brochure page: no `?wc-ajax=get_refreshed_fragments`, admin-ajax `elementor_menu_cart_fragments`, `sourcebuster` or `wc-geolocation-js`; add to cart then revisit; checkout country prefill | Stale Menu Cart counter for returning shoppers; attribution report "Unknown" (marketer's OK); geolocated taxes/prices lost |
| Mobile hero swap (facade, `<source media>`, `<picture>`, Background Video "Play on Mobile" off) | Mobile `network-requests`: no `.mp4`/`.glb`/three.js/lottie `.json`; LCP node = poster `img` with `fetchpriority="high"`; desktop still loads the clip (206); loader keeps `type="module"` | Delay-JS rewrites the loader → blank desktop hero; UA-based variants (`wp_is_mobile()`, Dynamic Visibility) cached for everyone without a mobile vary; poster rewritten to `data-src` by an optimiser |
| Consent gating / one GTM / pixel via GTM / autoConfig off | Section 5, both states; thank-you conversion on a staging order; `gtag/js?id=` inline = 0, `gtm.js?id=` = 1 | Conversion lost where a hard-coded snippet was its only source; GA4 ecommerce lost when the Woo GA extension was the source; auto-detected pixel events lost; two CMPs → two `default` pushes |
| reCAPTCHA removed/delayed (Honeypot instead) | Submit each form type once; `recaptcha__xx.js` absent before focus and never twice | Submission fails when the delayed order is wrong; spam if removed without Honeypot ([reCAPTCHA loading](https://developers.google.com/recaptcha/docs/loading)) |
| Polylang/WPML redirect fix | `curl -sI /` → 200 or a cacheable 301, no `no-store`; `finalDisplayedUrl == requestedUrl`; `hreflang` present | Greek-browser visitors land on the default language (choose it first); home never cached with detection + cache on |

## 10. Rollback

**Undo = replay the recorded before-values**, purge, then re-run the gates against the step's own before baseline (not the original site baseline) until green; `wp option get <name>` must equal the recorded value.

- `scripts/debloat.php` with `$actions = ['undo']`: restores every option in `elementor_lean_undo` (deletes those recorded as `__unset__`), restores `_elementor_data` from `_elementor_data_lean_bak`, clears the undo option and the Elementor CSS cache.
- Other options: `wp option update <name> '<json>' --format=json`; experiments by raw value.
- LiteSpeed `wp litespeed-option import <file>`; W3TC `wp w3-total-cache import <file>`; Perfmatters `wp perfmatters disable <key>` (plus `clear-used-css` after RUCSS) or `import-settings <file>`; FlyingPress `wp option update` of the recorded JSON; Script Manager rules reverted in the UI; `wp db import` only for the irreversible step.

**Purge order, after apply and after undo**: Elementor first (`wp elementor flush-css`, which also empties the Element Cache meta) → the optimiser's generated assets (`wp perfmatters clear-used-css`, LiteSpeed purge all, `wp flying-press purge-everything`, W3TC flush — verify the exact subcommand on the installed version) → page cache / CDN → one warming GET → HIT header → gates. Measuring on a MISS compares an uncached "after" with a cached "before".

**Attribute before rolling back.** Re-run only the failed gate with the vendor's off-switch and report "fails normally / passes with X off": Perfmatters `?perfmattersoff` (the global buffer: delay, defer, minify, RUCSS, lazy, preload, fonts, Woo tweaks, CDN — not Script Manager), `?perfmattersjsoff`, `?perfmatterscssoff` ([test Perfmatters off](https://perfmatters.io/docs/test-perfmatters-off/)); the doc states no login requirement — confirm that `?perfmattersjsoff` removes `pmdelayedscript` for an anonymous request, else use the admin storageState. LiteSpeed `?LSCWP_CTRL=before_optm` works from any IP; `?LSCWP_CTRL=NOCACHE` only from an IP under Toolbox > Debug Settings > Admin IPs. FlyingPress has no documented per-URL switch (do not rely on `?no_optimize` until verified). Unknown query strings may be served from the page cache — purge or NOCACHE first. Perfmatters 2.6.8+ has a sitewide Safe Mode under Tools; never leave it on after a diagnosis.

**Roll back automatically**, then report, when: a new first-party console error or failed first-party request; a smoke flow fails; CLS > 0.1 or +0.02; a tracking assertion fails; the editor gate fails; Lighthouse "regressed"; the banner is not visible at first paint; `html[lang]` wrong after a switch.

**Stop and ask** when: only the visual gate is red (a diff says what moved, not whether the client accepts it — show `-diff.png`, `-actual.png`, `-expected.png` and the metric table, then "approve / undo"); the lever is an intended visual change (hero poster, fewer weights, animations removed, map facade); the score is unchanged but the targeted metric moved; Lighthouse spread exceeds 6 points; marketing data is touched (tags delayed, attribution off, snippet removed — the marketer's OK comes before, not after). No step is "kept" with a red visual gate and no recorded human decision.

**Staging → production**: the identical recorded key set (`diff` the sorted exports), gates again on production with Lighthouse last; staging on these sites has differed in plugin set and Elementor version, so a green staging run is necessary, not sufficient.

## 11. What cannot be automated

- Whether a flagged visual difference is acceptable (Greek fallback font, replaced hero poster, removed animation): a human decision, recorded in the ledger.
- Email delivery: the webhook endpoint proves the submission pipeline, not SMTP. One real submission per form type, confirmed in the mailbox.
- A real booking or payment: a 1 EUR sandbox order per gateway on staging (Nexi XPay, Everypay, Attica Bank, Klarna, PayPal) to the 3-D Secure/redirect step; never on production.
- Marketing sign-off for delaying or gating GTM/GA4/Ads/pixel, removing hard-coded snippets or the Woo GA extension, Order Attribution off, autoConfig off; GA4 DebugView, Tag Assistant (Consent tab: "On-page Default" before "Update"), Meta Test Events and a 7-day conversion comparison are human tools ([Elementor GA/GTM](https://elementor.com/help/add-google-analytics-and-gtm/)).
- Legal correctness of the banner (Greek HDPA: prior consent for every non-essential tracker, Reject on the first layer with equal styling and clicks, no cookie wall) ([HDPA guidelines](https://iapp.org/news/a/greek-dpa-issues-guidelines-on-cookies-and-trackers)).
- Googlebot: URL Inspection's rendered HTML must contain the loop/filter/forum text and image `src` after any delay or lazy-render change; Search Console's Core Web Vitals report is the ranking signal, not the lab score.
- Field confirmation weeks later: CrUX 28-day p75 (LCP ≤ 2.5 s, INP ≤ 200 ms, CLS ≤ 0.1); low-traffic sites have no record, so field success there needs a self-hosted web-vitals RUM beacon ([CrUX methodology](https://developer.chrome.com/docs/crux/methodology/)). INP cannot be measured in the lab.
- Greek text rendering, tap targets and animation quality on a physical phone.
- Design decisions the stopping rules surface: a canvas/video LCP, a banner that stays the LCP, first-party JS bootup still high after defer — no lever below the design level reaches 95 there, and the report must say so.
