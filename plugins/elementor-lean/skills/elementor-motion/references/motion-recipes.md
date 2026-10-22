# Motion recipes: premium effects that keep the 95+ budget

Sixteen effects with copy-ready code. Each one names the library it needs, gives a one-line cost and
lists what not to do. Costs come from `motion-budget.md`. Numbers from the local lab run are marked
*(measured, unreviewed)*.

Rules every recipe follows:

- Nothing animates in the first viewport at load, and nothing animates on the LCP element or its
  ancestors. Recipe 16 is the only above-the-fold motion: it runs after load + 1.5 s, on a decorative
  element that is not the LCP.
- Animate only `transform` and `opacity`. The marquee, the curtain wipes and the hover shadows are
  built so that this holds.
- Every rule sits inside `@media (prefers-reduced-motion: no-preference)` or behind a JS check of the
  same query.
- Content is visible when JS is off. CSS never pre-hides content. Scroll-driven start states exist
  only inside `@supports`, and JS hides only the elements it is about to animate that are still off
  screen.

## Shared setup

**Where it goes:**

- CSS: append to the child theme's `site.css` (the file elementor-design owns). Do not add a second
  stylesheet, because each one is another render-blocking request.
- JS blocks: paste them into `assets/js/motion-lite.js`. `motion-loader.js` (in `gsap-in-elementor.md`)
  imports that file on the first scroll, wheel, touchmove or keydown when the page has
  `[data-reveal]`, `[data-count]` or `.marquee`.
- Each JS block is wrapped in `{ }`, so you can paste any subset into the file.
- Classes are V4 Global Classes. Create a class with no styles if it is only a hook. Attributes go in
  the element's General tab > Attributes (v4) or Advanced > Attributes (v3 Pro).

House tokens. These are the Emil Kowalski values used by the local motion skills, plus tasteskill's
reveal and stagger timings:

```css
:root {
  --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
  --ease-in-out: cubic-bezier(0.77, 0, 0.175, 1);
  --ease-drawer: cubic-bezier(0.32, 0.72, 0, 1);
  --ease-expo: cubic-bezier(0.16, 1, 0.3, 1);
  --t-press: 140ms;
  --t-hover: 180ms;
  --t-ui: 240ms;
  --t-reveal: 800ms;
  --stagger-item: 70ms;
  --stagger-line: 90ms;
}
```

When a Global Class sets the same `transform`, `transition` or `opacity`, prefix the selector with
`.elementor` (specificity 0,3,0) and keep `site.css` loading after Elementor. Never use `!important`.

---

## 1. Section reveal on scroll

**Library:** none. CSS `view()`, with an IntersectionObserver + WAAPI fallback for browsers without
`view()`.
**Cost:** 0 KB of library. The fallback block is about 0.5 KB, loaded after the first scroll.

**Elementor:** add the Global Class `reveal` to the short children of a section (heading, text,
image, card), not to the section wrapper. Each subject must be shorter than about 400 px and start at
least 100 vh down at 390 px.

```css
@supports (animation-timeline: view()) {
  @media (prefers-reduced-motion: no-preference) {
    .reveal {
      animation: reveal-up linear both;
      animation-timeline: view(); /* must come after the shorthand */
      animation-range: entry 0% entry 40%;
    }
  }
}
@keyframes reveal-up {
  from { opacity: 0; transform: translateY(24px); }
}
```

The fallback also drives recipe 2 (`data-reveal`). Browsers that support `view()` skip `.reveal` here.

