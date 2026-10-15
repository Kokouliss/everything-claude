# Handover checklist (per site)

Site-wide
- [ ] Flexbox containers, optimized markup, SVG icons on; FA4 shim off; element cache 24h
- [ ] 2 font families max, `.woff2`, weights listed, Google Fonts off
- [ ] One cache plugin, one image optimiser, one SEO plugin; no duplicate popup/forms/icons plugins
- [ ] Add-on packs: none, or unused widgets disabled
- [ ] One GTM container; pixel and ads tags inside it; delayed until interaction
- [ ] `WP_POST_REVISIONS` = 10; revisions pruned
- [ ] Cookie banner deferred

Per key page (home, main inner page, checkout/booking)
- [ ] Hero: static image, lazy-load off, correct size, WebP
- [ ] No slideshow / background video / 3D / animation / counter above the fold
- [ ] Containers ≤ 4 deep
- [ ] All videos lazy-loaded with poster
- [ ] No `<script>` inside HTML widgets
- [ ] Plugin CSS not needed on this page is unloaded (jQuery UI, booking calendar, Woo on brochure pages)
- [ ] Mobile Lighthouse: score ≥ 85, LCP ≤ 2.5 s, CLS ≤ 0.1, ≤ 1.5 MB, ≤ 60 requests
