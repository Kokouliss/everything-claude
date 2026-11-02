# Project instructions: web design, Elementor/WordPress, SEO/GEO/AEO

Paste this into a project's instructions (Claude Project, CLAUDE.md, or a Cowork project). Edit the
"About the work" lines per client. Skill names are written as Claude Code shows them
(`plugin:skill`). Load the skill named for a task before starting that task.

## About the work

- Agency building and redesigning WordPress sites with Elementor (4.x, Pro) on a lean child theme
  (Inglelandi child or Hello Elementor child). Main sectors: hotels, villas, restaurants, attractions.
- Sites are bilingual Greek and English. Reply in the language I write in.
- Sites are reached through their Novamira MCP connection (`mcp__novamira-<site>__…`). Work on staging
  first. Never change a live site without my OK.

## Non-negotiables (these beat any skill or brief)

1. **Speed gate per page**: mobile Lighthouse median of 3 runs ≥ 95, LCP ≤ 2.0 s, CLS ≤ 0.05,
   TBT < 300 ms, 0 render-blocking resources, page ≤ 1 MB, ≤ 50 requests.
2. **Static hero**: the LCP is the h1 text or one `<img>` (WebP/AVIF ≤ 100 KB, `fetchpriority="high"`,
   never lazy). No slider, background video, Lottie, 3D or animated headline above the fold.
3. **Fonts**: 2 Greek-capable families, ≤ 4 woff2 files, self-hosted, `font-display: swap`. Run a Greek
   glyph check before proposing a font. No icon fonts: icons are SVG.
4. **Structure**: containers ≤ 4 deep, one DOM for all devices (no "hide on mobile" twins), core and Pro
   widgets only, no add-on packs unless one widget is truly needed.
5. **Tokens only**: ≤ 8 colour Variables, 2 font Variables, one type scale, ≤ 30 Global Classes. No local
   colour or font overrides on single widgets. Custom CSS lives in the kit or child theme.
6. **Motion**: nothing animates above the fold at load or on the LCP element. Animate `transform` and
   `opacity` only. Respect `prefers-reduced-motion`. Animation libraries load after LCP on first
   interaction. No smooth-scroll libraries on mobile. Content is visible without JS.
7. **Third-party JS** deferred to interaction (booking engine, maps, reCAPTCHA on focus, chat, pixels).
   One GTM container; everything else inside it.
8. **Accessibility WCAG 2.2 AA**: text contrast 4.5:1, large text/UI/focus 3:1, targets ≥ 24 px (48 px
   house rule), visible focus never hidden by a sticky header, reflow at 320 px, every field labelled.
9. **Truthful content**: all-in prices where a price first appears, urgency only from live data,
   discounts only with a real prior price. Never invent reviews, ratings, FAQs, addresses, hours or
   prices, in copy or in schema. Ask me for missing facts.
10. **Measured and reversible**: one change at a time, before/after numbers, undo on regression.

## Which skill to use

Pick **one** design-direction skill per project and stick to it. Do not mix taste-skill, gpt-taste and
high-end-visual-design on the same site.

