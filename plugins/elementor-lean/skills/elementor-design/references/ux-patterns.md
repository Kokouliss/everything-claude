# UX patterns for hotels, villas, a water park and restaurants (Greek + English)

Each pattern is labelled **[E]** (evidence: research, a standard or the law, with its source) or **[T]**
(taste or house rule). Where the corpus has no evidence for a site type, the pattern is marked
**derive**: run `perception-first-design:solve` for it and record the R1–R5 requirements it produces.
How to build each piece in Elementor is in `elementor-implementation.md`. Widget choices and costs are
in `../../../references/design-to-elementor.md` §2.

## 1. Navigation

### Evidence [E]

From an NN/g study with 179 participants on 6 sites (2016) ([NN/g](https://www.nngroup.com/articles/centered-logos/), [Baymard](https://baymard.com/research-articles/new-research-travel-accommodations)):

**Desktop.** Hidden navigation was used in 27 % of tasks, against 48 % for visible navigation and 50 % for the "combo" pattern. Discoverability dropped by more than 20 % when navigation was hidden. Users took 5–7 s longer to reach it.

**Mobile.** The combo pattern was used in 86 % of tasks, hidden navigation in 57 %.

**Logo.** Getting home is about 6× harder with a centred logo than with one top-left.

**Sticky headers** (NN/g, 2021):

- opaque, contrasting with every section they pass over;
- small;
- no animation other than a shrink;
- never a "stalker" header that slides in late.

### Pattern

- **Desktop:** logo top-left, linking to the current language's home page. Main links visible. A Book button that is always visible. The language switcher.
- **Mobile:** a hamburger, plus a Book (or Call) button that stays **outside** the panel.
- **Header height:** ≤ 64 px on mobile [T].
- **Header styling:** no transparency effects on scroll and no entrance animation on any header element (plugin rule 13).
- **Dropdowns** open on click or Enter and close on Esc. Prices, CTAs and submenus are never hover-only, because hover fails on touch and keyboard (2.5.8 / 2.1.1).
- **Mega menu content:** headings, text links and at most one small image. Never a form, map, Loop Grid or Lottie (elementor-implementation.md §6).
- **Footer:** NAP (name, address, phone as a `tel:` link), the licence/registration number (owner-confirmed until the agency's lawyer confirms Law 4276/2014 as amended), languages and legal links. Text, links and SVG only.

### IA per site type

| Site | Top level | Basis |
|---|---|---|
| Hotel | Home › Rooms › Offers › Experiences/Facilities › Location › Gallery › Contact/Book | [E] Baymard boutique-hotel behaviour |
| Villa | Home › Villa(s) › Location › Gallery › Rates & availability › Enquire | [T]; derive |
| Water park | Home › Tickets › Attractions › Plan your visit (hours, food, getting there) › Shop › Contact | derive |
| Restaurant | Home › Menu › Reserve › About/Team › Location & hours › Contact | derive |

**How boutique-hotel visitors behave** [E] (Baymard, 992 h of testing):

1. They scroll the home page to get a feel for the place.
2. Then they hunt for the gallery, the amenities, the room details (bathroom included), the offers, the map and "what makes it unique".

When room photos and bathroom information were missing, one user gave up and phoned. On Hilton, a hero
promo that led away from the booking path with no way back lost users [E]
([MeasuringU](https://measuringu.com/hotel-ux/)).

## 2. Booking and enquiry flows

### Rules for every site type

**Book CTA in view** [E] ([MeasuringU](https://measuringu.com/hotel-ux/), [Baymard](https://baymard.com/research-articles/new-research-travel-accommodations))

The booking path is visible in the first mobile viewport. In MeasuringU's benchmark of 405 + 160
participants, the one concrete failure was a Book button below the fold with an "Update" button above it.

**Static search form in the hero**

The form is the second element in the hero. It holds:

- two date inputs and a guests select;
- visible labels;
- native controls only (`input type="date"`, `number`, `select`);
- the `form-field` class: 16 px text, 48 px height, a border at ≥ 3:1.

Defaults:

- check-in today or the next available date, plus 2 or 7 nights;
- 2 guests;
- past dates disabled with `min` = today.

Unmodified native controls are exempt from 2.5.8, and they ship zero picker JS in the first screen [E]
([2.5.8](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html)).

**Engine behind a facade**

The vendor engine loads on `focusin` **and** `pointerdown` (`{once: true}`), or opens on `/book`.

- Its loader token is excluded from Delay-JS. Otherwise the first tap does nothing, which is the most common booking breakage on delay-JS sites.
- Never embed the engine iframe or script in the hero. That adds 300–900 KB of JS, it often becomes the LCP, and it shifts the layout.
- Booking/checkout page: Elementor Canvas or a minimal header (logo and phone), with no popups (design-to-elementor §3).

**Calendar (vendor engine)** [E] ([Baymard date picker](https://baymard.com/ecommerce-design-examples/date-picker))

- Show availability and the minimum nightly rate in the calendar ("a Monday night is cheaper").
- Mark unavailable dates clearly.
- Two months side by side on desktop, one on mobile.
- Always allow typing the date.
- Spell out month names (10/11 is ambiguous for an international audience).
- No drum pickers for far dates.
- Day cells ≥ 24 px, ideally 44 px or more.
- Prices visible without hover.
- The calendar's locale matches `html[lang]`.

### Price transparency [E]

**EU.** Consumer law (UCPD Art. 7(4)(c)) already requires the displayed price to include taxes and
unavoidable charges.

**US guests.** The FTC junk-fee rule (16 CFR 464, effective 12 May 2025) adds the same for short-term
lodging sold to US consumers.

**Enforcement.** European consumer authorities obtained commitments from Booking.com and Expedia (ACM/CPC,
Dec 2020): no artificial scarcity, genuine deals, and no price presented as a discount unless it is one
([ACM](https://www.acm.nl/en/publications/booking-and-expedia-inform-consumers-more-clearly-about-their-offers-following-action-european-consumer-authorities)).

**Pattern:**

- Room and villa cards read "from €X / night, incl. taxes".
- Avoid several rate names for one room, each marked "Excluding Taxes & Fees". Baymard saw this make comparison impossible.
- Put the total in the first place a price appears, not at the last step.
- Use `tabular-nums` on every price.

### Trust signals

- **Real photography.** Users scrutinise it [E] (design-direction.md §3).
- **A "Why book direct" list** of 3 items at most, static [T].
- **A trust strip** [T]:
  - SVG icons;
  - the review count with a link to its source;
  - no review-widget or social-feed script, because these are third-party JS (plugin rule 6).
- **Contact and identity** in the footer [E]: phone, address and licence number.
- **The `<form>` stays static** until interaction.

### Urgency without dark patterns [E]

([EU UCPD](https://commission.europa.eu/law/law-topic/consumer-protection-law/unfair-commercial-practices-law/unfair-commercial-practices-directive_en), [ACM](https://www.acm.nl/en/publications/booking-and-expedia-inform-consumers-more-clearly-about-their-offers-following-action-european-consumer-authorities))

**Background:**

- Which? (Sept 2019) found 5 of 10 "only X rooms left" prompts on Booking.com were false. The CMA held that false popularity claims, and not showing the full cost up front, can break consumer law. 25 companies agreed to change their displays.
- The Commission's UCPD Guidance (17 Dec 2021) explicitly covers dark patterns.

**Allowed:** urgency copy only through dynamic tags bound to the engine's availability, and only after
the guest has chosen dates.

**Never:**

- countdown timers;
- "X people viewing";
- hard-coded "only N left";
- a "%-off" not bound to data.

**Prior prices.** The 30-day prior-price rule (Omnibus, from 28 May 2022) applies to goods: the water
park's shop merchandise, and arguably tickets sold as products. It does not apply to room rates. A
room-rate strike-through is still judged under the UCPD test for misleading practices, so keep a dated
record of the real prior rate.

**Audit grep** for urgency strings without a dynamic source:
`left|viewing|hurry|ends in|μόνο .* δωμάτι|απομένουν`.

### Per site type

**Hotel**

- Search: the hero form above. Results and checkout happen in the engine.
- Room page [E Baymard]: name, size in m², bed type, maximum guests, bathroom, view, price-from incl. taxes, 6–12 photos, an amenities list with SVG icons, and a "Check availability" CTA. Build it as one Loop item / single template.

**Villa**

- An enquiry or request form is the usual direct channel [E]. A broken or ugly form is a lost booking.
- Fields [T; derive]: dates, guests, name, email, phone (optional), message.
- Show the price-from incl. taxes on the villa page and the rate table.
- The enquiry form must stay short. A 7-field form above the fold on mobile is the anti-pattern pfd warns about.

**Water park tickets** (derive)

- Date (native `input type="date"`, `min` = today), then ticket types with quantity steppers that have 48 px targets, then a running all-in total, then checkout.
- Checkout: Canvas or a minimal header. No Menu Cart in the header: its `get_refreshed_fragments` XHR took 1.5–5 s (design-to-elementor §3, unreviewed).
- Shop merchandise is covered by the prior-price rule.

**Restaurant reservation** (derive)

- Date, time and party size, with native inputs, as a static form. Load the vendor widget on focus.
- Show opening hours next to it. Publish them as `openingHoursSpecification` in the Restaurant JSON-LD.

**EAA scope** [E] ([Art. 2](https://www.legislation.gov.uk/eudr/2019/882/article/2), [Art. 3](https://www.legislation.gov.uk/eudr/2019/882/article/3))

- Any flow that leads to a consumer contract (booking, tickets, a paid reservation) is an e-commerce service under the European Accessibility Act from 28 June 2025.
- A brochure site with no contracting is arguably out of scope.
- Exempt: microenterprises providing services, meaning fewer than 10 staff **and** either turnover or balance sheet ≤ €2 M.
- No transition period applies to new websites.
- Record the intake answers. Apply WCAG 2.2 AA anyway.
- Publish an accessibility statement when the site is in scope.

## 3. Forms and error states [E]

([Elementor form fields](https://elementor.com/help/form-fields/), [Form widget](https://elementor.com/help/form-widget/); web.dev form best practices; NN/g error-message guidelines 2023)

### Labels and input types

- Use `<form>`, `<label for>` and `<button>`. Give each input one visible label.
- Never use a placeholder as the label or as the format hint (fails 1.3.1 and 3.3.2).
- Use the right input type:

| Input | Type |
|---|---|
| email | `type=email` |
| phone | `type=tel` (never `type=number`) |
| numbers that are not quantities | `inputmode=numeric` |
| known dates such as a date of birth | three inputs with `inputmode=numeric` (the GOV.UK pattern); not for check-in |

- Set `autocomplete` on every field: `name`, `email`, `tel`, `bday-day/month/year`, `address-line1`, `postal-code`, `country`. In Elementor Form this goes under Advanced → Custom attributes for each field (verify the field-level path).

### Errors

- Show the error inline, next to the field.
- Make it noticeable and redundant: text, colour **and** an icon, never colour alone (1.4.1; about 350 M people have a colour-vision deficiency).
- Write it in plain language with no codes, and suggest a fix (3.3.3).
- Keep what the user typed.
- Write custom messages in Greek and English.
- Announce the success state through a status region (4.1.3).

### Spam protection

- Use a honeypot. Load reCAPTCHA on focus only, never twice (plugin rule 7).
- Do not put a form in the footer template: it ships on every page (design-to-elementor §2).

### Avoid re-asking

Don't ask twice for anything the guest already entered (3.3.7 Redundant Entry). Carry the dates from the
hero form into the engine or the enquiry form.

## 4. Mobile behaviour and tap targets

| Rule | Value | Label |
|---|---|---|
| First screen | h1 + Book CTA inside 390 × 844 | [E] NN/g, MeasuringU |
| Tap targets | WCAG minimum 24 × 24 CSS px; house rule 48 × 48 with 8 px gaps | [E] ([target-size](https://dequeuniversity.com/rules/axe/4.10/target-size)); 48 px is the web.dev/Material recommendation, about 9 mm |
| Inputs | font-size 16 px, min-height 48 px; `appearance` not overridden on date inputs | [E] |
| Reflow | no horizontal scroll at 320 CSS px (`scrollWidth <= innerWidth`) | [E] 1.4.10 |
| One DOM | responsive style variants, never hidden desktop/mobile twins | [E] design-to-elementor §3 |
| Sticky header and focus | `html{scroll-padding-top: var(--header-h)}` so focused and anchored elements are not hidden (2.4.11) | [E] |
| Phone | `tel:` link in the header or bottom bar | [T] |
| Bottom Book/Call bar | optional: CSS only, opaque, ≤ 56 px, 2 targets, hidden on /book and while the hero CTA is in view; `env(safe-area-inset-bottom)` only with `viewport-fit=cover`. The lift figures (7–12 %) come from unaudited vendor posts; never quote them | [T] |
| Cards | single column on mobile; section padding 48–64 px | [T] |

Lighthouse 12+ reports the axe `target-size` audit at 24 px only. Enforce 48 px with a Playwright check.
The audits `tap-targets`, `font-size`, `third-party-facades` and `offscreen-images` no longer exist in
Lighthouse 13 ([changelog](https://raw.githubusercontent.com/GoogleChrome/lighthouse/main/changelog.md)).

## 5. Content architecture

- **Order of a page** [T]: thesis (hero) → booking entry → proof (rooms or attractions with real photos) → what makes it unique → offers → location → reviews link → contact. 57 % of attention sits above the fold and 74 % within the first two screens [E], so the hero and the section below it carry the page.
- **Location:** a static map image (≤ 150 KB) linking to Google Maps, or a click-to-load facade. Load the Maps JS only where a map is shown, and never with an empty `key=` (plugin rule 7).
- **Structured data** [E] ([local business](https://developers.google.com/search/docs/appearance/structured-data/local-business)):
  - Generate JSON-LD from a mu-plugin (sandbox plugin) that reads ACF/options.
  - Hotel / LodgingBusiness: `checkinTime`, `checkoutTime`, `address`, `geo`, `telephone`, `image`.
  - Restaurant: also `priceRange` and `openingHoursSpecification`.
  - The gain is knowledge-panel and Maps details and consistent NAP, not rich results with prices.
  - Check with the Rich Results Test: 0 errors.
- **Every content image has alt text** in the page language. Decorative images have empty alt.

## 6. WCAG 2.2 AA checklist with numbers

Criteria are from [WCAG 2.2](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html).
Lighthouse 13 audit ids: `color-contrast` and `target-size`. axe-core rules: `color-contrast`,
`target-size`, `label`, `image-alt`, `aria-*`. Do not install an accessibility overlay (Ally widget,
accessiBe, UserWay). They add 100–300 KB of JS and a floating button that can become an LCP candidate.

| SC (level) | Requirement | Number | Elementor implementation |
|---|---|---|---|
| 1.1.1 (A) | Text alternatives | alt on every content image | Media library alt; `e-image` alt |
| 1.3.1 (A) / 3.3.2 (A) | Labels, structure | visible `<label>` per field | Form: labels shown; never placeholder-only |
| 1.4.1 (A) | Not colour alone | errors = text + colour + icon | `form-error` class |
| 1.4.3 (AA) | Text contrast | 4.5:1 body; 3:1 at ≥ 24 px regular / 18.66 px bold | Contrast script on the Variables |
| 1.4.4 (AA) | Resize text | 200 % zoom enlarges text | `clamp()` with a `rem` term |
| 1.4.10 (AA) | Reflow | 320 CSS px, no 2-D scroll | Mobile variants; no fixed widths |
| 1.4.11 (AA) | Non-text contrast | 3:1 borders, icons, focus, button edges | `color-border`, `color-focus` |
| 2.1.1 / 2.1.2 (A) | Keyboard, no trap | every control reachable; modals and off-canvas release focus | Menu dropdown button; Off-Canvas Esc on |
| 2.2.2 (A) | Pause, stop, hide | anything auto-moving for > 5 s alongside other content needs a control | No auto carousels; marquee with a pause button (elementor-motion) |
| 2.4.7 (AA) | Focus visible | house rule: 2 px ring, ≥ 3:1 | Global `:focus-visible` in child-theme CSS |
| 2.4.11 (AA) | Focus not obscured | the focused element is not hidden by the sticky header or bar | `scroll-padding-top`; bar hidden while it would cover |
| 2.5.7 (AA) | Dragging | single-pointer alternative for sliders and galleries | Arrows/buttons on any carousel below the fold |
| 2.5.8 (AA) | Target size | ≥ 24 × 24 px (house rule 48) | `btn-*`, `icon-btn` min 48 px |
| 3.1.1 (A) / 3.1.2 (AA) | Language | `html[lang]` per page; `lang` on other-language parts | Polylang; switcher items `lang="el"` / `lang="en"` |
| 3.3.1 (A) / 3.3.3 (AA) | Errors identified with suggestions | inline, plain language | Form custom messages EL/EN |
| 3.3.7 (A) | Redundant entry | do not re-ask entered data | Carry dates into engine/enquiry |
| 4.1.2 (A) | Name, role, value | no div-buttons; `aria-expanded` on toggles | Native Button/Menu widgets |
| 4.1.3 (AA) | Status messages | form success/error announced | Form messages in a live region (verify the widget's output) |

**Beyond AA** [E, AAA]: 2.3.3 reduced motion. Elementor Interactions do not honour it, so
`elementor-motion` adds the gate. It is a house requirement.

**Testing:**

- Lighthouse accessibility ≥ 95 on the home, room, offer, contact and booking pages.
- axe: 0 violations.
- A manual Tab pass with the ring visible throughout.
- VoiceOver/NVDA spot check (the method of `design:accessibility-review`).

## 7. Multilingual (Greek + English) UX

- **`lang` attributes** [E] ([MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/text-transform)):
  - Every page emits `<html lang="el">` or `"en"` (Polylang does this).
  - Any element whose text is in the other language carries its own `lang`.
  - Without them, screen readers read Greek with an English voice and the uppercase rules do not apply.
- **Greek capitals:** type them in the content. Never use `text-transform: uppercase` on Greek text, because Safari keeps the tonos and Android drops it.
- **One header and footer per language** with Polylang conditions (design-to-elementor §3). Add `hreflang` through Polylang.
- **Lengths:** design every component with the Greek strings.
  - Check button text, menu items at 1024 px and card titles at 390 px for clipping.
  - Run `design:ux-copy` in both languages and apply its localisation notes.
- **Dates and forms:**
  - spelled-out month names;
  - form labels and errors in the page language;
  - the vendor calendar's locale matching `html[lang]`;
  - `lang` set on the form.
- **Fonts:** both families must have Greek glyphs. Check that ά, ώ and ς render in the brand font, not a fallback (design-direction.md §1).
- **Token names stay ASCII** (`color-brand`, not `Κύριο Χρώμα`). Greek or spaced Variable labels produce invalid or ungreppable CSS custom properties (elementor-implementation.md §1).
- **Domains:** if Polylang uses one domain per language, switching language is cross-origin. Cross-document view transitions then do nothing, which is harmless.
- **Switcher:** every template, the header on mobile included, outside the hamburger if space allows [T]. Each item is labelled with the language name in that language ("Ελληνικά", "English"), not a flag [T].
