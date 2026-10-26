# Implementing sophisticated designs in Elementor 4.x

This file gives exact option names, JSON shapes and code for turning the design into Elementor 4.3.x
without adding JavaScript or add-on packs. Source code was read at elementor/elementor 4.3.5 and Pro 4.3.1
(read-only, from the kand.dev6 site). Help pages are as of 2026. Anything not confirmed is marked
**verify**. Element JSON shapes, the `elementor-set-content` contract and the per-page sequence are in
`../../../references/design-to-elementor.md` §5. This file does not repeat them.

## 0. Before anything: what this site can do

1. Run `novamira/elementor-check-setup`. Atomic `e-*` elements, Global Classes and Variables need `atomic.runtime_available`, `global_classes_available` and `variables_available` all true. Pre-conditions and menu paths are in design-to-elementor §7.
2. Run `mcp-adapter-discover-abilities` and record which of these exist:
   - `elementor-create-global-class`
   - `elementor-get-style-schema`
   - `elementor-add-interaction`
   - `elementor-create-atomic-widget`

   On kand.dev6 (Elementor 4.3.3, Pro 4.3.1, Novamira 1.12.7 + Pro 1.11.0) and platonos.dev6, **none of them was registered**. The Elementor set there was exactly 24 abilities: get/set-content, create/list/get/edit/delete-template, get/set-template-conditions, add/edit/delete-element, delete-element-style, get-schema, list-v3-styles, create/edit/delete-v3-color, create/edit/delete-v3-typography, list-dynamic-tags, get-dynamic-tag, apply-dynamic-tag, check-setup and clear-document-cache.
3. Read each ability with `mcp-adapter-get-ability-info` before its first use. Every call goes through `mcp__novamira-<site>__mcp-adapter-execute-ability` with `{"ability_name": "novamira/<name>", "parameters": {…}}`.
4. **If the atomic runtime is off**, take one of two routes:
   - Ask the owner to activate it, staging first: Atomic Editor → "Activate the new experience".
   - Build with v3 containers, kit Global Colors/Fonts (`elementor-create-v3-color` / `elementor-create-v3-typography`) and child-theme CSS. In that case skip Variables, Classes and Interactions.
5. **Second route for classes and variables: Elementor's built-in MCP abilities** (`elementor/manage-classes`, `elementor/manage-global-variable`). They exist only when all of the following are true:
   - the WordPress MCP Adapter plugin is active;
   - the Abilities API is present (core since WP 6.9);
   - Elementor's MCP exposure toggle is on;
   - the user is an admin.

   Ask the owner before installing or enabling anything. Turn exposure off after the build.

   The REST routes below need no adapter.

## 1. Tokens: V4 Variables and Global Classes

### How Variables render

(source: `modules/variables/classes/css-renderer.php`, Elementor 4.3.5)

Each variable becomes a custom property named after its **label**, inside `:root{…}` in the kit's post
CSS file:

```css
:root{--color-brand:#1f3a4d;--color-surface:#faf7f2;--font-heading:Noto Serif Display;--space-section:64px;}
```

**Types:**

| Type | Introduced | Edition |
|---|---|---|
| `global-color-variable` | 3.31 | free |
| `global-font-variable` | 3.31 | free; **family only** (size, weight and line height go into classes) |
| `global-size-variable` | 3.32 | Pro |

**REST** (namespace `elementor/v1`, base `variables`):

- `GET /list`
- `POST /create {type, label, value}`
- `POST /update {id, label, value, type}`
- `POST /delete {id}`
- `/restore`
- `/batch` (its body shape is not in the corpus; verify)

**Rules** (`Rest_Api` limits; a broken label silently drops the declaration):

- Labels are ASCII kebab-case, non-empty, ≤ 50 characters and unique: `color-*`, `font-*`, `space-*`, `radius-*`. Labels are only passed through `sanitize_text_field`, so `Primary Color` yields `--Primary Color:`, which is invalid CSS and drops the whole declaration.
- Values are non-empty and ≤ 512 characters. **Never use a bare `0`**: PHP treats `'0'` as empty and the variable is not emitted. Write `0px`.
- **Never rename a label after the first production deploy.** Every `var(--old)` reference in classes and child-theme CSS breaks. Create a new variable and migrate.
- Keep `tokens.json` in the child theme as the single source of truth.
- Check that every `var(--x)` in theme CSS is defined in post-{kit}.css.
- `clamp()` strings as Size values: acceptance on 4.3.x is unconfirmed (**verify** on the site). The fallback is the class's `custom_css`.
- Pro v3 widgets (Nav Menu, Form, Loop) still read kit Global Colors/Fonts. Mirror the colours and fonts 1:1.

