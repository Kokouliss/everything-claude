# Design direction for premium hospitality sites within the 95+ budget

What makes a hotel, villa, water park or restaurant site look expensive is mostly restraint applied
precisely. Almost all of it fits the plugin's rules. Every statement here carries one of two labels:

- **[E] evidence:** research, a standard or a measurement, with its source.
- **[T] taste:** a convention seen on premium sites, to be confirmed or changed by the owner.

Where this file gives a direction, design-to-elementor.md gives the mechanics: font subsetting and upload
(§1), image export and sizes (§4).

## 1. Typography

### Families with Greek glyphs

A Greek site needs both families to ship Greek. The fonts below were checked against the Google Fonts
CSS2 subsets in October 2026 [E] ([font check](https://fonts.googleapis.com/css2?family=GFS+Didot:wght@400&display=swap)).

| | With Greek | Without Greek (rejected for EL/EN sites) |
|---|---|---|
| Sans | Inter, Manrope, Commissioner, Roboto Flex, IBM Plex Sans, Fira Sans, Open Sans, Noto Sans, Ubuntu Sans, Didact Gothic, Comfortaa, Advent Pro | Montserrat, Raleway, DM Sans, Jost, Figtree, Outfit, Plus Jakarta Sans, Onest, Hanken Grotesk, Instrument Sans, Bricolage Grotesque, Red Hat Display, Josefin Sans, Nunito Sans, Mulish |
| Serif / display | Literata, Source Serif 4, EB Garamond, Alegreya, Noto Serif Display, Noto Serif, Vollkorn, Cardo, Gentium Plus, GFS Didot (Greek-first Didot; Latin basic only) | Playfair Display, Cormorant (all cuts), Lora, Fraunces, Libre Caslon Text, Gelasio, Crimson Pro, Spectral, Merriweather, Marcellus, Cinzel |

Most of the "premium" display fonts designers reach for have no Greek. In Figma the Greek text still
renders, through a silent fallback font, so the handoff looks fine until the site is live [E].

**Gate before any font is proposed** [E]
([Manrope check](https://fonts.googleapis.com/css2?family=Manrope:wght@400&display=swap)):

```bash
curl -sA 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/130 Mobile Safari/537.36' \
  'https://fonts.googleapis.com/css2?family=<Family>:wght@400&display=swap' | grep -c '/\* greek \*/'
```

- 1, or 2 with greek-ext, means the family ships Greek. 0 means it does not.
- Without a browser UA the API returns a TTF-only CSS with no subset comments.
- For non-Google fonts, count the Greek code points (U+0370–03FF) with `fontTools`; expect ≥ 100.
- A variable font listed as "latin" on Google may still carry Greek in the foundry's file. Check that file.
- Record the result in the build log.

### Pairings that pass the gate

These pairings come from the corpus. The choice between them is [T].

| Heading | Body | Character |
|---|---|---|
| Noto Serif Display | Manrope | classic contrast serif + calm geometric sans |
| Literata | Inter | warm book serif + neutral UI sans |
| EB Garamond | Commissioner | old-style elegance + soft humanist sans |
| GFS Didot | Source Serif 4 | Greek-first Didot + readable text serif (check the Latin coverage of GFS Didot) |
| Inter | Inter | single variable family: one file per subset, weights cost 0 bytes |

File sizes and the subsetting command are in design-to-elementor §1. Use variable families where you
can, because extra weights then cost nothing.

### Sizes, scale, measure and leading

| Setting | Value | Label |
|---|---|---|
| Body text | 16–18 px; never below 16 px on inputs (iOS zooms below 16 px) | [E] ([Lighthouse font-size](https://developer.chrome.com/docs/lighthouse/seo/font-size); iOS zoom is long-standing WebKit behaviour, not re-verified) |
| h1 at 390 px | 32–40 px | [T] |
| Scale ratio | 1.2 on mobile, rising to 1.25–1.333 on desktop | [T] |
| Line length | 45–80 characters (`max-width` in `ch` on text classes) | [E] |
| Body line height | 1.5 | [E] |
| Display line height | about 1.05, letter-spacing −0.02em on large display sizes | [T] |
| Large text for contrast | ≥ 24 px regular or ≥ 18.66 px bold (3:1 applies) | [E] ([axe](https://dequeuniversity.com/rules/axe/4.10/color-contrast)) |

**Fluid scale.** This scale is computed from the corpus values: base 16 px at 390 px rising to 18 px at
1440 px, ratio 1.2 rising to 1.25. Each step is `16·1.2ⁿ` at 390 px and `18·1.25ⁿ` at 1440 px,
interpolated linearly. The `rem` term keeps browser zoom working.

```css
--step--1: clamp(0.833rem, 0.809rem + 0.102vw, 0.900rem); /* 13.3 → 14.4 px, captions only */
--step-0:  clamp(1.000rem, 0.954rem + 0.190vw, 1.125rem); /* 16.0 → 18.0 px, body */
--step-1:  clamp(1.200rem, 1.123rem + 0.314vw, 1.406rem); /* 19.2 → 22.5 px, lead / h4 */
--step-2:  clamp(1.440rem, 1.322rem + 0.484vw, 1.758rem); /* 23.0 → 28.1 px, h3 */
--step-3:  clamp(1.728rem, 1.554rem + 0.715vw, 2.197rem); /* 27.6 → 35.2 px, h2 */
--step-4:  clamp(2.074rem, 1.824rem + 1.025vw, 2.747rem); /* 33.2 → 43.9 px, h1 inner pages */
--step-5:  clamp(2.488rem, 2.137rem + 1.440vw, 3.433rem); /* 39.8 → 54.9 px, h1 home */
```

For a more dramatic display size [T], the corpus example is
`clamp(2rem, 1.2rem + 3.2vw, 4.5rem)` (32 → 72 px), which corresponds to ratio 1.333 at the top step.
Whether a Size Variable accepts a `clamp()` string on 4.3.x is unconfirmed (verify). The fallback is the
class's `custom_css` (elementor-implementation.md §3).

### Greek typographic correctness

- **All-caps Greek** [E] ([MDN text-transform](https://developer.mozilla.org/en-US/docs/Web/CSS/text-transform), [compat data](https://raw.githubusercontent.com/mdn/browser-compat-data/main/css/properties/text-transform.json)):
  - Uppercase Greek must drop the tonos: ΚΡΑΤΗΣΗ, not ΚΡΆΤΗΣΗ.
  - `text-transform: uppercase` does this only with `lang="el"` and only in Chrome and Firefox. Safari never strips the accents.
  - So type Greek capitals in the content. Keep a `label-caps` class for Latin text only.
- **Greek strings run longer** than English. Size buttons, menu items and cards with the Greek text, at 390 px and 1024 px [E].
- **Glyph check:** a Greek paragraph must render in the brand font. Check ά, ώ and ς (design-to-elementor §4).

### Polish rules for the classes [T]

These come from `make-interfaces-feel-better`:

- `text-wrap: balance` on headings and short titles.
- `text-wrap: pretty` on body text and captions, but not on long Greek prose (reflow cost).
- `font-variant-numeric: tabular-nums` on prices and counters.

## 2. Colour

### Numbers [E]

([contrast minimum](https://raw.githubusercontent.com/w3c/wcag/main/understanding/20/contrast-minimum.html), [axe](https://dequeuniversity.com/rules/axe/4.10/color-contrast))

| Element | Minimum contrast |
|---|---|
| Body text | 4.5:1 |
| Large text | 3:1 |
| Input borders, icons, focus indicators, button edges (against adjacent colours) | 3:1 |

- 2.999:1 fails. There is no rounding.
- Each state (hover, focus, pressed) must keep 3:1 against its surroundings. The states do not need to contrast with each other.
- Disabled controls are exempt.

**Semantic tokens: 8 at most** [E for the count rule in design-to-elementor §1; names from the corpus]:

`color-ink`, `color-ink-muted`, `color-surface`, `color-surface-alt`, `color-brand`, `color-accent`,
`color-border`, `color-focus`. The footer uses an inverted pair from the same set.

**Check every pair before creating Variables.** Keep the output in the build log:

```python
# python3 -I contrast.py  — WCAG 2.x relative-luminance contrast
def lum(h):
    c = [int(h.lstrip('#')[i:i+2], 16) / 255 for i in (0, 2, 4)]
    c = [x / 12.92 if x <= 0.04045 else ((x + 0.055) / 1.055) ** 2.4 for x in c]
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2]
def ratio(a, b):
    hi, lo = sorted((lum(a), lum(b)), reverse=True)
    return (hi + 0.05) / (lo + 0.05)
assert round(ratio('#000000', '#ffffff'), 1) == 21.0
pairs = {'ink-muted/surface': ('#5f5a54', '#faf7f2'), 'border/surface': ('#8a847c', '#faf7f2')}
for name, (fg, bg) in pairs.items():
    print(name, round(ratio(fg, bg), 2))  # text needs >= 4.5, UI/borders >= 3.0
```

Worked values, computed with the script above:

| Pair | Contrast | Result |
|---|---|---|
| `#5f5a54` on `#faf7f2` (muted text on warm off-white) | 6.39:1 | passes (the corpus quoted 6.1:1; recomputed) |
| `#8a847c` on `#faf7f2` (border) | 3.46:1 | passes as UI |
| `#a0a0a0` on `#ffffff` (a common designer grey) | 2.61:1 | fails for text |

**Text over photographs.** Measure the contrast against the lightest area of the image behind the text.
If it is under 4.5:1, add a scrim as a pseudo-element on the hero container, not baked into the LCP image
[E for the ratio; the scrim placement is from the hero recipe in the corpus].

### Proportions [T]

These are common to premium hotel sites:

- A warm off-white surface instead of pure `#fff`, and a near-black ink instead of `#000`.
- One deep brand hue for the primary button.
- One accent (sand, terracotta or sea), reserved for Book/Reserve, so the primary action is the only saturated element on the screen.
- Secondary buttons are outline or ghost.
- In the first viewport, exactly one saturated element.
- Never use the same accent for both links and decorative chips. The CTA loses prominence.

## 3. Imagery

### Evidence [E]

([NN/g photos as content](https://www.nngroup.com/articles/photos-as-web-content/))

- Users scrutinise photographs that carry information: the actual room, pool, owners and staff.
- They ignore big feel-good stock images.
- Real team portraits held 10 % more attention than bios that took 316 % more space.

**Rule:** every image answers a guest question. What do the bed, bathroom, view and breakfast look like?
How far is the beach? Who will greet me? If an image answers nothing, cut it. That also saves bytes.

### Photographer brief per site type [T]

From the corpus:

| Site type | Shots |
|---|---|
| Hotel / villa | room, bathroom, view, breakfast, pool, staff, map context |
| Restaurant | 5 dishes, the room, the team, the exterior at dusk |
| Water park | slides in use, families, food, the ticket desk |

### Treatment [T]

- One photographer and one consistent grade: warm, low saturation, no HDR.
- 3:2 landscape for cards and galleries. Corpus sizes: card 800 × 533, gallery 1200 × 800.
- Bake every gradient, duotone, blur or grain into the exported WebP/AVIF. Live CSS filters on large photos cost paint on every frame [E] ([animations guide](https://web.dev/articles/animations-guide)).
- No text inside images: it cannot be translated and fails as content.
- Hero sizes, formats and the export pipeline are in design-to-elementor §4.

## 4. Layout rhythm, spacing and whitespace

### Values the corpus anchors

| Value | Use | Label |
|---|---|---|
| 8 px | minimum gap between tap targets | [T] (house rule) |
| 12 px / 24 px | button padding (block / inline) | [T] |
| `clamp(16px, 4vw, 40px)` | page gutter | [T] |
| 48–64 px | section padding on mobile | [T] |
| `max-width: var(--container-max, 1200px)` | boxed content width | [T] |

Take the desktop section padding and the remaining steps from the design file. Store them as Size
Variables `space-1…space-n` and **never** use a bare `0`; write `0px` (elementor-implementation.md §1).

### Whitespace and chrome [E]

(NN/g sticky-headers study, 2021; no URL in the corpus)

- Keep the content-to-chrome ratio high: about 13:1 is good, 2:1 is bad.
- Mobile header ≤ 64 px. An optional bottom bar ≤ 56 px.
- Never let two fixed bars cover more than about 25 % of a 390 × 844 screen.

### Rhythm [T]

- One photograph per section.
- Alternate full-bleed bands (`section-full`) with boxed content (`section-boxed`).
- For editorial pages, use an asymmetric 6-column grid with 4/2 spans that collapses to one column on mobile.
- Cards: one column on mobile.
- Concentric radii: outer radius = inner radius + padding.
- Use borders for separation. Use shadows only on things that float.
- Avoid cards nested inside cards (tasteskill image-to-code anti-nested-box rule).

## 5. Static heroes that still look striking

### Evidence [E]

- **Attention.** 57 % of viewing time is above the fold, 74 % within the first 2,160 px, and 65 % of above-the-fold time is in the top half ([NN/g](https://www.nngroup.com/articles/auto-forwarding/)).
- **Static beats carousel.** A static hero forces one strong image and message and keeps the global navigation visible. If a carousel is unavoidable: ≤ 5 frames, show the count and position, and no auto-forward ([NN/g carousels](https://www.nngroup.com/articles/designing-effective-carousels/)).
- **Booking button.** When the Book button sat below the fold and an "Update" button sat above it, hotel users stalled ([MeasuringU](https://measuringu.com/hotel-ux/)).
- **Viewport units.** Use `svh`, not `100vh`: on iOS, `100vh` pushes the CTA under the browser UI ([viewport units](https://web.dev/blog/viewport-units)).

### Base recipe

From the corpus. The mechanics are in design-to-elementor §2/§5.

- The hero is an `e-div-block.hero` with `min-height: 100svh` on mobile and `80svh` on desktop.
- It holds the hero `<img>` (absolute, `object-fit: cover`) and a copy block: h1, a one-line value proposition, the primary button, and a text link.
- The scrim is a pseudo-element.
- The booking form comes second.
- The CTA's bounding box must sit inside 390 × 844.

### Compositions [T]

All are static and all keep one LCP:

| Composition | Layout | Why it reads premium |
|---|---|---|
| Full-bleed photo | Copy bottom-left over a scrim; booking form as a card just below the fold line on mobile, inline on desktop | One confident image, large serif h1 |
| Split | Text column left, photo right on desktop; on mobile the h1 is first and the photo follows (text LCP) | Editorial, fast (text LCP) |
| Text-first | Display-size h1 on a warm surface, eyebrow and CTA; a wide photo starts the next band | Fastest possible LCP; confident typography carries it |
| Offset overlap | Photo with a caption or booking card overlapping its lower edge (parent `relative`, card `absolute` on desktop, static on mobile) | Depth without motion |
| Framed | Photo inset inside a coloured band with generous margin | Gallery feel |

A "receding hero" (a CSS scroll-driven exit with zero JS) is motion. Leave it to `elementor-motion`; it
needs the owner's OK and a measurement.

## 6. What signals quality and what looks cheap

| Signals quality | Looks cheap | Label |
|---|---|---|
| Real photos of the actual place and people | Stock models, generic smiling couples | [E] NN/g |
| One static, art-directed hero | Sliders, auto-forwarding carousels, video heroes | [E] NN/g; plugin rules 3–4 |
| All-in price shown up front | "Excl. taxes & fees", four indistinguishable rate names | [E] [Baymard](https://baymard.com/research-articles/new-research-travel-accommodations) |
| Visible navigation, logo top-left | Hidden desktop nav (used in 27 % of tasks vs 48–50 %), centred logo (going home about 6× harder) | [E] [NN/g](https://www.nngroup.com/articles/centered-logos/) |
| Truthful "book direct" reasons | "Only 1 room left!", countdowns, "12 people viewing" | [E] UCPD / CMA (ux-patterns.md §2) |
| Correct Greek: typed capitals without tonos, Greek glyphs in the brand font | Fallback-font Greek, accented all-caps on iPhone | [E] MDN |
| Warm off-white + near-black, one accent | Pure white + pure black with photos, accent everywhere | [T] |
| `tabular-nums`, balanced headings, neutral image inset outline (`outline: 1px solid rgba(0,0,0,.1); outline-offset: -1px`) | Ragged headline wraps, jumping price digits | [T] make-interfaces-feel-better |
| Restraint in hover effects | ≥ 3 components with the same hover scale, any pulsing indicator, ≥ 3 components with the same blur entrance, stagger on ≥ 2 lists per view | [T] thresholds from design-motion-principles' anti-checklist |
| Purposeful colour | Gratuitous gradients, purple-to-blue defaults, glass cards with no purpose, generic centred hero over a stock gradient | [T] ECC design-system slop list |
| `transition` on transform/opacity, 150–300 ms | `transition: all`, layout-property animation | [E] web.dev animations guide |

Speed is part of the look. In the Google/Deloitte study, a 0.1 s mobile speed improvement correlated with
+10.1 % travel conversions [E] ([Milliseconds Make Millions](https://web.dev/case-studies/milliseconds-make-millions)).
The study is correlational and uses pre-Core-Web-Vitals metrics. Use it to frame trade-offs with owners,
never to promise a figure.

## 7. Reference sites

Award galleries could not be used as evidence. Awwwards reset the connection, CSSDA returned only its
shell, and [elementor.com/showcase](https://elementor.com/showcase/) is now marketing copy. What awards
reward (WebGL heroes, custom cursors, smooth scroll) is what the plugin forbids above the fold.

The two 2025 Awwwards honourable mentions found by search have **not been opened**. Do not cite them
until they are measured:

- Hotel Jägerhof by upgreight
- Grand Hotel Central

The figures below are single Lighthouse mobile runs from 2026-10-15
(`../../../references/sites-in-the-wild.md`). Borrow composition from these sites, never their weight.

| Site | Score | Borrow | Do not copy |
|---|---|---|---|
| pallada.gr (agency) | 86 | static hero, 2 families, delay/defer in place: the agency's best pattern | remaining render-blocking CSS |
| gapscout.com | 88 | text LCP, local fonts, zero third parties | — |
| element.how | 93 (72 on another run) | small static `<img>` hero (35 KB) with `fetchpriority=high`, 2 local woff2 | consent/commerce plugins that cap it |
| rometheme.net | 96 | static h1 hero, zero render-blocking | 550 KB of icon fonts |
| theartemispalace.gr | 68 | minimal stack | background video and font sprawl |
| sophali.gr, panorama-hotel.gr, eloundapeninsula.com, pilot-beach.gr | 25–54 | — | sliders, lazy-loaded hero, video heroes, booking engine React bundle on load, consent dialog as LCP |

Before adding a new reference, record its date, its mobile Lighthouse median and its LCP element type.
