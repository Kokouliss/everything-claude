---
name: elementor-design
description: Designs Elementor 4.x sites (hotels, villas, a water park with tickets, restaurants; Greek and English) that look premium and have a complete UX, using V4 Variables and Global Classes and keeping the 95+ mobile PageSpeed budget. Use when asked to design a site, make it beautiful, premium or sophisticated, build a design system, work from Figma / XD / a moodboard / a reference, improve UX, or design a booking flow or landing page.
---

# Designing sophisticated Elementor sites within the budget

This skill decides how the site should look and work. `elementor-build` decides how it is built lean,
`elementor-motion` adds motion afterwards, and the `elementor-ux-reviewer` agent checks the result.
The local design skills give advice. When one of them conflicts with the gate below, the gate wins.

## Read first

- `references/design-direction.md`: typography, colour, imagery, layout rhythm, static heroes, quality signals, reference sites.
- `references/ux-patterns.md`: navigation, booking and enquiry flows per site type, forms, mobile, content architecture, the WCAG 2.2 AA checklist, Greek/English UX.
- `references/elementor-implementation.md`: Variables and Global Classes as tokens, where CSS lives, image treatments, layouts, states, menus, Loop items, Custom Code, theme code, Novamira calls.
- `../../references/design-to-elementor.md`: token extraction from Figma/XD/screenshots (§1), component-to-widget mapping (§2), structure (§3), image/font/script budget (§4), JSON shapes (§5), the per-page gate (§7). This skill does not repeat that file. Follow it.
- `../elementor-build/SKILL.md` and `../../references/sites-in-the-wild.md`, section "Rules the plugin adopts".

## Non-negotiables

These hold for every direction, every client and every skill you consult:

1. **Static hero.** The LCP is the h1 text or one `<img>` (`e-image`, registered size, `fetchpriority="high"`, never lazy, ≤ 100 KB AVIF/WebP). Never use a slider, video, container background image, Lottie, animated headline or 3D scene as the hero.
2. **Two Greek-capable font families**, ≤ 4 woff2 files, ~100 KB total, `font-display: swap`. Run the Greek glyph check before proposing any font. **Zero icon fonts**: icons are SVG.
3. **Containers ≤ 4 deep**, one DOM for all devices. No "hide on mobile" twins.
4. **No animation above the fold.** Nothing in the first top-level container or the header gets an entrance animation, an Interaction, a start state of `opacity:0`, `visibility:hidden` or `translate(≥100%)`, Page Transitions or a preloader.
5. **Third-party JS is deferred to interaction.** That covers the booking engine, maps, reCAPTCHA (on focus only), chat and pixels. Keep to one GTM container, ≤ 150 KB, ≤ 300 ms blocking.
6. **WCAG 2.2 AA.** Text contrast 4.5:1. Large text, UI parts and focus rings 3:1. Targets at least 24 × 24 CSS px, with 48 px as the house rule. Focus must be visible and never hidden under the sticky header. Reflow at 320 px. Every field has a visible label.
7. **Tokens only.** Use ≤ 8 colour Variables, 2 font Variables and one size scale. Colour and type come from Global Classes, never from a local override.
8. **Truthful commerce.** Show the all-in price (taxes and unavoidable charges included) where the price first appears. Show urgency or scarcity only when it is bound to live data. Show a discount only with a genuine, recorded prior price.
9. **The per-page gate** in design-to-elementor §7 must pass before the next page starts: median of 3 runs ≥ 95, LCP ≤ 2.0 s, CLS ≤ 0.05, TBT < 300 ms, 0 render-blocking resources.

## Process (in this order)

### 0. Check the site

Call `novamira/elementor-check-setup` and `mcp-adapter-discover-abilities` on the site. Record whether
`atomic.runtime_available`, `global_classes_available` and `variables_available` are true, and which of
`elementor-create-global-class`, `elementor-get-style-schema` and `elementor-add-interaction` exist.
On the agency's dev sites none of them were registered. If they are missing, either ask the owner to
turn on the Atomic Editor (staging first) or plan v3 kit globals plus child-theme CSS. See
elementor-implementation.md §0.

