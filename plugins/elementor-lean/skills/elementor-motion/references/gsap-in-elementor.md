# GSAP in Elementor without losing the score

GSAP is the top rung of the ladder. Use it for pins, horizontal scroll, scrubbed sequences and
SplitText, where its `matchMedia` revert and ScrollTrigger refresh logic earn their 46 KB. The bytes
are not what costs points; running it at load is. platonos ships gsap + ScrollTrigger + SplitText at
load and pays 1.78 s of bootup and 358 ms TBT (score 84). The loader below keeps GSAP out of the lab
run entirely.

## 1. Licence and version

- GSAP has been 100% free for commercial use since 3.13 (April 2025), including every former Club
  plugin: ScrollTrigger, SplitText, ScrollSmoother, MorphSVG, DrawSVG, Flip, Draggable, Inertia,
  MotionPath and Observer ([3.13 post](https://gsap.com/blog/3-13/)).
- The current release is **3.15.0** (13 Apr 2026). It adds `easeReverse` and deprecates `yoyoEase`.
- Licence: GreenSock's Standard "no charge" licence, not MIT
  ([README](https://cdn.jsdelivr.net/npm/gsap@3.15.0/README.md)). Keep the header comment in every file.
- Install from public npm or jsDelivr. Remove any `@gsap:registry` / `npm.greensock.com` lines from
  `.npmrc`; that private registry is retired.
- Do not use an add-on pack to get GSAP. Animation Addons for Elementor bundles GSAP, ScrollSmoother
  and 100+ widgets; it breaks the one-pack rule (sites-in-the-wild rule 16). If a client already
  runs it, treat it as the one allowed pack, turn off its Smooth Scroller, cursor and text
  extensions, and dequeue it on pages without its widgets.

## 2. Sizes (3.15.0, gzip -9, measured from jsDelivr)

| File (`dist/`) | Raw | Gzip |
|---|---|---|
| `gsap.min.js` (core) | 72.9 KB | **28.3 KB** |
| `ScrollTrigger.min.js` (bundles Observer) | 44.6 KB | **18.0 KB** |
| `SplitText.min.js` | 7.7 KB | **3.7 KB** |
| `ScrollSmoother.min.js` | 13.4 KB | 5.5 KB (never ship) |
| `Flip.min.js` | 25.5 KB | 9.7 KB |
| `MorphSVGPlugin.min.js` | 21.2 KB | 9.6 KB |
| `DrawSVGPlugin.min.js` | 4.4 KB | 2.2 KB |
| `Observer.min.js` | 10.0 KB | 4.3 KB |
| `ScrollToPlugin.min.js` | 4.1 KB | 2.0 KB |
| `MotionPathPlugin.min.js` | 22.0 KB | 9.7 KB |
| `TextPlugin.min.js` | 10.6 KB | 3.6 KB |
| `CustomEase.min.js` | 7.1 KB | 3.7 KB |
| `Draggable.min.js` | 35.8 KB | 13.5 KB |
| `InertiaPlugin.min.js` | 7.3 KB | 3.3 KB |
| `all.min.js` | 334 KB | 124 KB, **never ship** |

- The typical reveal stack (core + ScrollTrigger) is **46.3 KB gz**, or **49.9 KB** with SplitText.
  An esbuild bundle of core + ScrollTrigger is 45.1 KB gz *(measured, unreviewed)*.
- These figures hold only for `dist/*.min.js` or an esbuild bundle. The npm package-root ESM files
  (`gsap-core.js`, `CSSPlugin.js`, `ScrollTrigger.js`, …) come to about **109 KB gz / 375 KB raw**.
  Never copy them into the theme.

Sources: [jsDelivr dist](https://cdn.jsdelivr.net/npm/gsap@3.15.0/dist/),
[ScrollTrigger.js](https://cdn.jsdelivr.net/npm/gsap@3.15.0/ScrollTrigger.js).

## 3. Self-hosting

1. Download only the files you use from `https://cdn.jsdelivr.net/npm/gsap@3.15.0/dist/` into
   `wp-content/themes/CHILD/assets/js/vendor/gsap-3.15.0/`. The version in the path is the cache key.
2. Usually that is `gsap.min.js`, `ScrollTrigger.min.js` and, optionally, `SplitText.min.js`. Add Flip,
   Draggable or MotionPath only when a component uses them.
3. Upload text files with `novamira/write-file` (`{path, content, mode: "overwrite", create_directories:
   true}`, path relative to ABSPATH). Check the ability's arguments with `mcp-adapter-get-ability-info`
   first.
4. Never load GSAP from a CDN at runtime. It counts against the third-party budget, needs an extra
   connection and gets no cross-site cache.

Optional build step instead of the UMD files (pin the version, commit the output):

```sh
npm i -E gsap@3.15.0
npx esbuild src/motion.js --bundle --minify --format=esm --outfile=assets/js/motion.js
```

In that source, replace the `script()` helper and its three calls in §4's `motion.js` with
`import { gsap } from 'gsap'; import { ScrollTrigger } from 'gsap/ScrollTrigger'; import { SplitText } from 'gsap/SplitText';`.
Leave the rest unchanged. SplitText is then always bundled (+3.7 KB).

## 4. The score-safe loader

### How it works

- **One script in the HTML.** `motion-loader.js` is the only motion script in the page: a module
  under 1 KB.
- **Libraries load on movement.** It imports the library tier (`motion.js`) only after the first
  `scroll`, `wheel`, `touchmove` or `keydown`. Lighthouse never scrolls, so the lab run executes zero
  GSAP. A real visitor gets it before reaching the first animated block.
- **Never an IntersectionObserver.** The Lighthouse mobile viewport is 412×823. With a 300 px
  `rootMargin`, an observer on the first `[data-anim]` fires at load whenever that element starts
  above 1,123 px, so GSAP lands in `bootup-time` and TBT.
- **Never `pointerdown` or `click`.** They put the module's parse inside the first interaction's INP.
- **Idle is also out for libraries.** TBT is summed between FCP and TTI. On a 4× slowed CPU, even
  `requestIdleCallback` after `load` can fall inside that window.
- **Cache busting.** The loader forwards its own `?ver=` to the modules it imports, so bumping the
  version in PHP refreshes all of them.

### PHP (the enqueue)

Save as `wp-content/novamira-sandbox/motion.php` via `novamira/write-file`, or put it in the child
theme's `functions.php` when the theme lives in git:

```php
<?php
// Prints the one motion loader as a module that Delay-JS / combine layers must leave alone.
add_action('wp_enqueue_scripts', function () {
    $files = glob(get_stylesheet_directory() . '/assets/js/motion*.js');
    if (!$files) return;
    $ver = max(array_map('filemtime', $files)); // any motion file edit busts every module's cache
    wp_enqueue_script('motion-loader', get_stylesheet_directory_uri() . '/assets/js/motion-loader.js', [], $ver, ['in_footer' => true]);
});

add_filter('script_loader_tag', function ($tag, $handle) {
    if ($handle !== 'motion-loader') return $tag;
    // shortcut: core's script-module API has no tag filter for these attributes (class-wp-script-modules.php); switch when WP adds one
    $tag = preg_replace('/\stype=(["\'])text\/javascript\1/', '', $tag);
    return str_replace('<script ', '<script type="module" nowprocket data-no-optimize="1" data-no-defer="1" ', $tag);
}, 10, 2);
```

Use the classic enqueue plus `script_loader_tag`, not `wp_enqueue_script_module()`. The module API
accepts only `fetchpriority` and has no filter for the tag, so `nowprocket` / `data-no-optimize`
cannot be added, and Delay-JS would rewrite the tag
([class-wp-script-modules.php](https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-includes/class-wp-script-modules.php)).

**No theme access** (Pro Custom Code fallback): create one item with location `<body> – End`,
priority 10, condition Entire Site, containing only the loader tag. Never put animation code there.

```html
<script type="module" src="/wp-content/themes/CHILD/assets/js/motion-loader.js?ver=1" nowprocket data-no-optimize="1" data-no-defer="1"></script>
```

### `assets/js/motion-loader.js` (whole file, shared with the anime.js tier)

```js
// The only motion script in the HTML. Libraries load on first movement, never at load.
const html = document.documentElement;
const ver = new URL(import.meta.url).search; // ?ver= from PHP, forwarded so every module busts together
const load = (file) => import(new URL(`./${file}${ver}`, import.meta.url));
const once = (events, fn) => {
  const run = () => { for (const ev of events) removeEventListener(ev, run); fn(); };
  for (const ev of events) addEventListener(ev, run, { passive: true });
};

if (!document.body.classList.contains('elementor-editor-active')) {
  // After-LCP flag for CSS flourishes (recipe 16): load + 1.5 s, outside the LCP window
  const flag = () => setTimeout(() => html.classList.add('after-lcp'), 1500);
  if (document.readyState === 'complete') flag();
  else addEventListener('load', flag, { once: true });

  // Reduced motion: nothing would run, so download nothing (content is already in its visible state)
  const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const lite = !calm && document.querySelector('[data-reveal], [data-count], .marquee');
  const lib = !calm && document.querySelector('[data-anim]');
  // Movement only: an IntersectionObserver fires inside the 412x823 lab viewport; a click puts the parse inside INP
  if (lite || lib) {
    once(['scroll', 'wheel', 'touchmove', 'keydown'], () => {
      if (lite) load('motion-lite.js');
      if (lib) load('motion.js');
    });
  }

  if (document.querySelector('.magnetic, [data-cursor]')
    && matchMedia('(pointer: fine) and (hover: hover) and (min-width: 1025px) and (prefers-reduced-motion: no-preference)').matches) {
    once(['pointermove'], () => load('motion-pointer.js'));
  }
}
```

### `assets/js/motion.js` (whole file, GSAP tier)

```js
// GSAP tier, imported by motion-loader.js after the first scroll/wheel/touchmove/keydown.
// The dist/*.min.js files are UMD: run them as classic scripts. import() of them throws in strict module code
// ("Cannot set property window of #<Window>", tested in Chrome 148). Load in order: ScrollTrigger needs gsap.
const script = (file) => new Promise((resolve, reject) => {
  const s = document.createElement('script');
  s.src = new URL(`./vendor/gsap-3.15.0/${file}`, import.meta.url);
  s.async = false;
  s.onload = resolve;
  s.onerror = reject;
  document.head.append(s);
});
await script('gsap.min.js');
await script('ScrollTrigger.min.js');
if (document.querySelector('[data-anim="lines"]')) await script('SplitText.min.js');
const { gsap, ScrollTrigger, SplitText } = window;
gsap.registerPlugin(ScrollTrigger, ...(SplitText ? [SplitText] : []));
ScrollTrigger.config({ ignoreMobileResize: true, limitCallbacks: true });
gsap.defaults({ duration: 0.8, ease: 'power2.out' });

const PRESETS = {
  'fade-up': { y: 32, autoAlpha: 0 },
  fade: { autoAlpha: 0 },
  'scale-in': { scale: 0.96, autoAlpha: 0 },
};
const ONCE = (trigger) => ({ trigger, start: 'top 85%', once: true });
// On screen (or display:none, whose rect is 0) when GSAP arrives: leave static, never snap back to a start state.
const offScreen = (el) => el.getBoundingClientRect().top >= innerHeight;

function reveals(root, seen) {
  for (const el of root.querySelectorAll('[data-anim]')) {
    if (seen.has(el)) continue;
    seen.add(el);
    if (!offScreen(el)) continue;
    const kind = el.dataset.anim;
    if (kind === 'stagger') {
      const items = [...(el.dataset.animItems ? el.querySelectorAll(el.dataset.animItems) : el.children)];
      gsap.set(items, { y: 24, autoAlpha: 0 });
      ScrollTrigger.batch(items, {
        start: 'top 85%',
        once: true,
        onEnter: (batch) => gsap.to(batch, { y: 0, autoAlpha: 1, stagger: 0.07 }),
      });
    } else if (kind === 'lines' && SplitText) {
      const masked = innerWidth >= 1025; // no line masks on phones (3+ line headings shift)
      SplitText.create(el, {
        type: 'lines',
        mask: masked ? 'lines' : undefined,
        autoSplit: true,
        onSplit: (self) => gsap.from(self.lines, {
          ...(masked ? { yPercent: 100 } : { y: 16, autoAlpha: 0 }),
          duration: 0.9,
          ease: 'expo.out',
          stagger: 0.09,
          scrollTrigger: ONCE(el),
        }),
      });
    } else if (PRESETS[kind]) {
      gsap.from(el, { ...PRESETS[kind], scrollTrigger: ONCE(el) });
    }
  }
}

await document.fonts.ready; // SplitText line breaks and trigger positions need final fonts
const mm = gsap.matchMedia();

// Everyone without reduced motion. Under reduce nothing runs and every target stays in its visible CSS state.
mm.add('(prefers-reduced-motion: no-preference)', (ctx) => {
  const seen = new WeakSet();
  reveals(document, seen);
  // Pro popups dispatch window CustomEvents (no jQuery needed). The modal is its own scroller: plain tweens.
  const onShow = (e) => {
    const root = e.detail?.instance?.$element?.[0];
    if (root) ctx.add(() => gsap.from(root.querySelectorAll('[data-anim]'), { y: 16, autoAlpha: 0, duration: 0.5, stagger: 0.05 }));
  };
  const onHide = () => ScrollTrigger.refresh();
  addEventListener('elementor/popup/show', onShow);
  addEventListener('elementor/popup/hide', onHide);
  return () => {
    removeEventListener('elementor/popup/show', onShow);
    removeEventListener('elementor/popup/hide', onHide);
  };
});

// Desktop pointer only: pins and horizontal scroll. Phones and the Lighthouse mobile run never match.
mm.add('(prefers-reduced-motion: no-preference) and (pointer: fine) and (min-width: 1025px)', () => {
  const wrap = document.querySelector('.hscroll'); // one pin per page
  const track = wrap?.querySelector('.hscroll__track');
  if (!track) return;
  gsap.set(track, { overflow: 'visible' });
  const dist = () => track.scrollWidth - wrap.clientWidth;
  gsap.to(track, {
    x: () => -dist(),
    ease: 'none',
    scrollTrigger: {
      trigger: wrap, start: 'top top', end: () => `+=${dist()}`,
      pin: true, pinSpacing: true, anticipatePin: 1, scrub: 0.8,
      invalidateOnRefresh: true, refreshPriority: 1, // pins change page height: measure them first
    },
  });
});

// Loop Grid "Load more", lazy images and AJAX change layout after ScrollTrigger measured it.
let t;
window.elementorFrontend?.hooks?.addAction('frontend/element_ready/global', () => {
  clearTimeout(t);
  t = setTimeout(() => ScrollTrigger.refresh(), 100);
});
ScrollTrigger.refresh();
```

Notes on the file:

- **Load more:** check that `frontend/element_ready/global` fires for Loop Grid "Load more" items on
  the installed version (**verify**). If it does not, call `ScrollTrigger.refresh()` from the
  pagination's own event.
- **UMD files:** the corpus claimed that `await import()` of the UMD files attaches `window.gsap`. In
  Chrome 148 it throws instead, because module code is strict. The classic-script helper above is the
  tested path; the esbuild ESM bundle (§3) is the other one.
- **AMD conflict:** if a plugin exposes an AMD `define`, the UMD files register with it instead of
  `window`. In that case use the esbuild bundle (§3).
- **Cache plugins:** the vendor tags are injected at runtime, after cache plugins rewrite the HTML, so
  Delay-JS never sees them (**verify** on the site's optimizer).

### CSS that goes with it (child theme `site.css`)

```css
html { scroll-behavior: auto; } /* smooth makes ScrollTrigger measure at the wrong scroll position */
[data-anim], [data-anim="stagger"] > * { transition-property: color, background-color, border-color; } /* GSAP owns transform/opacity */
@media (prefers-reduced-motion: reduce) {
  .elementor-invisible { visibility: visible; } /* reduced motion never removes Elementor's class */
}
```

## 5. ScrollTrigger patterns that stay cheap

- **Tween only these:** `x`, `y`, `xPercent`, `yPercent`, `scale`, `rotation`, `opacity` and
  `autoAlpha`. Never `height`, `width`, `top`, `left`, `margin`, `filter: blur()` or
  `background-position`, which fail Lighthouse's `non-composited-animations` audit.
- **Reveals:** use `once: true`, and `ScrollTrigger.batch()` for grids. Call `ScrollTrigger.config()`
  once, set `gsap.defaults()` once, and ship no `markers`.
- **Matching conditions:** wrap all setup in `gsap.matchMedia()`. Use separate `mm.add` calls per
  condition. With the object form, the handler runs only while at least one key matches, so a phone
  without reduced motion would get nothing.
- **Reduced motion:** run nothing, because targets are visible by default. If anything is pre-hidden
  in CSS, set its end state with `gsap.set` instead of skipping it.
- **Cleanup:** `matchMedia` contexts revert their tweens, ScrollTriggers and SplitTexts automatically
  when a query stops matching. For work created later (popups, event handlers), use the context's
  `ctx.add(() => …)` so it is reverted too. Kill everything with `mm.revert()`.
- **Pins:** desktop only, one per page, `pinSpacing: true` on flex containers, `scrub` of 0.5 or more,
  `anticipatePin: 1`. Animate only the elements inside the pin. No transform or `will-change` on any
  ancestor.
- **Sticky header:** keep the Pro Sticky header (`.elementor-sticky`) outside every pinned tree.
- **Toggles** (menus, accordions): they must work without GSAP. To enhance them after import, reuse
  one paused timeline per toggle (`easeReverse` exists since 3.15).
- **Pointer effects:** desktop only, with `gsap.quickTo(el, 'x', { duration: 0.4, ease: 'power3' })`
  inside `pointermove`.
- **Event handlers:** keep each one under 50 ms.
- **Smooth scrolling:** never ScrollSmoother or Lenis. For a "catch-up" feel, use `scrub: 1` on
  scroll-linked tweens.

Sources: [ScrollTrigger docs](https://gsap.com/docs/v3/Plugins/ScrollTrigger/),
[ScrollTrigger.config()](https://gsap.com/docs/v3/Plugins/ScrollTrigger/static.config()/),
[non-composited animations](https://developer.chrome.com/docs/lighthouse/performance/non-composited-animations).

## 6. SplitText

SplitText was rewritten in 3.13: it is now 3.7 KB gz and adds `autoSplit`, `onSplit`, `mask` and
automatic `aria`.

- **Where:** `data-anim="lines"` only, on h2 and h3 below the fold, at most 3 per page.
- **Split type:** lines (and words at most) on phones, never chars. For line masks on phones, see
  recipe 3.
- **When:** split after `document.fonts.ready` with `autoSplit: true`, and return the tween from
  `onSplit` so re-splits clean it up.
- **Fonts:** keep `font-display: swap` and the preloaded heading woff2 (rule 11) so `fonts.ready`
  resolves early.
- **Never:** on the hero h1 or any LCP text.
- **Verify:**
  - the LCP node in Lighthouse has no injected `aria-label`;
  - the DOM node delta after splitting is under 150;
  - the console shows no SplitText font warning;
  - the scroll CLS gate (breakage-checklist §6) shows no shift whose source is a split heading.

## 7. Targeting Elementor markup

- **Hooks you own:**
  - **v4:** the attribute in General tab > Attribute section (the element's `attributes` prop) and
    Global Classes (`settings.classes`).
  - **v3 Pro:** Advanced > Attributes `data-anim|fade-up`, which is `_attributes: "data-anim|fade-up"`
    in the JSON.
  - **Exact prop shape:** read an existing element with `elementor-get-content` and copy its
    `attributes` shape (**verify**).
- **Presets:** `fade-up`, `fade`, `scale-in`, `stagger` (optional `data-anim-items=".e-loop-item"`)
  and `lines`. Use at most 20 per page, none in the first top-level container, header or footer, and
  one mechanism per element.
- **Never select by** `.elementor-element-{id}` or `[data-id]`. The 8-character id changes on import
  and duplicate. Also avoid generic `.e-con`, which also matches the hero, and widget internals. Use
  `data-id` only to find the node in `tree.json` while writing.
- **Editor:** the loader skips the editor (`body.elementor-editor-active`).

## 8. Conflicts and fixes

| Conflict | Symptom | Fix |
|---|---|---|
| Elementor entrance animation (`_animation`) on the same element | `elementor-invisible` keeps it `visibility:hidden` under Delay-JS, or it double-animates | One mechanism per element. Set Entrance Animation to None wherever `data-anim` is set |
| Pro Interaction on the same element | Two engines fight | If a page has `data-anim`, GSAP does all its reveals and the page has no Interactions |
| Motion Effects (scrolling) on an ancestor | Pins jump, `position: fixed` breaks | Pin only containers whose ancestors have no `motion_fx_*`, transform or `will-change` |
| Pro Sticky header inside a pinned tree | Header scrolls away | Header stays a Theme Builder header outside `main`. No `.elementor-sticky` inside `[data-pin]` / `.hscroll` |
| CSS transitions on GSAP-driven properties | Interpolation fights, jank | `transition-property` excludes transform/opacity on `[data-anim]` (CSS above) |
| WP Rocket Delay JS | Tag rewritten to `rocketlazyloadscript` | `nowprocket` on the tag (works on external and inline tags), plus "Excluded JavaScript Files": `motion-loader` |
| LiteSpeed Delayed / Guest Mode / Combine | `type="litespeed/javascript"` / `data-src` | `type="module"` and `data-no-optimize="1"` are skipped by design; `data-no-defer="1"` for Guest Mode; keep JS Combine OFF; add `motion-loader.js` to "JS Deferred/Delayed Excludes" |
| Perfmatters Delay JS | Delayed module | Add `motion-loader` and `/js/vendor/gsap-` to "Excluded from Delay" (it matches by string, not by attribute) |
| FlyingPress Delay JS | Delayed module (module handling undocumented) | Always add the keyword `motion-loader` and `/js/vendor/gsap-` to Exclude |
| Popups | Triggers measure a `display:none` modal (start/end 0) | Window `elementor/popup/show` → plain tweens via `ctx.add`, refresh on hide |
| Lazy images, AJAX, Loop Grid "Load more" | Reveals fire early or late | width/height on every image (rules 3/5), refresh after import, fonts and element_ready |
| `scroll-behavior: smooth` | Markers drift | `html { scroll-behavior: auto }` |

Verify a cached page with view-source. The tag must still read
`<script type="module" … src=".../motion-loader.js?ver=…">`, with no `rocketlazyloadscript`,
`litespeed/javascript`, `data-src` or `data-rocket-src`.

Sources: [WP Rocket delay JS](https://docs.wp-rocket.me/article/1349-delay-javascript-execution),
[FlyingPress delay all JS](https://docs.flyingpress.com/en/articles/11406701-delay-all-javascript),
[custom attributes](https://elementor.com/help/custom-attributes-pro/),
[motion effects](https://elementor.com/help/motion-effects/).

## 9. Proving it

Run a mobile Lighthouse median of 3 and check:

- `network-requests` lists `motion-loader.js` only: no `gsap`, `motion.js` or `vendor/` entry;
- `bootup-time` and `long-tasks` are unchanged against the static build;
- the LCP node is unchanged and Render Delay is under 300 ms.

Then run a Puppeteer or Playwright pass:

1. One `page.mouse.wheel({ deltaY: 10 })` loads the GSAP files. Check that `gsap.version === '3.15.0'`.
2. A reduced-motion pass leaves every `[data-anim]` at `opacity: 1`.
3. Resizing across 1024/1025 px keeps `ScrollTrigger.getAll().length` stable.
4. After a popup closes, the trigger count returns to its previous value.

Pilot: move platonos' load-time gsap + ScrollTrigger + SplitText onto this loader. Record the
before/after medians (bootup, TBT, score) as the plugin's first first-party "GSAP at 90+" example.
No independently published GSAP site scoring 90+ on mobile could be verified.