```js
// motion-lite.js block: reveal (recipes 1 and 2)
{
  const css = CSS.supports('animation-timeline: view()');
  const els = [...document.querySelectorAll(css ? '[data-reveal]' : '[data-reveal], .reveal')]
    .flatMap((el) => (el.dataset.reveal === 'stagger' ? [...el.children] : [el]))
    .filter((el) => el.getBoundingClientRect().top >= innerHeight); // on screen (or display:none) now: leave static
  if (els.length && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const io = new IntersectionObserver((entries) => {
      let i = 0;
      for (const { target, isIntersecting } of entries) {
        if (!isIntersecting) continue;
        io.unobserve(target);
        target.animate(
          [{ opacity: 0, transform: 'translateY(24px)' }, { opacity: 1, transform: 'none' }],
          { duration: 700, easing: 'cubic-bezier(0.23, 1, 0.32, 1)', delay: i++ * 70, fill: 'backwards' },
        );
        target.style.opacity = '';
      }
    }, { threshold: 0.15 });
    for (const el of els) {
      el.style.opacity = '0';
      io.observe(el);
    }
  }
}
```

**Do not:**

- Put `reveal` on a full section wrapper or on the first two top-level containers. A tall or
  fold-straddling subject sits half-faded at load and for a long scroll on phones.
- Write `animation-timeline` before the `animation` shorthand. The shorthand resets it and nothing
  animates.
- Pre-hide `.reveal` outside `@supports`. In browsers without `view()` the content would stay
  invisible.
- Use `overflow: hidden` on an ancestor. It creates a scroll container and stops `view()`. Use
  `overflow: clip`.
- Add `fill: 'forwards'` to WAAPI reveals. Let the stylesheet hold the end state.

## 2. Staggered card reveal

**Library:** none (the recipe 1 block). anime.js `stagger()` is an option only when the choreography
needs it (see `animejs-in-elementor.md`).
**Cost:** 0 KB of library. Lab result: CSS transition + IO reveal on 30 cards = score 100, LCP 751 ms,
TBT 0 *(measured, unreviewed)*.

**Elementor:** put the attribute `data-reveal="stagger"` on the container whose direct children are
the cards. For a Loop Grid, use the element that directly wraps the `.e-loop-item`s (check this in
the DOM). Cards that enter in the same observer callback are staggered 70 ms apart.

A CSS-only option needs no JS. It uses a linear scroll-driven entrance and offsets each card's range:

```css
@supports (animation-timeline: view()) {
  @media (prefers-reduced-motion: no-preference) {
    .cards > * {
      animation: reveal-up linear both;
      animation-timeline: view();
      animation-range: entry 0% entry 40%;
    }
    .cards > :nth-child(3n + 2) { animation-range: entry 5% entry 45%; }
    .cards > :nth-child(3n + 3) { animation-range: entry 10% entry 50%; }
  }
}
```

**Do not:**

- Stagger more than one list per view. design-motion-principles flags "stagger-spam".
- Put the hover transform (recipe 10) on the same node that the reveal animates. Reveal the Loop
  item and put the hover on the card container inside it.
- Use the Pro Counter or Posts widget entrance animations here. They add `elementor-invisible`.

## 3. Split-text heading reveal (below the fold)

**Library:** GSAP 3.15.0 + ScrollTrigger + SplitText, through the loader.
**Cost:** 49.9 KB gz, fetched after the first scroll and never in the lab run.

**Elementor:** put the attribute `data-anim="lines"` on an h2 or h3 below the fold. Use it on at most
3 headings per page. The full `motion.js` is in `gsap-in-elementor.md`. This is its `lines` branch,
shown as a function:

```js
// motion.js branch: data-anim="lines" (masks on desktop, a plain fade on phones)
function splitLines(el, gsap, SplitText) {
  const masked = innerWidth >= 1025;
  SplitText.create(el, {
    type: 'lines',
    mask: masked ? 'lines' : undefined,
    autoSplit: true, // re-splits when fonts load or the width changes
    onSplit: (self) => gsap.from(self.lines, {
      ...(masked ? { yPercent: 100 } : { y: 16, autoAlpha: 0 }),
      duration: 0.9,
      ease: 'expo.out',
      stagger: 0.09,
      scrollTrigger: { trigger: el, start: 'top 85%', once: true },
    }), // returning the tween lets SplitText clean it up on re-split
  });
}
```

