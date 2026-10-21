---
name: elementor-build
description: Build new Elementor pages and sites that score high on PageSpeed from day one. Use when creating or editing Elementor pages, templates, headers, footers or kits through Novamira, or when asked how to build a clean Elementor site.
---

# Building lean Elementor sites

These rules come from measuring the agency's own sites: the fast ones use few fonts, no
animations in the first screen, one cache plugin and almost no add-on packs. The slow ones have
3D or video heroes, four Tag Manager tags, 12 font weights and plugins doing the same job twice.

## Read first

- `references/design-to-elementor.md` (plugin root, two levels up): tokens from Figma/XD into the
  kit and global classes, the component-to-widget mapping with the heavy widgets to avoid, the
  `_elementor_data` JSON shape for building pages programmatically, what the local design skills
  (adobe-xd, kallyas-to-elementor, image-to-code, design-taste-frontend, the Figma connector)
  provide, and the handover procedure.
- `references/sites-in-the-wild.md`, section "Rules the plugin adopts": the budgets a new page must
  meet (third-party ≤ 150 KB, hero text or one image ≤ 100 KB, zero render-blocking files, ≤ 2 font
  families, zero icon-font files, no animation above the fold).

## Design and motion

This skill keeps a page lean; it does not make it beautiful. For the design direction, tokens,
typography, colour, imagery and the booking/enquiry UX, use `elementor-design` first (it hands you a
component plan and the Global Classes to build with). For animation and micro-interactions, build the
page static first, pass the gate below, then hand it to `elementor-motion`: motion is added after
the LCP, never on the LCP element or above the fold at load, and is measured again afterwards.

## Site setup (once per site)

1. Theme: the agency's `Inglelandi` child theme, or a `hello-elementor` child. Nothing heavier.
2. Elementor → Settings → Features: Flexbox Container **on**, Optimized Markup **on**, Inline Font Icons (SVG) **on**, Font Awesome 4 Support **off**, Element Cache **24h**. Google Fonts **off** once fonts are self-hosted.
3. Kit: **2 font families, 2–3 weights each**, uploaded as `.woff2` in Elementor Pro Custom Fonts. Set all typography in the kit; never set a font family on a single widget.
4. Plugins: one cache (LiteSpeed on LiteSpeed hosts, else FlyingPress), one image optimiser, one SEO plugin, one forms tool. No Elementor add-on pack unless a specific widget is needed; then disable all its other widgets.
5. `WP_POST_REVISIONS` = 10 in wp-config.
6. Tracking: one GTM container only; everything else (pixel, ads tags) lives inside GTM and is delayed until interaction.

## Page rules

- **First screen**: one static image (WebP, sized to its box, lazy-load **off**, `fetchpriority=high`), a heading, text, a button. No slideshow, background video, 3D scene, animation, counter or carousel above the fold.
- **Containers**: max 4 levels deep. One container per visual block, not one per widget.
- **Widgets**: core and Pro widgets only — Heading, Text Editor, Image, Button, Icon List, Gallery, Loop Grid, Form, Nav Menu. If a layout needs an HTML widget, it must not contain `<script>`; scripts go to the theme.
- **Images**: Elementor image size "large" or a custom size matching the box, never "full". WebP via the image optimiser. Lazy-load on for everything below the fold.
- **Video**: Video widget with Lazy Load **on** and a poster image. Never autoplay above the fold.
- **Animations**: entrance animations only below the fold, on 1–2 blocks per page at most. No motion effects.
- **Maps**: a static image linking to Google Maps instead of the Google Maps widget, except on the contact page.
- **Custom CSS**: in the kit or child theme, not per widget. Target Elementor's optimized-markup classes.
- **Popups**: Elementor Pro popups only, with a scroll or time trigger, never on page load.

## Building through Novamira

- Create pages with `novamira/elementor-set-content`, using only the widgets listed above.
- After a batch of pages, run `elementor-audit` and fix anything it flags before handover.
- Targets on mobile Lighthouse before handover: score ≥ 95, LCP ≤ 2.0 s, CLS ≤ 0.05, page ≤ 1 MB, ≤ 50 requests, zero render-blocking resources, LCP element is text or an `<img>`.

`references/checklist.md` is the handover checklist.