| Task | Skills, in order |
|---|---|
| Design a new Elementor site or page | `elementor-lean:elementor-design` → `elementor-lean:elementor-build` → `elementor-lean:elementor-motion`; agents `elementor-designer` → `elementor-motion-designer` → `elementor-ux-reviewer` |
| Audit / speed up an Elementor site | `elementor-lean:elementor-audit` → `elementor-lean:elementor-debloat` (agents `elementor-auditor`, `elementor-debloater`) |
| Migrate a Kallyas/Zion site | `anthropic-skills:kallyas-to-elementor` |
| Design input: Figma / XD / screenshots / live URL | Figma connector, `anthropic-skills:adobe-xd`, `taste-skill:image-to-code-skill` (extraction rules only), `anthropic-skills:extract-design-system <url>` |
| Design direction from a brief | `taste-skill:taste-skill` (default); alternatives `impeccable:impeccable shape`, `ui-ux-pro-max:ui-ux-pro-max` (token draft), `ecc:frontend-design-direction` |
| A specific look | `taste-skill:soft-skill` (high-end/luxury), `taste-skill:minimalist-skill` (editorial), `taste-skill:brutalist-skill`, `taste-skill:gpt-tasteskill` (GSAP-heavy, non-Elementor only), `taste-skill:stitch-skill` (DESIGN.md for Google Stitch) |
| Concept images before code | `taste-skill:imagegen-frontend-web`, `taste-skill:imagegen-frontend-mobile` |
| Redesign an existing site | `taste-skill:redesign-skill`, `impeccable:impeccable audit` / `critique` / `polish`, `ecc:inherit-legacy-style` (keep existing conventions) |
| Good practices / polish | `anthropic-skills:web-design-guidelines`, `ecc:make-interfaces-feel-better`, `anthropic-skills:emil-design-eng`, `anthropic-skills:apple-design`, `anthropic-skills:mobile-native`, `anthropic-skills:break-ui` (worst-case data) |
| Animation | `design-motion-principles:design-motion-principles`, `anthropic-skills:animate`, `anthropic-skills:animejs`, `anthropic-skills:find-animation-opportunities`, `anthropic-skills:improve-animations`, `anthropic-skills:review-animations`, `anthropic-skills:animation-vocabulary`; React: `ecc:motion-foundations` / `ecc:motion-patterns` / `ecc:motion-advanced`; React Native: `anthropic-skills:animate-expo` |
| Critique and UX review | `design:design-critique`, `perception-first-design:evaluate` (score), `perception-first-design:solve` (derive a fix), `perception-first-design:analyze` ("what if") |
| Accessibility | `design:accessibility-review`, `ecc:accessibility`, `ecc:frontend-a11y` (React), agent `ecc:a11y-architect` |
| Design system, tokens, handoff | `design:design-system`, `ecc:design-system`, `ui-ux-pro-max:design-system`, `design:design-handoff`, `impeccable:impeccable document` / `extract` |
| Brand and visual identity | `ecc:brand-discovery`, `ui-ux-pro-max:brand`, `taste-skill:brandkit`, `ui-ux-pro-max:design` (logo, icons, CIP), `ui-ux-pro-max:banner-design`, `anthropic-skills:theme-factory` |
| Copy and microcopy | `design:ux-copy`, `ecc:brand-voice`, `ecc:article-writing`, `ecc:content-engine`; always finish with `unslop:unslop` |
| SEO (technical, on-page, schema) | `ecc:seo`, agent `ecc:seo-specialist`, `wp-dev-skills:wp-structured-data` (JSON-LD from WordPress) |
| GEO / AEO (AI search visibility) | `sitelemetry:ai-visibility`, then `sitelemetry:fix-and-retest` |
| Site audit and pre-launch | `sitelemetry:website-audit`, `sitelemetry:pre-launch-check`, `sitelemetry:domain-verification` |
| Performance and QA | Chrome DevTools MCP Lighthouse, `ecc:benchmark`, `ecc:browser-qa`, `ecc:click-path-audit`, `ecc:e2e-testing`, `ecc:production-audit`, agent `ecc:performance-optimizer` |
| Frontend code (non-Elementor) | `ecc:frontend-patterns`, `ecc:react-patterns`, `ecc:react-performance`, `ecc:vue-patterns`, `ecc:nextjs-turbopack`, `ecc:vite-patterns`, `ui-ux-pro-max:ui-styling` (Tailwind/shadcn), `anthropic-skills:pick-ui-library`, `anthropic-skills:prototype` (compare versions) |
| WordPress plugin / theme code | `wp-dev-skills:wp-coding-standards`, `wp-i18n-workflow`, `wp-woocommerce`, `wp-admin-browser`, `wp-plugin-audit`, `wp-build-tools` |
| User research | `design:user-research`, `design:research-synthesis`, `ecc:market-research` |
| Long code or full files | `taste-skill:output-skill` (no placeholders, no truncation) |

When two skills disagree: my brief → the non-negotiables above → the project's own brand and tokens →
the design skill's advice.

## Workflow for a new site or redesign

1. **Brief**: site type, audiences, languages, the one conversion goal (book, enquire, reserve, buy,
   call), booking engine, pages, real photography available, brand to keep.
2. **Direction**: present two directions (fonts, scale, palette with contrast ratios, hero sketch with
   the booking/enquiry entry inside the first 390 × 844 screen, imagery, 1–2 zero-JS signature
   details). **Stop and wait for my pick.**
3. **Tokens and system**: Variables + Global Classes, mirrored in the kit globals.
4. **Build static** page by page. Each page passes the speed gate before the next starts.
5. **Motion** only after the static gate, cheapest technique first (CSS → Web Animations →
   Elementor Interactions → anime.js → GSAP), measured before and after.
6. **Review** at 390 px and 1440 px with screenshots (`elementor-ux-reviewer`), fix, re-measure.
7. **SEO / GEO / AEO** pass (below), then `sitelemetry:pre-launch-check`.
8. **Handover**: scores, what changed, what is still open, how to undo.

## SEO, GEO and AEO rules

- One h1 per page, logical h2/h3, unique title (≤ 60 chars) and meta description (≤ 155 chars) per
  page and per language.
- Greek and English versions linked with `hreflang` (plus `x-default`), language-specific slugs, one
  canonical per page, XML sitemap listed in robots.txt.
- One SEO plugin only. JSON-LD only for facts visible on the page (Organization or LocalBusiness /
  Hotel / Restaurant, WebSite, BreadcrumbList, FAQPage only for real FAQs). One stable `@id` per entity
  across pages. Validate before publishing.
- Same name, address and phone everywhere (site, Google Business Profile, directories).
- Answer-first sections: state who the business is, what it offers and where on the home and about
  pages. FAQs come from real guest questions.
- Source signals: author and date on articles, real about and contact pages.
- robots.txt for AI crawlers is the owner's decision: explain training crawlers vs search/user-fetch
  crawlers, keep existing rules, never block Googlebot or Bingbot. `llms.txt` is optional and links
  only to real public pages.
- Say what was measured: AI-visibility audits check the site's readiness, not whether an AI assistant
  actually cites it.

## How to work with me

- Ask before anything hard to undo (deleting, publishing, switching themes, editing live).
- Keep answers short. Lead with the result, then numbers, then what is still open.
- Copy goes through `unslop:unslop`. No filler, no AI-sounding phrasing, in Greek or English.
- If a skill or connector I named is missing or not connected, say so instead of guessing.
