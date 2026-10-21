---
name: elementor-audit
description: Audit an Elementor WordPress site for bloat and PageSpeed problems. Use when asked to audit, check speed, find why a site is slow, or before debloating. Read-only — changes nothing on the site.
---

# Elementor audit

Two measurements, then one ranked list. Never change the site in this skill.

## 1. Server-side audit (through Novamira)

Run `scripts/audit.php` with the site's `novamira/execute-php` ability (pass the file's
contents as `code`, no `<?php` tag). It returns:

- `elementor.features` — experiments on/off (`e_optimized_markup`, `container`, `e_font_icon_svg` matter most)
- `elementor.settings` — `elementor_load_fa4_shim` (`yes` = extra CSS for old icons), `elementor_google_font`, `elementor_element_cache_ttl` (`disable` = no element cache), `elementor_css_print_method`
- `usage` — widget counts, nesting depth, fonts used per widget, and `flags`: entrance animations, background videos/slideshows, sticky, motion effects, custom CSS, **videos without lazy-load**, **FA4 icons**, **inline scripts in HTML widgets**
- `kit_fonts` — font families set in the Elementor kit
- `plugins.by_role` — cache / optimizer / image / elementor add-on / popup / icons / slider plugins, to spot duplicates
- `db` — revisions, autoloaded options KB, transients, Elementor meta KB

## 2. Front-end measurement (Lighthouse, mobile)

`scripts/lighthouse.sh <out_dir> <url>...` prints one JSON line per URL: score, LCP, CLS, TBT,
page KB, request count, DOM size, unused JS/CSS, `lcp_lazy` (hero image lazy-loaded) and
`third_party_kb`. Measure the home page, one inner page, and the checkout or booking page if any.

For a site without Novamira access (a competitor, a reference, a prospect), `scripts/profile-url.sh <url> [out_dir]`
reads the public HTML and prints one JSON object: Elementor/Pro version, theme, cache or optimizer plugin,
add-on packs, font families requested, GTM containers and gtag ids, Facebook pixel, third-party hosts,
animated elements, containers vs legacy sections, script/stylesheet counts, whether the first images are
lazy-loaded, video/iframe counts — plus the Lighthouse line when an out_dir is given.

Then open the saved JSON for the worst page and pull:
`network-requests` sorted by size, `third-party-summary`, `render-blocking-resources`,
`largest-contentful-paint-element`, `bootup-time`.

Caveats to state in the report: lab TBT is inflated on slow shared CPUs (`benchmark` < 1000)
and on pages that run WebGL/3D without a GPU; a site behind a maintenance or coming-soon plugin
measures the maintenance page, not the site.

## Knowledge base (read before ranking)

The plugin's `references/` folder (two levels up from this skill) holds the verified research:

- `references/levers.md` — the score math (metric weights and curves, what "near 100" needs), the
  per-site triage procedure, and every lever with gain, risk, exact how-to and how-to-verify.
  Read its first two sections before ranking; use the triage to order the findings.
- `references/sites-in-the-wild.md` — 32 public Elementor sites measured; the 17 rules at the end
  are the thresholds this audit checks (third-party bytes, hero type, render-blocking count, fonts,
  icon fonts, animations, HTML/DOM size). Compare the site against them.

Trust the Lighthouse JSON network log over HTML grep: `profile-url.sh` misses pixels injected by
Tag Manager, optimizers that rewrite markup, and sites that serve a WAF page to curl.

## 3. Rank findings

Order by expected PageSpeed gain, biggest first. Typical order:

1. Third-party scripts (Tag Manager with several tags, Facebook pixel, chat widgets)
2. Hero image lazy-loaded, or hero is a video/slideshow/3D scene
3. Fonts: more than 2 families, Google Fonts with many weights, `.woff`/`.ttf` instead of `.woff2`, FA4 shim on
4. Render-blocking CSS from plugins not needed on that page (jQuery UI, booking calendars, WooCommerce on non-shop pages)
5. Elementor settings: optimized markup off, element cache disabled, font icons not SVG
6. Entrance animations and motion effects above the fold
7. Video widgets without lazy-load; HTML widgets with inline scripts
8. Duplicate plugins (two image optimisers, two popup tools, Font Awesome plugin next to Elementor icons)
9. Database: revisions > 200, autoload > 800 KB, transients in the thousands

For each finding give: what, where (page/widget/plugin), the fix, the risk, and whether the
debloat skill can do it automatically. Hand the list to `elementor-debloat`.