### How Global Classes render

(source: `modules/atomic-widgets/styles/style-states.php`, `modules/global-classes/atomic-global-styles.php`, 4.3.5)

| Aspect | Behaviour |
|---|---|
| Selector | `.elementor .<label>`, specificity 0,2,0. The HTML carries `class="card"` (the label, not the id) |
| Which classes ship | Only classes used on the page, its header/footer and its Loop items are emitted, into that page's `global-<post>-frontend*.css` files. Unused classes cost 0 bytes |
| Breakpoints | One CSS file per breakpoint, each `<link>` with its own `media` attribute |
| Order and ties | Class Manager order decides ties between classes. A local style always wins |
| Limits | 1,000 classes per site (`global_classes_limit_exceeded`); labels ≤ 50 characters; duplicate labels get a `DUP_` prefix; keep ≤ 30 per site |
| Renaming | Renaming a label changes the public class name and breaks theme selectors |
| Storage | Kit post meta `_elementor_global_classes` (`items` + `order`) |
| REST | `GET/PUT /wp-json/elementor/v1/global-classes` (PUT body `items`, `order`, `changes{added,deleted,modified,order}`), plus `/post`, `/styles`, `/usage` |

**Variant shape.** A Global Class item uses the same `variants[]` structure as an element's local style
(design-to-elementor §5). Before writing with PUT, GET one class created in the editor and copy its exact
item shape (**verify**).

```json
{ "id": "g-card", "type": "class", "label": "card",
  "variants": [
    { "meta": { "breakpoint": "desktop", "state": null },
      "props": { "display": "flex", "flex-direction": "column", "border-radius": "…", "background": "…" },
      "custom_css": { "raw": "…" } },
    { "meta": { "breakpoint": "desktop", "state": "hover" }, "props": { "transform": "…" } },
    { "meta": { "breakpoint": "mobile",  "state": null },    "props": { "padding": "…" } }
  ] }
```

**Typed values.** Typed prop values (size, colour, transform) need their `$$type` envelopes. Get them
from `elementor-get-style-schema`, or from the write response's `unknown_style_properties` and inline
schema. Read the breakpoint ids (`mobile`, `tablet`, …) from the site's kit (**verify**).

**`custom_css`.** A variant's `custom_css.raw` is decoded and appended **inside** the same rule block, so
it is scoped to that class, state and breakpoint. It is stored encoded; copy the encoding from an
editor-made class (**verify**).

### Style properties the schema accepts

From `style-schema.php` 4.3.5, plus `clip-path`, `content` and `appearance` per the critic. These cover
the whole "sophisticated CSS without code" surface. For anything not listed, use `custom_css`.

| Group | Properties |
|---|---|
| Size | `width`, `height`, `min-width`, `min-height`, `max-width`, `max-height`, `overflow`, `aspect-ratio`, `object-fit`, `object-position` |
| Position | `position` (static / relative / absolute / fixed / sticky), `inset-block-start`, `inset-inline-end`, `inset-block-end`, `inset-inline-start`, `z-index`, `scroll-margin-top` (= Anchor offset) |
| Typography | `font-family`, `font-weight`, `font-size`, `color`, `letter-spacing`, `word-spacing`, `column-count`, `column-gap`, `line-height`, `text-align`, `font-style`, `text-decoration`, `text-transform`, `direction`, `stroke`, `all`, `cursor` |
| Spacing | `padding`, `margin` (`block-start/block-end/inline-start/inline-end`) |
| Border | `border-radius`, `border-width`, `border-color`, `border-style`, … |
| Layout | `grid-template-columns`, `grid-template-rows`, `grid-auto-flow` (row / column / row dense / column dense), `grid-auto-rows`, `grid-auto-columns`, `grid-column`, `grid-row`, `order`, `align-self` |
| Effects | `transform`, `transition`, `filter`, `backdrop-filter`, `mix-blend-mode`, background clipping, `clip-path` |

`text-wrap` and `font-variant-numeric` are not in the list, so they go in `custom_css`. The full list was
cut from the corpus; **verify** with `elementor-get-style-schema`.

### Where each kind of CSS lives