**Do not:**

- Split the hero `h1` or any LCP text. That rewrites the LCP node and adds render delay.
- Use `type: 'chars'` on Greek paragraphs on phones. That creates hundreds of spans and a long task.
- Split before `document.fonts.ready` without `autoSplit`. The line breaks come out wrong.
- Use `mask: 'lines'` on phone headings of 3 or more lines.
- Skip the CLS gate (breakage-checklist §6). Fail on any shift whose source is a split heading.

## 4. Cheap parallax

**Library:** none (CSS scroll-driven animation).
**Cost:** 0 KB of JS, runs off the main thread in Chrome and Safari 26+. Other browsers get a static
image.

**Elementor:** add the Global Class `parallax` to the image's wrapper container and give the wrapper
a fixed `aspect-ratio`. The image is an Image widget with width and height set.

```css
.parallax { overflow: clip; view-timeline: --plx block; }
.parallax img { display: block; width: 100%; height: 100%; object-fit: cover; }
@supports (animation-timeline: view()) {
  @media (prefers-reduced-motion: no-preference) {
    .parallax img {
      animation: plx linear both;
      animation-timeline: --plx;
      animation-range: cover 0% cover 100%;
    }
  }
}
@keyframes plx {
  from { transform: translateY(-5%) scale(1.12); }
  to { transform: translateY(5%) scale(1.12); }
}
```

**Do not:**