### 1. Brief and direction

1. Intake: site type, audiences, languages, conversion goal (book, enquire, buy tickets, reserve, call), booking engine and vendor, pages, the photography that actually exists, and the existing brand. For EU e-commerce, record three facts for the European Accessibility Act check: staff count, turnover and balance sheet (ux-patterns.md §6).
2. Set an impeccable mode for each template:
   - **Persuade**: home, landing pages, offers.
   - **Operate**: booking, tickets, checkout, account.
   - **Read**: blog, policies.
   - **Experience**: gallery.

   Operate surfaces get no decoration.
3. Get the direction:
   - **Brief only:** invoke `taste-skill:taste-skill` (tasteskill v2, also listed as design-taste-frontend) for its steps 1–5. Pass the brief, the imagery inventory, the brand to keep, and the sector (hospitality / restaurant / ecommerce / event / other). It deals three directions with `scripts/draw.mjs --options 3`. Skip its step 9 (motion).
   - **Figma or XD:** extract the tokens as in design-to-elementor §1.
   - **Screenshots:** invoke `taste-skill:image-to-code-skill` (image-to-code) and use only its extraction rules (text, typography, spacing, button, colour) to fill the token sheet. Ignore its HTML output.
   - **Live reference site:** invoke `anthropic-skills:extract-design-system <url>` and read `.extract-design-system/normalized.json`.
   - **Optional first token draft:** run `ui-ux-pro-max:ui-ux-pro-max` (`scripts/search.py "<sector>" --design-system --density 3 -p "<site>"`; check `--help` for the flags). It over-produces, so prune it in step 2.
4. Run the Greek glyph check on every font proposed (design-direction.md §1). If a font fails, swap it for one of the Greek-capable pairings.
5. Present **two directions** to the owner. For each, give the two families and the fluid scale, the palette with the ratio of every text/background pair, a hero sketch with the booking or enquiry entry inside the first 390 × 844 viewport, the imagery direction, and one or two zero-JS signature details. **STOP until the owner picks one.**

### 2. Tokens

Prune to ≤ 8 semantic colours, 2 families and 1 scale. Compute contrast for every pair with the script
in design-direction.md §2 and fix failures before anything is created. Name tokens in ASCII kebab-case
(`color-brand`, `font-heading`, `space-section`). Write them as V4 Variables and mirror them in the v3
kit Global Colors/Fonts, because Pro Nav Menu, Form and Loop still read the kit
(elementor-implementation.md §1).

### 3. System

Set Defaults for h1–h6, p, a and button, and set `div` padding to 0. Then create ≤ 30 Global Classes, for example:

- `heading-display`, `h2`, `h3`, `body`, `eyebrow`, `caption`, `price`
- `btn-primary`, `btn-ghost`, `icon-btn`, `link-underline`
- `card`, `card--media`, `img-cover`, `img-duotone`
- `section-full`, `section-boxed`
- `form-field`, `form-error`, `book-bar`