| Layer | Lands in | Selector / specificity | Use for |
|---|---|---|---|
| Variables | kit `post-{kit}.css` | `:root{--label:value}` | tokens |
| Global Class | `global-<post>-frontend[-<bp>].css` | `.elementor .<label>` (0,2,0) | every reusable component, its states and breakpoints |
| Local style | `local-<post>-frontend[-<bp>].css` | `.elementor .s-<id>` (0,2,0), wins over classes | genuine one-offs only |
| Variant `custom_css` | inside the class or local rule | same as its rule | properties the schema lacks (`text-wrap`, `font-variant-numeric`, `mask-*`, `clamp()` sizes) |
| Kit Custom CSS (Site Settings) | kit CSS | as written; the `selector` keyword is **not** replaced here (that rule is silently dead) | small site-wide rules with no extra file |
| Child-theme CSS (`site.css`) | its own file | as written; to beat a class match `.elementor .<label>` and load after Elementor, or add one class (0,3,0); never `!important` | `@font-face`, `@keyframes`, `@supports`, `@view-transition`, `@media (hover:hover)` gates, the global `:focus-visible` ring, `scroll-padding-top`, the reduced-motion block, non-Elementor DOM |
| Pro Custom Code | inline at `wp_head` / `wp_body_open` / `wp_footer` | — | third-party and facade **scripts** only, never site-wide CSS (§8) |
| Sandbox PHP | `wp-content/novamira-sandbox/*.php` | — | hooks, image sizes, enqueues, JSON-LD, Polylang conditions (§9) |

A child-theme stylesheet is one more CSS request. Confirm that `render-blocking-insight` stays at 0 items
through the cache plugin's critical CSS. Otherwise move the rules to kit Custom CSS.

## 2. Image treatments and their cost

**The LCP image stays a plain, unmasked, unfiltered `<img>`.** Treatments go on images below it.
Wherever possible, bake the treatment into the exported file.

| Treatment | How | Cost | Where allowed |
|---|---|---|---|
| Aspect ratio + crop | class `img-cover`: `aspect-ratio: 4/3` (or `3/2`); `object-fit: cover`; `object-position: center`; `width: 100%` | none; reserves space (no CLS) | everywhere, including the hero |
| Loading placeholder | class `img-cover`: `background-color: var(--color-surface)` (the next muted token if the band is already that colour), so a slow image shows a calm block in its reserved box, not a white hole | none. Never a BlurHash/LQIP script or a blurred data-URI preview: both add bytes or JS for a few hundred ms of effect | everywhere |
| Duotone | `e-image` with class `img-duotone` (`filter: grayscale(1) contrast(1.05)`; `mix-blend-mode: multiply`, or `screen` on dark) inside an `e-div-block` with `background: var(--color-brand)` | blend and filter create a stacking context and composite the backdrop; paint scales with area | cards and small layers below the fold; never the hero. For large photos, export the duotone into the WebP |
| Mask (v3) | Advanced → Mask: toggle; Shape Circle / Flower / Sketch / Triangle / Blob / Hexagon / Custom (PNG or SVG); Fit: Fit / Fill / Custom; Position (9); Repeat No Repeat / Repeat / Repeat-X / Repeat-Y / Round / Space (Elementor help, 28 Sep 2025). Uses CSS `mask-image` (Baseline since Dec 2023) | a custom SVG is one more request before the image paints | below the fold |
| Mask (V4) | prefer `clip-path: inset()/polygon()` (schema prop). Otherwise class `custom_css`: `mask-image:url("data:image/svg+xml,…"); mask-size:contain; mask-repeat:no-repeat; mask-position:center` with the SVG inlined at ≤ 2 KB | data URI = no request | below the fold |
| Blend mode | V4 Blend Mode control = `mix-blend-mode`, 16 values | compositing | small decorative layers only |
| Filters / backdrop filters | V4 Filters (blur, brightness, contrast, hue-rotate, saturate, grayscale, invert, sepia, drop-shadow) and Backdrop Filters | blur is the most expensive paint; `backdrop-filter` repaints on every scroll frame under it | `backdrop-filter` only on elements < 100 px tall (sticky header, pill); larger glass only at `@media (min-width:1025px)`; never animate blur or box-shadow across many cards |
| Gradient text | class `heading-gradient`: `background: linear-gradient(90deg,var(--color-brand),var(--color-accent)); -webkit-background-clip:text; background-clip:text; color: transparent` (V4 Background clipping → Text) | none | headings below the hero; check contrast against both gradient ends |
| Inset outline | `outline: 1px solid rgba(0,0,0,.1); outline-offset: -1px` on images | none | everywhere |