- Use it on the hero or on any first-viewport image.
- Move more than about 8% of the box (tasteskill's study shows a median below 8%).
- Use `background-position` or `background-attachment: fixed`. Both repaint on every frame.
- Use Elementor Motion Effects > Scrolling on mobile. They are a main-thread jQuery scroll handler.
- Use ScrollTrigger scrub for an effect CSS can do.

## 5. Number counter without jQuery

**Library:** none (IntersectionObserver + `requestAnimationFrame`).
**Cost:** about 0.5 KB of JS after the first scroll. The text updates for 1.2 s, once, with its width
reserved so nothing shifts.

**Elementor:** in a Heading or Text Editor widget, write the final number into the HTML so that
no-JS visitors and crawlers see it, for example `<span data-count="1250">1.250</span>+`. Keep the
suffix outside the span. Formatting follows `<html lang>`, so `el` gives 1.250 and `en` gives 1,250.
Integers only.

```css
[data-count] { display: inline-block; font-variant-numeric: tabular-nums; }
```

```js
// motion-lite.js block: counter (recipe 5)
{
  const fmt = new Intl.NumberFormat(document.documentElement.lang || undefined);
  const els = [...document.querySelectorAll('[data-count]')]
    .filter((el) => el.getBoundingClientRect().top >= innerHeight);
  if (els.length && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const run = (el) => {
      const end = Number(el.dataset.count);
      const t0 = performance.now();
      const tick = (now) => {
        const p = Math.min((now - t0) / 1200, 1);
        el.textContent = fmt.format(Math.round(end * (1 - (1 - p) ** 3)));
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };
    const io = new IntersectionObserver((entries) => {
      for (const e of entries) if (e.isIntersecting) { io.unobserve(e.target); run(e.target); }
    }, { threshold: 0.6 });
    for (const el of els) {
      el.style.minWidth = `${el.offsetWidth}px`; // reserve the final width: no shift while digits grow
      el.textContent = fmt.format(0);
      io.observe(el);
    }
  }
}
```

**Do not:**

- Use the Pro Counter widget. It loads jquery-numerator and an extra chunk.
- Put a counter in the first viewport.
- Use `setInterval`.
- Count from 0 in the HTML. A blocked script would then show 0.
- Animate a CSS `@property` integer. That runs on the main thread and is not composited.

## 6. CSS marquee without setInterval

**Library:** none (CSS keyframes). The pause control is a checkbox, so it works before any JS loads.
**Cost:** 0 KB. Lab result: main thread 155 ms, the same as the bare page. A `setInterval` marquee
added 413 ms of main-thread work while TBT still read 0 *(measured, unreviewed)*.

**Elementor:** use one HTML widget (markup only, no `<script>`), below the fold, with at most 1 per
page. Translate the label per language (Παύση / Pause).

```html
<div class="marquee" role="region" aria-label="Partners">
  <label class="marquee__control"><input type="checkbox" class="marquee__pause"> Pause</label>
  <div class="marquee__track">
    <ul class="marquee__group"><li>Item one</li><li>Item two</li><li>Item three</li></ul>
    <ul class="marquee__group" aria-hidden="true"><li>Item one</li><li>Item two</li><li>Item three</li></ul>
  </div>
</div>
```

```css
.marquee { overflow: clip; }
.marquee__track { display: flex; width: max-content; }
.marquee__group { display: flex; gap: 3rem; padding-inline-end: 3rem; margin: 0; list-style: none; }
@media (prefers-reduced-motion: no-preference) {
  .marquee__track { animation: marquee 40s linear infinite; }
  .marquee:hover .marquee__track,
  .marquee:focus-within .marquee__track,
  .marquee:has(.marquee__pause:checked) .marquee__track { animation-play-state: paused; }
}
@media (prefers-reduced-motion: reduce) {
  .marquee__track { width: auto; flex-wrap: wrap; }
  .marquee__group[aria-hidden] { display: none; }
}
@keyframes marquee { to { transform: translateX(-50%); } }
```

Optional: pause it while it is off screen.

```js
// motion-lite.js block: marquee off-screen pause (recipe 6)
{
  const io = new IntersectionObserver((entries) => {
    for (const e of entries) {
      e.target.querySelector('.marquee__track').style.animationPlayState = e.isIntersecting ? '' : 'paused';
    }
  });
  for (const m of document.querySelectorAll('.marquee')) io.observe(m);
}
```

**Do not:**

- Use `setInterval` or rAF writing `left` / `margin-left`, or `background-position` scrolling.
- Use an autoplay Swiper with `loop: true` as a ticker.
- Run the marquee for more than 5 s without the pause control (WCAG 2.2.2, Level A).
- Put it in the first viewport.
- Use `gap` on `.marquee__track`, which breaks the seamless −50% loop.

## 7. Sticky stacking cards

**Library:** none (`position: sticky`).
**Cost:** 0 KB, and nothing animates. The stacking comes from native scrolling.

**Elementor:** add the Global Class `stack-card` to 3–6 sibling containers with opaque backgrounds.
Each card must be shorter than about 70 svh.

```css
.stack-card { position: sticky; top: calc(var(--header-h, 80px) + var(--i, 0) * 12px); }
.stack-card:nth-child(2) { --i: 1; }
.stack-card:nth-child(3) { --i: 2; }
.stack-card:nth-child(4) { --i: 3; }
.stack-card:nth-child(5) { --i: 4; }
.stack-card:nth-child(6) { --i: 5; }
```

**Do not:**

- Put `overflow: hidden` on any ancestor, or `content-visibility: auto` on the section. Both break
  sticky; add the class `no-cv` if the debloat skill applies content-visibility.
- Make cards taller than the viewport. Their bottoms can never be read.
- Pin the cards with GSAP.
- Use Pro Sticky on the cards.

## 8. Horizontal scroll section (desktop only)

**Library:** GSAP 3.15.0 + ScrollTrigger, desktop only. Phones get native scroll-snap.
**Cost:** 46.3 KB gz, after the first scroll, only for `(pointer: fine) and (min-width: 1025px)`.
Phones and the Lighthouse mobile run never execute it.

**Elementor:**

- Outer: a V4 Div block (`display: block`) with the class `hscroll`.
- Inside it: a Full-width container with the class `hscroll__track`, so its cards are direct children
  (boxed containers add `.e-con-inner`; **verify** in the DOM).
- Use one per page, with no Motion Effects or sticky elements anywhere in its ancestors or inside it.

```css
.hscroll { overflow: clip; }
.hscroll__track { display: flex; gap: 24px; overflow-x: auto; scroll-snap-type: x mandatory; overscroll-behavior-x: contain; }
.hscroll__track > * { flex: 0 0 min(80vw, 420px); scroll-snap-align: start; }
```

This is the desktop block of `motion.js` (full file in `gsap-in-elementor.md`), shown as a function:

```js
// motion.js desktop branch: one pinned horizontal section
function horizontal(gsap) {
  const wrap = document.querySelector('.hscroll');
  const track = wrap?.querySelector('.hscroll__track');
  if (!track) return;
  gsap.set(track, { overflow: 'visible' }); // matchMedia revert restores the mobile scroller
  const dist = () => track.scrollWidth - wrap.clientWidth;
  gsap.to(track, {
    x: () => -dist(),
    ease: 'none',
    scrollTrigger: {
      trigger: wrap, start: 'top top', end: () => `+=${dist()}`,
      pin: true, pinSpacing: true, anticipatePin: 1, scrub: 0.8,
      invalidateOnRefresh: true, refreshPriority: 1,
    },
  });
}
```

**Do not:**

- Pin on phones.
- Pin an `.e-con`, which is `display: flex`, without `pinSpacing: true`. The next section slides under
  it.
- Animate the pinned element itself. Animate the track inside it.
- Put the sticky header inside the pinned tree.
- Use ScrollSmoother or Lenis to "smooth" it.

## 9. Image mask reveal (curtain)

**Library:** none (CSS `view()`). A curtain that scales away gives the clip/mask look with
`transform` only.
**Cost:** 0 KB, off the main thread.

**Elementor:** add the Global Class `img-reveal` to the image's wrapper container, below the fold.
`--curtain` must match the section background.

```css
.img-reveal { position: relative; overflow: clip; view-timeline: --img block; }
@supports (animation-timeline: view()) {
  @media (prefers-reduced-motion: no-preference) {
    .img-reveal::after {
      content: ''; position: absolute; inset: 0; pointer-events: none;
      background: var(--curtain, #f4efe6); transform-origin: 50% 0;
      animation: curtain linear both; animation-timeline: --img; animation-range: entry 10% cover 40%;
    }
    .img-reveal img { animation: settle linear both; animation-timeline: --img; animation-range: entry 10% cover 40%; }
  }
}
@keyframes curtain { to { transform: scaleY(0); } }
@keyframes settle { from { transform: scale(1.15); } }
```

**Do not:**

- Use it on the hero image (the LCP).
- Animate `clip-path` or `mask-position`. The house rule is transform and opacity only. The corpus
  calls clip-path cheap in Chrome and Safari, but it is still paint.
- Use it on a photo over a gradient or another photo. The curtain needs a flat background.

## 10. Card and button hover micro-interactions

**Library:** none (CSS transitions).
**Cost:** 0 KB, zero on phones, safe for INP.

**Elementor:** add the Global Classes `card`, `img-zoom` (on the image's wrapper inside the card),
`btn` and `link`. Elementor's Hover tab variants are not wrapped in `@media (hover: hover)`, so keep
movement here.

```css
.elementor .card { position: relative; transition: transform var(--t-hover) var(--ease-out); }
.elementor .card::after { /* shadow layer: fade its opacity, never animate box-shadow */
  content: ''; position: absolute; inset: 0; border-radius: inherit; pointer-events: none;
  box-shadow: 0 16px 40px rgb(0 0 0 / 0.14); opacity: 0; transition: opacity var(--t-hover) var(--ease-out);
}
.elementor .img-zoom { overflow: clip; }
.elementor .img-zoom img { transition: transform 0.5s var(--ease-out); }
.elementor .btn { position: relative; isolation: isolate; overflow: clip; transition: transform var(--t-press) var(--ease-out); }
.elementor .btn::before {
  content: ''; position: absolute; inset: 0; z-index: -1; background: rgb(255 255 255 / 0.18);
  transform: translateX(-101%); transition: transform 0.35s var(--ease-out);
}
.elementor .link { position: relative; }
.elementor .link::after {
  content: ''; position: absolute; inset: auto 0 -2px; height: 1px; background: currentColor;
  transform: scaleX(0); transform-origin: 100% 50%; transition: transform 0.3s var(--ease-out);
}
@media (hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference) {
  .elementor .card:hover { transform: translateY(-4px); }
  .elementor .card:hover::after { opacity: 1; }
  .elementor .card:hover .img-zoom img { transform: scale(1.04); }
  .elementor .btn:hover::before { transform: none; }
  .elementor .link:hover::after { transform: scaleX(1); transform-origin: 0 50%; }
}
.elementor .card:focus-within::after { opacity: 1; }
.elementor .btn:active { transform: scale(0.97); } /* press feedback also on touch */
```

If the card container already uses `::after`, move the shadow layer to an inner element. Elementor's
background overlay uses `::before` (**verify** on your version).

**Do not:**

- Animate `box-shadow`, `border-radius`, `width` or `background-position`. On a 12-card grid that is
  a paint storm.
- Use `transition: all`.
- Use `scale(0)`; use 0.96–0.97.
- Hover-scale every component with the same value. Three or more trips design-motion-principles'
  hover-scale lint.
- Use Elementor hover animation classes that animate shadows or borders.

## 11. Magnetic button and cursor follower (desktop pointer only)

**Library:** none. `motion-pointer.js` is imported on the first `pointermove`, and only when
`(pointer: fine) and (hover: hover) and (min-width: 1025px) and (prefers-reduced-motion: no-preference)`
matches.
**Cost:** 0 bytes on phones and in the Lighthouse mobile run. On desktop it is one rAF write per frame.

**Elementor:** add the Global Class `magnetic` to 1–2 primary CTAs. Add the attribute `data-cursor` to
any element on pages that should get the follower dot.

```css
.magnetic { transition: transform 0.3s var(--ease-out); }
.cursor-dot {
  position: fixed; left: 0; top: 0; z-index: 9999; width: 10px; height: 10px; margin: -5px 0 0 -5px;
  border-radius: 50%; background: currentColor; pointer-events: none;
}
```

```js
// assets/js/motion-pointer.js (whole file)
for (const el of document.querySelectorAll('.magnetic')) {
  let r = null, x = 0, y = 0, raf = 0;
  const write = () => { raf = 0; el.style.transform = x || y ? `translate3d(${x}px, ${y}px, 0)` : ''; };
  el.addEventListener('pointermove', (e) => {
    r ??= el.getBoundingClientRect(); // one layout read per hover
    x = (e.clientX - r.left - r.width / 2) * 0.25;
    y = (e.clientY - r.top - r.height / 2) * 0.25;
    raf ||= requestAnimationFrame(write);
  }, { passive: true });
  el.addEventListener('pointerleave', () => { r = null; x = y = 0; raf ||= requestAnimationFrame(write); });
}

if (document.querySelector('[data-cursor]')) {
  const dot = document.body.appendChild(document.createElement('div'));
  dot.className = 'cursor-dot';
  dot.setAttribute('aria-hidden', 'true');
  let cx = 0, cy = 0, q = 0;
  addEventListener('pointermove', (e) => {
    cx = e.clientX; cy = e.clientY;
    q ||= requestAnimationFrame(() => { q = 0; dot.style.transform = `translate3d(${cx}px, ${cy}px, 0)`; });
  }, { passive: true });
}
```

**Do not:**

- Enqueue it site-wide or gate it with `wp_is_mobile()` or UA sniffing. Page cache serves the first
  device to everyone.
- Read layout on every `pointermove`.
- Hide the native cursor.
- Use it on booking or checkout forms, where Emil's frequency gate says no motion.
- Use `mix-blend-mode` on the follower.

## 12. Page transitions with View Transitions

**Library:** none. Cross-document View Transitions work in Chrome 126+ and Safari 18.2+. Other
browsers just navigate.
**Cost:** 0 KB. Cold navigations, including Lighthouse runs, never run a transition, so LCP is
unchanged.

**Elementor:** put this in child-theme CSS. Alternatively, use the WordPress Performance Team's View
Transitions plugin (Settings > Reading). Its reduced-motion handling and 400 ms default date from
1.1.0; the current 1.2.1 needs WP 6.9+. Keep Pro Page Transitions and the Preloader off.

```css
@media (prefers-reduced-motion: no-preference) {
  @view-transition { navigation: auto; }
}
::view-transition-old(root),
::view-transition-new(root) { animation-duration: 240ms; }
[data-elementor-type="header"] { view-transition-name: site-header; } /* header holds still */
```

**Do not:**

- Give `view-transition-name` to the hero/LCP image. A shared-element morph animates the LCP
  element. Name only small, non-LCP elements, and each name only once per page.
- Expect transitions across Polylang/WPML language subdomains. They are cross-origin, so there is no
  transition (and no harm).
- Let LiteSpeed UCSS or another RUCSS strip the rules. Safelist `@view-transition` and
  `::view-transition`.
- Add a `pagereveal` script unless it is a classic render-blocking script in `<head>`.

## 13. SVG line draw

**Library:** none (CSS `view()`). A flat curtain wipes off the SVG with `transform: scaleX`. This
gives a left-to-right "drawing" of routes, waves and signature underlines.
**Cost:** 0 KB, off the main thread.

**Elementor:** put the inline SVG (an HTML widget with markup only, or an SVG `<img>`) inside a
container with the Global Class `draw`, below the fold. `--curtain` must equal the section background.

```css
.draw { position: relative; overflow: clip; view-timeline: --draw block; }
@supports (animation-timeline: view()) {
  @media (prefers-reduced-motion: no-preference) {
    .draw::after {
      content: ''; position: absolute; inset: 0; pointer-events: none;
      background: var(--curtain, #fff); transform-origin: 100% 50%;
      animation: wipe linear both; animation-timeline: --draw; animation-range: entry 20% cover 50%;
    }
  }
}
@keyframes wipe { to { transform: scaleX(0); } }
```

**Do not:**

- Animate `stroke-dashoffset`, GSAP DrawSVG or anime `createDrawable()`. They are path-exact, but
  they repaint on every frame, which is outside the transform/opacity rule.
- Use Lottie for a line drawing. That is 63–76 KB gz plus JSON.
- Put the drawing in the hero.

## 14. Scroll progress bar

**Library:** none (CSS `scroll()` timeline on `transform: scaleX`).
**Cost:** 0 KB, no markup, off the main thread.

**Elementor:** put this in child-theme CSS, scoped by a body class (for example `single-post` for
articles).

```css
@supports (animation-timeline: scroll()) {
  @media (prefers-reduced-motion: no-preference) {
    body.single-post::before {
      content: ''; position: fixed; inset: 0 0 auto; z-index: 100; height: 3px;
      background: var(--e-global-color-accent, currentColor); transform-origin: 0 50%;
      animation: progress linear both; animation-timeline: scroll(root);
    }
  }
}
@keyframes progress { from { transform: scaleX(0); } }
```

**Do not:**

- Animate `width`.
- Use a scroll listener.
- Put the animation outside `@supports`. Browsers without scroll timelines would play it once as a
  time-based animation.

## 15. Accordion, tab and panel transitions

**Library:** none. CSS for accordions and panels, plus a delegated WAAPI block for tabs (after the
first scroll).
**Cost:** 0 KB. The height snaps and the content fades and slides about 6 px, which reads as smooth.

**Elementor:**

- Nested Accordion: set every item to start collapsed. The CSS assumes `<details>` markup
  (**verify** what your version prints).
- Nested Tabs: the block assumes `role="tab"` with `aria-controls` (**verify**).
- Off-canvas and popup content: add the Global Class `panel-in`, only on elements that are hidden at
  load.

```css
@media (prefers-reduced-motion: no-preference) {
  details[open] > :not(summary) { animation: panel-in var(--t-ui) var(--ease-out); }
  .panel-in { transition: opacity var(--t-ui) var(--ease-out), transform var(--t-ui) var(--ease-out); }
  @starting-style { .panel-in { opacity: 0; transform: translateY(16px); } }
}
@keyframes panel-in { from { opacity: 0; transform: translateY(-6px); } }
```

```js
// motion-lite.js block: tab panel fade (recipe 15)
if (!matchMedia('(prefers-reduced-motion: reduce)').matches) {
  document.addEventListener('click', (e) => {
    const tab = e.target.closest('[role="tab"]');
    const panel = tab && document.getElementById(tab.getAttribute('aria-controls'));
    if (panel) requestAnimationFrame(() => panel.animate(
      [{ opacity: 0, transform: 'translateY(-6px)' }, { opacity: 1, transform: 'none' }],
      { duration: 240, easing: 'cubic-bezier(0.23, 1, 0.32, 1)' },
    ));
  });
}
```

**Do not:**

- Animate `height`, `max-height` or `grid-template-rows`, or use `interpolate-size`. Each is a layout
  animation on every frame.
- Leave an accordion open by default. Its content would animate at load.
- Use `.panel-in` on anything visible at load. `@starting-style` also applies to first render.
- Pair this with Pro Interactions or popup entrance animations on the same element. Set the popup
  entrance animation to None.

## 16. Hero "after-LCP" flourish

**Library:** none. CSS keyframes keyed on the `after-lcp` class, which `motion-loader.js` adds at
`load` + 1.5 s.
**Cost:** 0 KB of library and about 0.3 KB of CSS. It is transform-only and finite, so it changes
neither LCP nor CLS. Lab result: work started at `load` + 1.5 s kept LCP at the bare-page 751 ms
*(measured, unreviewed)*.

**Elementor:** add the Global Class `hero-cue` to a small `aria-hidden` scroll-cue icon, and/or
`btn-sheen` to the hero CTA button. Never put either on the LCP element (the h1 or hero `<img>`) or on
a container that wraps it.

```css
.btn-sheen { position: relative; overflow: clip; }
.btn-sheen::after {
  content: ''; position: absolute; inset: 0; pointer-events: none; opacity: 0; transform: translateX(-100%);
  background: linear-gradient(100deg, transparent 30%, rgb(255 255 255 / 0.35), transparent 70%);
}
@media (prefers-reduced-motion: no-preference) {
  .after-lcp .hero-cue { animation: cue-nudge 1.4s var(--ease-in-out) 2; }
  .after-lcp .btn-sheen::after { animation: sheen 1.1s var(--ease-out) 1; }
}
@keyframes cue-nudge { 50% { transform: translateY(6px); } }
@keyframes sheen { from { opacity: 1; } to { opacity: 1; transform: translateX(100%); } }
```

Both elements are fully visible from the first paint. The animation starts and ends at their resting
state, so the filmstrip never shows them missing.

**Do not:**

- Start from `opacity: 0` or an off-screen transform.
- Use an Elementor entrance animation or a Page-load Interaction on hero content. That costs 3.6–7.8 s
  of render delay on the agency's sites.
- Loop it (`infinite` pulse or glow on a small element is flagged by design-motion-principles).
- Animate the hero container or its background.
- Add an Animated Headline, typewriter or SplitText to the h1.