Give each class desktop, tablet and mobile variants and hover/focus-visible states. Put the polish rules
in the classes, not on widgets: `tabular-nums` on prices, `text-wrap: balance` on headings, concentric
radii and transform/opacity transitions only. Document each class (states, a11y, do/don't) in the
`design:design-system` document format.

### 4. Components

Map every component with design-to-elementor §2. Then:

- Build repeated static sections (CTA band, testimonial, stat) as Pro Components.
- Give each repeating content type (room, offer, post, product) one Loop item template.
- Build the header and footer once per language.
- Build the static booking or enquiry form from native inputs.
- Each component references only classes and Variables.

### 5. Pages

Work through the pages in this order: home, one inner page, the booking/tickets/reservation page, then the rest. For each page:

1. **Transactional templates** (room/villa booking, water-park tickets, restaurant reservation, sign-up): run `perception-first-design:solve` first. State the problem as the user's experience. Turn its requirements R1–R5 into the section spec: field count, choices at each decision point, where the price is shown, and the CTA wording.
2. **Section spec:** for every page, list the bands in order, the widget per band, the LCP element, and the UX pieces from the checklist below. Use `impeccable:impeccable` with `shape` to plan the UX before building.
3. **Copy:** invoke `design:ux-copy` for every template in both languages: CTAs, labels, errors, empty and success states. Apply its localisation notes to button widths at 390 px. Do not invent prices, reviews, awards or scarcity. Mark placeholders `TODO`.
4. **Build and gate:** build through the `elementor-build` sequence (draft → `elementor-set-content` → `elementor-clear-document-cache` → screenshots at 390/768/1440 → Lighthouse median of 3 → gate).

### 6. UX completeness pass

Run the following on the built and published static pages:

- **Checklist:** go through the "complete UX" checklist below and mark each item done or missing.
- **Perception:** run `perception-first-design:evaluate` on the home and booking URLs. Keep the flags it raises for each layer (L0–L4).
- **Accessibility:** run `design:accessibility-review` and use its tables (findings by principle, contrast, keyboard, screen reader) for the report. Take the pass/fail criteria from WCAG 2.2 (ux-patterns.md §6), not from its 2.1/44 px defaults.
- **Critique and polish:** run `impeccable:impeccable` with `critique`, then `polish` / `typeset` / `layout` / `harden` (Greek/English, edge cases) / `clarify`, on screenshots and the rendered CSS. Apply each fix as a Global Class or Variable change, never per widget.
- **Inspector:** run tasteskill's inspector:
  ```
  node <taste-skill>/scripts/inspect.mjs <url> --widths 1440,768,390 \
    --allow few-pictures,no-footage,still-page,scene-not-started,scene-not-live,many-scenes,scene-heavy,reduced-hidden --json
  ```
  Treat any FAIL that remains as a design finding. Never add motion or pictures to clear one.
- **Re-gate:** run Lighthouse again after any fix.

### 7. Motion hand-off

Hand each static page that passes the gate to `elementor-motion` (agent `elementor-motion-designer`) with:

- the sections that may move, below the first viewport only;
- the feeling you want;
- what must stay static;
- the motion lens: Jakub/Jhey below the fold, Emil for the header, nav, forms and booking.

Do not add Interactions, entrance animations or animation libraries yourself.

### 8. Review

Run the `elementor-ux-reviewer` agent on each page after motion. Fix the findings tagged `designer`, then re-run the gate.

## Rules from other skills that do not apply here

- tasteskill's hero "load sequence", masked-headline reveal, its step 9 and scenes, and the inspector's "fails under eight moving elements": motion goes below the fold through elementor-motion.
- Its picture floor (`few-pictures`): the image budget in design-to-elementor §4 wins.
- Any GSAP, Lenis, ScrollSmoother or CDN script a skill suggests: never on mobile before interaction. ScrollSmoother never.
- Any font a skill proposes without Greek glyphs.
- Any palette over 8 colours, or any type setting applied per widget.
- Glass panels: `backdrop-filter` only on the sticky header (under 100 px tall).
- Carousels or sliders in the first viewport.

Do not use these skills for Elementor sites:

| Skill | Reason |
|---|---|
| `ecc:liquid-glass-design` | iOS SwiftUI only |
| `ecc:taste`, `taste-application`, `taste-distillation` | video grading |
| `taste-skill:taste-skill-v1`, `taste-skill-v2-experimental` | perpetual-motion defaults |
| `ui-ux-pro-max:design` | logo/banner generation that needs a Gemini key |
| `ecc:motion-foundations`, `ecc:motion-patterns`, `ecc:motion-advanced` | React only |

## What "complete UX" means

Mark each item for every page. A page is not done while any item applies to it and is missing.

**Navigation**
- [ ] Logo top-left, links to the home page of the current language, exact width/height.
- [ ] Desktop: main links visible without hover. Mobile: hamburger plus a Book/Call button that stays visible outside the panel.
- [ ] Sticky header: opaque, ≤ 64 px on mobile, no animation except a shrink. `scroll-padding-top` set so focused and anchored elements are not covered.
- [ ] Dropdowns open on click/Enter and close on Esc. Nothing is hover-only. A hover panel survives a diagonal mouse move to it.
- [ ] The current page is marked in the menu (`aria-current="page"`) with an underline or weight, not colour alone.
- [ ] A skip link to the main content is the first focusable element and shows on focus (Hello ships one; check custom themes).
- [ ] Language switcher on every template. Each item carries its own `lang`.

**Conversion path**
- [ ] Book/enquire/tickets/reserve CTA inside the first 390 × 844 viewport. One primary CTA per view.
- [ ] Static search or enquiry form with native inputs, visible labels, 16 px text and 48 px height. The vendor engine loads on `focusin`/`pointerdown` and works on the first tap from a cold profile.
- [ ] Prices are all-in ("from €X / night, incl. taxes"). Rate names can be compared.
- [ ] Trust: a "why book direct" list (≤ 3 items), a review count with a link to its source, real photographs of the place and the people, contact details and licence number in the footer (owner-confirmed).
- [ ] No fake scarcity, countdowns, "X viewing" or unrecorded strike-through prices.
- [ ] The booking/checkout page uses Elementor Canvas or a minimal header (logo and phone). No popups on it.

**Content**
- [ ] Room/villa pages show size in m², bed type, maximum guests, bathroom, view, price-from, 6–12 photos, amenities as SVG icons and a "Check availability" CTA.
- [ ] Location: a static map image linking to Google Maps (≤ 150 KB). The map JS loads on the contact page only.
- [ ] Every image answers a guest question and has alt text in the page language. No text is baked into images.
- [ ] LocalBusiness / Hotel / Restaurant JSON-LD comes from the mu-plugin.

**States** (six content states; verify the list in `ecc:frontend-design-direction`)
- [ ] Form: inline error next to the field (text, colour and icon), the user's input kept, a success message, both languages.
- [ ] Form sending: the submit button is disabled and visibly busy, so a second tap cannot send twice.
- [ ] Loop Grid: empty state. Booking widget: loading, partial and no-availability states.
- [ ] 404 page in each language, with navigation and a search or home CTA.

**Mobile and accessibility**
- [ ] Targets ≥ 48 px with 8 px gaps, measured on the hit area: small icons use the `hit` class. No horizontal scroll at 320 px. Body text ≥ 16 px.
- [ ] The off-canvas menu has `overscroll-behavior: contain`, so the page behind it does not scroll.
- [ ] Contrast pairs pass (script output kept). The focus ring is 2 px at ≥ 3:1 on every interactive element.
- [ ] `<html lang>` matches the page. Greek capitals are typed as capitals, not made with `text-transform`.
- [ ] Optional bottom Book/Call bar: CSS only, opaque, ≤ 56 px, 2 targets, hidden on /book.
- [ ] Cookie banner is a fixed bottom sheet with small type and can never be the LCP. No accessibility overlay widget.

**Polish** (all CSS, 0 KB of JS; how-to in elementor-implementation.md §2, §3, §5)
- [ ] Prices use `tabular-nums`. Headings use `text-wrap: balance`, body text `text-wrap: pretty`.
- [ ] Images sit on a muted surface colour in their reserved box while loading. No BlurHash or LQIP script.
- [ ] Native checkboxes, radios and sliders take the brand colour through `accent-color`.
- [ ] Hover movement is wrapped in `@media (hover:hover)`, so it never sticks after a tap. Buttons have a press state.
- [ ] Hover, press and open/close use transitions, so a state reverses smoothly when the pointer leaves mid-way.

## Return

1. The approved direction and the date of the OK.
2. The token sheet with contrast results and the Greek check output.
3. The list of Global Classes and Components as created.
4. For each page: URL, LCP element, Lighthouse median, screenshot paths (390 / 1440) and the ticked UX checklist.
5. The motion hand-off note, the reviewer's findings, and the open `TODO`s.