To verify:

- `largest-contentful-paint-element` is still the plain hero `<img>` or the h1.
- No new mask or SVG requests appear.
- DevTools Rendering → Paint flashing while scrolling the first two viewports shows no full-viewport flashes.

Sources: `modules/atomic-widgets/styles/style-schema.php` (4.3.5), [aspect-ratio](https://developer.mozilla.org/en-US/docs/Web/CSS/aspect-ratio).

## 3. Fluid type and variable fonts

**Fluid sizes.** `clamp()` (Baseline 2020) replaces media-query type steps. The scale is in
design-direction.md §1. Keep a `rem` term in the middle value, so that 200 % zoom still enlarges the
text. If the Size control or Size Variable rejects the string, put it in the class:

```text
heading-display  props: font-family: var(--font-heading); font-weight: 650; line-height: 1.05; letter-spacing: -0.02em
                 custom_css: font-size: clamp(2rem, 1.2rem + 3.2vw, 4.5rem); text-wrap: balance;
body             custom_css: font-size: var(--step-0); max-width: 70ch; text-wrap: pretty;
price            custom_css: font-variant-numeric: tabular-nums;
```

**Variable fonts.** Pro Custom Fonts: Elementor → Editor → Custom elements → Add New font → Add
Variable Font. It asks for a **TTF** file, with Width and/or Weight checkboxes and min/max values
([variable fonts](https://elementor.com/help/use-variable-fonts/)). That conflicts with the woff2-only
rule: a Latin+Greek variable TTF is often 600 KB or more. Use one of two routes:

1. **Static woff2 uploads**, one per weight, through Add Static Font (design-to-elementor §1).
2. **A child-theme `@font-face` for a subset variable woff2**, preloading only the heading file:

```css
@font-face{font-family:'Inter';src:url(/wp-content/themes/CHILD/fonts/inter-var-latin-greek.woff2) format('woff2');
  font-weight:100 900;font-display:swap;
  unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+2000-206F,U+20AC,U+0370-03FF}
```

With route 2, check that the Font Variable picker accepts a family declared only in theme CSS, and that
Elementor does not try to enqueue it from Google (**verify**). Google Fonts must be Disabled.

To verify:

- ≤ 4 same-origin woff2 files and no `.ttf`.
- The computed h1 size at 390 px and at 1440 px sits inside the clamp range.
- `document.fonts.check('16px Inter','Ωμέγα')` returns true.

## 4. Layout techniques

All of these are CSS. V4 sticky positioning is CSS `position: sticky`, with no `jquery.sticky`. Depth
stays ≤ 4.

**Section pattern** (band + boxed):

- `e-div-block.section-full`: background and `padding-block: var(--space-section)`.
- It holds one `e-flexbox` or `e-grid` with class `section-boxed`: `width:100%; max-width: var(--container-max, 1200px); margin-inline:auto; padding-inline: clamp(16px, 4vw, 40px)`.

**Full-bleed inside a boxed parent** (rare; prefer the band pattern): class `bleed`, with `custom_css`
`width:100vw; margin-inline: calc(50% - 50vw)`. Verify there is no horizontal scroll at 390 px and at
1440 px with a classic scrollbar (`scrollWidth === innerWidth`).

**Asymmetric editorial grid** (V4 Grid: columns, rows, gaps and auto-flow in any unit including `fr`; on
a child, Grid Column / Grid Row span, Align Self & Order; dense auto-placement fills the holes):

- `e-grid`: `grid-template-columns: repeat(6, minmax(0,1fr)); grid-auto-flow: row dense`.
- Children: `grid-column: span 4` / `span 2`.
- Mobile variant: `grid-template-columns: 1fr`, with the spans reset to `span 1`.
- A Flexbox container can be switched to Grid in the Layout panel.

**Overlap:**

- Parent `position: relative`.
- The overlapping child `position: absolute` with `inset-block-end` / `inset-inline-start` and a `z-index`.
- Mobile variant `position: static`, so it stacks.
- Never overlap the LCP `<img>` with an element that has a blend mode or filter.

**Sticky stacking** (cards that stick and stack, or a sticky side column):

- `position: sticky; inset-block-start: var(--header-h)` on each card.
- **No ancestor may have `overflow: hidden`.** Elementor containers often have it for rounded corners, and it silently stops sticky. Use `overflow: clip` where clipping is needed (**verify** on the site).
- Keep stacked cards shorter than a 390 × 844 viewport, or switch stacking off in the mobile variant.
- Add class `no-cv` to these containers if `content-visibility` is used on the page.

**Anchors.** Set `scroll-margin-top` (Anchor offset) on anchored sections, and
`html{scroll-padding-top: var(--header-h)}` in the child theme.

To verify: screenshots at 390, 768, 1024 and 1440 match the design frame within 5 px (the kallyas parity
loop). DevTools Layout shows the grid.

## 5. Hover, focus and active states through class variants

**States** (`Style_States`): `hover`, `active`, `focus`, `focus-visible` and `checked`. A **hover**
variant renders as `.elementor .card:hover,.elementor .card:focus-visible{…}`, so keyboard focus gets the
same treatment for free. That only matters if the element is focusable, so make cards links or put a
real link inside them.

**Card lift** (class `card`):

- Base props: `transition: transform 300ms ease, box-shadow 300ms ease`.
- Hover variant: `transform: translateY(-4px)` and a `box-shadow` (from `--shadow-2`).

**Button** (`btn-primary`):

- Base: `min-height: 48px; min-width: 48px; padding: 12px 24px; transition: transform 150ms, background-color 150ms`.
- Active variant: `transform: scale(0.96)` (press feedback).
- Hover variant: a colour change that keeps 3:1 against the surroundings.

**Touch.** The hover variant is not wrapped in `@media (hover:hover)`, so a lift can "stick" after a tap
on touch screens. For movement effects, put the transform in the child theme instead (specificity
0,3,0):

```css
@media (hover:hover){ .elementor .card:hover{ transform: translateY(-4px); box-shadow: var(--shadow-2); } }
.elementor .card:focus-visible{ transform: translateY(-4px); }
:where(a,button,input,select,textarea,[tabindex]):focus-visible{ outline:2px solid var(--color-focus); outline-offset:2px; }
@media (prefers-reduced-motion: reduce){ *,*::before,*::after{ transition-duration:.01ms !important; animation-duration:.01ms !important; } }
:root{ accent-color: var(--color-brand); } /* native checkboxes, radios and range sliders in the brand colour */
.elementor :is(.btn-primary,.btn-ghost,.icon-btn){ -webkit-tap-highlight-color: transparent; } /* they have their own :active press */
.elementor .hit{ position: relative; }
.elementor .hit::before{ content:''; position:absolute; inset:-12px; } /* a 24 px icon gets a 48 px target */
```

**Hit area** (class `hit`: an empty Global Class so the label lands on the HTML, or Advanced → CSS
Classes on v3 widgets; the `::before` lives in the child theme because `custom_css` cannot reach a
pseudo-element). Use it on icons that must look
smaller than 48 px: the close ×, gallery arrows, social icons, a dense row of text links. Set `inset` to
`(48 px − visible size) / 2` negated. Neighbouring hit areas must not overlap, so keep icon centres
≥ 48 px apart. An ancestor with `overflow: hidden` or `clip` cuts the area off. Prefer real padding
(`icon-btn`) wherever the layout allows it.

**Lint:**

- `transition` may only animate `transform`, `opacity`, `filter`, `clip-path`, `translate`, `scale`, `rotate` (colour changes are fine for state).
- Never `transition: all`.
- Never animate `box-shadow` on many cards on phones.
- Anything a user can reverse mid-way (hover, press, open/close, toggles) uses `transition`, never `@keyframes`: a transition turns back from where it is, a keyframe animation jumps to its start.

Further motion belongs to `elementor-motion`.

## 6. Clean mega menu and off-canvas

**Default.** Use the Pro Nav Menu for ordinary desktop menus and the Menu widget's dropdown or Pro
Off-Canvas on mobile (design-to-elementor §2). Use the **Menu** (mega) widget only when the dropdowns
need content. It costs smartmenus (~6.9 KB) plus `widget-mega-menu.min.css` (2.7 KB, render-blocking).

**Menu widget** (needs the Flexbox Containers, Nested Elements and Menu experiments; activating it
replaces Nav Menu with the WordPress Menu and Menu widgets) ([mega menu help](https://elementor.com/help/create-mega-menu/)):

- **Per item:** Title, Link, the **Dropdown Content** toggle (opens a container for any widgets; a Menu widget cannot be nested), Icon, CSS ID.
- **Layout:**
  - Content Width: Full Width | Fit To Content (applies to the whole menu).
  - Item Layout: Horizontal | Dropdown.
  - Open On: Click | Hover.
  - Animation + Animation Duration: set **None**.
  - Menu Toggle icon and alignment.
  - Horizontal Scroll: Disable | Enable.
  - Breakpoint: None | Mobile Portrait (> 767 px) | Tablet Portrait (> 1024 px).
- **Dropdown content:** headings, text links and at most one `e-image` ≤ 40 KB. **Never** a Form, Google Map, Loop Grid or Lottie: they render server-side in the header of every page and are only hidden with CSS.
- **Keyboard:** rely on the built-in dropdown button with `aria-expanded`. Style `.e-n-menu-dropdown-icon:focus-visible`. Do **not** add a `:focus-within` hack. Open On Hover is acceptable only if Tab + Enter opens and Esc closes the dropdown; otherwise use Click.
- **Hover safe zone:** with Open On Hover, a diagonal mouse path from the item to the panel crosses empty space and the panel closes (the "safe triangle" problem). Keep the panel touching its trigger (no top margin or gap), or bridge the gap in child-theme CSS with a transparent `::before` on the panel, `bottom: 100%`, full width, as tall as the gap (**verify** the panel class in the rendered header, e.g. `.e-n-menu-content`). Panels with more than one column open on Click.
- **Current page:** WordPress prints `aria-current="page"` on the current menu link, and Nav Menu also adds `.elementor-item-active`. Style it in child-theme CSS with an underline or weight, never colour alone (1.4.1): `.elementor-nav-menu a[aria-current="page"]{ text-decoration: underline; text-underline-offset: .3em; }`. Check that the Menu (mega) widget prints it too (**verify**).
- **RUCSS/UCSS safelist:** `.e-active`, `.e-n-menu-dropdown-icon`, `[aria-expanded]`, `.e-n-menu-mobile`, `.e-n-menu-tablet`, `.e-n-menu-layout-dropdown`, plus the popup and off-canvas list in design-to-elementor §3 (the corpus list was cut; **verify** the remaining `.e-n-menu-*` classes in the rendered header).

**Off-Canvas** (the mobile drawer, inside the header, never a popup):

- Horizontal Position: right
- Width: 86 %
- Height: Fit to Content
- Entrance Animation: Fade In (or none) / Exit: Fade Out
- Prevent Closing on ESC: **No**
- Disable Page Scrolling: default
- Overlay: on, colour at 40 %
- Trigger: a real button in the header with an accessible name
- Scrolling: `overscroll-behavior: contain` in child-theme CSS on the drawer's scrolling element, so scrolling to the end of the menu never scrolls the page behind it (**verify** the element class)

Keep the Book button outside the drawer.

To verify:

- The header HTML is < 40 KB with a single `<nav>`.
- `render-blocking-insight` lists at most `widget-mega-menu.min.css`.
- Keyboard: Tab, Enter and Esc all work.
- The focused item is never under the sticky header.

## 7. Loop item templates

([loop help](https://elementor.com/help/loop-carousel/))

**One loop-item template per content type** (room, offer, post, product). Build it from containers plus
the `card` class. The image is an Image widget with a **registered size** (`card-2x`), never Custom or
Full. Add an h3, an excerpt, the price-from with `price`/`tabular-nums`, and a button. Write it through
`elementor-set-content` with `template_type: "loop-item"`.

**Loop Grid controls:**

- **Layout:**
  - Choose template type: Posts | Post Taxonomy | Products | Product Taxonomy.
  - Choose a template / Edit template.
  - Columns: 3 / 2 / 1 per device.
  - Items Per Page: 6–9.
  - Masonry: off.
  - Equal height: on.
  - Apply an alternate template, with position, Apply Once, Static item position and Column Span. Example: a wide feature card at Position 1 with Column Span 2 and Apply Once on; a promo card at static Position 4.
- **Query:** Source (Posts, Pages, Landing pages, Manually selected, The Current Query, Related), Include/Exclude, Order By, Order, Ignore Sticky Posts, Query ID.
- **Pagination:** **Load on Click** (Page Limit 5). Not Infinite Scroll. AJAX load only for the Numbers styles. Turn Individual Pagination on when two grids share a page.

**Never** use Loop Carousel above the fold, and never put `_animation` on the Loop Grid or its items (the
grid stays invisible until JS runs). Give the grid an **empty state** (No-results message) in both
languages.

To verify: the grid's `<img>` tags carry width, height, `srcset` and a registered-size URL, none
`-scaled`. CLS ≤ 0.05 after clicking Load More.

**Pro Components** (V4) suit repeated *static* sections such as a CTA band, a testimonial or a stat:

- Right-click a container → Create component.
- In Edit Mode, expose General-tab props (text, media, link, HTML tag).
- Instances cannot override styles; styling stays in the classes.
- They are atomic-only, Pro-only and admin-only.
- Use Loop Grid, not Components, for content that comes from a CPT.

## 8. Pro Custom Code: locations and conditions

([help](https://elementor.com/help/custom-code-pro/))

**Storage.** CPT `elementor_snippet` (capability `manage_options`, `show_in_rest` true), with these meta
keys:

| Meta key | Values |
|---|---|
| `_elementor_location` | `elementor_head` (default) → `wp_head`; `elementor_body_start` → `wp_body_open`; `elementor_body_end` → `wp_footer` priority 21, after `wp_print_footer_scripts` |
| `_elementor_priority` | 1–10, default 1; 1 prints first |
| `_elementor_code` | raw HTML (`wp_kses_post` for users without `unfiltered_html`) |
| `_elementor_extra_options` | includes `ensure_jquery` |

Each snippet is scoped by Theme Builder conditions and skipped in `?elementor-mode=safe`. Custom Code is
not a dependency-aware enqueue and has no device or consent gating.

**Policy:**

- One snippet per third party.
- Location **`</body>` – End**, Priority 5–10.
- Conditions: only the pages that need it. Exclude checkout, booking and landing pages.
- **Never** `ensure_jquery`.
- No site-wide CSS in `<style>` snippets: they cannot be cached and are re-sent on every page.
- The CMP loads directly, at Body Start.
- Desktop-only module loaders go at Body End, so the import map has already been printed, and use absolute URLs inside `import()`:

```html
<script type="module" data-no-optimize="1" nowprocket>
if (matchMedia('(min-width:1025px)').matches) import('/wp-content/themes/CHILD/js/showpiece.js');
</script>
```

Set conditions in the editor (Custom Code → Conditions) so Pro rebuilds its cache. Creating snippets
outside the editor is untested (**verify**).

To verify: view-source of a page outside the conditions contains no snippet text. On a target page the
snippet sits after the footer scripts. `third-parties-insight` shows ≤ 150 KB.

## 9. Editor limits and what goes to theme code

| Need | The editor cannot | Put it in |
|---|---|---|
| Variable woff2 with `unicode-range` | Custom Fonts' variable path wants TTF and has no unicode-range field | child-theme `@font-face` (§3) |
| `@keyframes`, `@supports`, `@view-transition`, `@media (hover:hover)` | class `custom_css` sits inside one rule block | child-theme CSS or kit Custom CSS |
| Global focus ring, `scroll-padding-top`, reduced-motion block | not element-scoped | child-theme CSS |
| Image sizes, `font_display` swap filter, AVIF quality, Polylang conditions, JSON-LD, script enqueues | PHP | sandbox plugin |
| Device-specific hero art direction | `e-image` emits no `<picture>` | HTML widget with `<picture>` (no `<script>`; design-to-elementor §3) |
| Third-party and facade scripts | no script in HTML widgets | Pro Custom Code Body End (§8) or a deferred enqueue |

**Sandbox plugin** (`wp-content/novamira-sandbox/agency-site.php`). Novamira refuses PHP outside this
directory:

```php
<?php
add_action('after_setup_theme', function () {
    add_image_size('hero-mobile', 780, 0);
    add_image_size('card-2x', 800, 0);
});
add_filter('image_size_names_choose', fn($s) => $s + ['hero-mobile' => 'Hero mobile', 'card-2x' => 'Card 2x']);
add_filter('elementor_pro/custom_fonts/font_display', fn() => 'swap', 10, 3);
add_action('elementor/frontend/after_enqueue_scripts', function () {
    wp_enqueue_script('site-ux', get_stylesheet_directory_uri() . '/assets/js/site-ux.js',
        ['elementor-frontend'], '1.0', ['strategy' => 'defer', 'in_footer' => true]);
});
```

Do not add the `hello_elementor_enqueue_style` → `__return_false` filter unless the child theme carries
its own reset.

**Crash recovery.** A fatal error in any sandbox file puts all sandbox plugins into safe mode. MCP keeps
working. Fix or delete the file, then delete `wp-content/novamira-sandbox/.crashed`.

To verify: `novamira/list-directory wp-content/novamira-sandbox` shows the file and no `.crashed`.
View-source shows `<script defer src=".../site-ux.js">` after the Elementor frontend.

Sources: [wp_enqueue_script](https://developer.wordpress.org/reference/functions/wp_enqueue_script/), [wp_enqueue_script_module](https://developer.wordpress.org/reference/functions/wp_enqueue_script_module/).

## 10. Doing it through Novamira

| Task | Route | Parameters and notes |
|---|---|---|
| Pages, headers, footers, Loop items | `novamira/elementor-set-content` | `post_id`, `content` (array; `[]` wipes the document), optional `template_type` (`wp-page`, `header`, `footer`, `single-post`, `loop-item`, …). Classes go on nodes as `settings.classes`; local styles as a flat `{css-prop: value}` map or the verbose `variants` shape. Unknown keys abort the write and return the schema. Contract in design-to-elementor §5 |
| Small edits | `elementor-add-element` (`post_id`, `tree`, `parent_id`, `position`), `elementor-edit-element`, `elementor-delete-element-style` | read the arguments with `get-ability-info` first |
| Global Classes | `novamira/elementor-create-global-class` (only when registered; takes the same flat styles map as element styles). Otherwise Elementor MCP `elementor/manage-classes` `{action:'create', label:'card', css:'display:flex;flex-direction:column;gap:var(--space-2);border-radius:var(--radius-card);…'}`, or REST `PUT /wp-json/elementor/v1/global-classes` | verify the argument names with `get-ability-info`; read back with `GET /wp-json/elementor/v1/global-classes` |
| Variables | no Novamira ability. Use Elementor MCP `elementor/manage-global-variable`, or REST `POST /wp-json/elementor/v1/variables/create {type, label, value}` | read back with `GET …/variables/list`; check `:root` in post-{kit}.css |
| v3 kit mirrors | `elementor-create-v3-color`, `elementor-create-v3-typography`; read with `elementor-list-v3-styles` | verify the arguments |
| Interactions (owned by `elementor-motion`) | `novamira/elementor-add-interaction` when registered, or the element's `interactions` array through `elementor-set-content` | shape below |
| Child-theme CSS/JS, fonts | `novamira/write-file` `{path, content, mode: "overwrite"\|"append", create_directories}`. Paths are relative to ABSPATH, e.g. `wp-content/themes/CHILD/assets/css/site.css` | small UTF-8 text only; woff2 and other binaries go through `novamira/create-upload-link` |
| PHP | `novamira/write-file` to `wp-content/novamira-sandbox/<name>.php` only | — |
| Template conditions | `get/set-template-conditions` exists; design-to-elementor says to set conditions in the editor so Pro rebuilds `elementor_pro_theme_builder_conditions` | verify that the ability rebuilds the cache |
| After every write | `novamira/elementor-clear-document-cache`, then `elementor-get-content` to read back the ids | — |

**Interaction item shape** (`Interaction_Item_Prop_Type`). This is for elementor-motion. Never use it in
the first top-level container or the header, never use trigger `load`, and keep to ≤ 2 per page:

```json
"interactions": [ { "$$type": "interaction-item", "value": {
  "trigger":   { "$$type": "string", "value": "scrollIn" },
  "animation": { "$$type": "animation-preset", "value": {
    "effect":    { "$$type": "string", "value": "fade" },
    "type":      { "$$type": "string", "value": "in" },
    "direction": { "$$type": "string", "value": "bottom" },
    "timing_config": { "$$type": "timing-config", "value": {
      "duration": { "$$type": "time-size", "value": { "size": 500, "unit": "ms" } },
      "delay":    { "$$type": "time-size", "value": { "size": 0,   "unit": "ms" } } } } } } } } ]
```

**Triggers:** `load` and `scrollIn` are free. `scrollOut`, `scrollOn`, `hover` and `click` are Pro.

**Effects:** fade, slide and scale are free; custom is Pro.

**Breakpoint exclusion.** The plugin wants `breakpoints.excluded: ['mobile']` by default. The exact key
path is not in the corpus; read it from `elementor-get-schema` (**verify**).

**Stale cache after a write.** The frontend reads postmeta `elementor-interactions-cache`, which is
rebuilt only on `elementor/document/after_save`. After changing `interactions` through
`elementor-set-content`:

1. Delete that meta.
2. Clear the document cache.
3. Compare `#elementor-interactions-data` on the live page with the tree.

Sources: [interactions help](https://elementor.com/help/interactions/), [atomic elements](https://developers.elementor.com/docs/data-structure/atomic-elements).
