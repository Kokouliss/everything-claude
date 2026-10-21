# elementor-lean

Claude Code plugin for building, auditing and debloating Elementor sites so they score high on
PageSpeed. Talks to the sites through the Novamira WordPress connection (MCP).

## Install

**Claude Code** (CLI, web, IDE) — from this repository's marketplace:

```
claude plugin marketplace add Kokouliss/everything-claude
claude plugin install elementor-lean@everything-claude
```

**Claude desktop app (Cowork)** — open `dist/elementor-lean.plugin` from this repository (the same
plugin zipped; the app shows the files and an Accept button). Rebuild it after changes with
`cd plugins/elementor-lean && zip -r ../../dist/elementor-lean.plugin . -x '*.DS_Store'`.

Both read the same files: `claude plugin validate plugins/elementor-lean` passes; the only notes are
for the public plugin directory (no `license`, no icon), which this private plugin does not need.

## What's inside

| Piece | Use |
|---|---|
| skill `elementor-audit` | read-only audit: server side (`scripts/audit.php` via Novamira) + Lighthouse (`scripts/lighthouse.sh`) → ranked findings |
| skill `elementor-debloat` | reversible fixes (`scripts/debloat.php`, with `undo`) + the manual playbook in `references/fixes.md` |
| skill `elementor-build` | rules for building lean pages and the handover checklist |
| skill `elementor-design` | from brief / Figma / XD / references to a sophisticated design system and complete UX (typography, colour, imagery, layout, booking flows, accessibility) implemented with Global Classes and Variables |
| skill `elementor-motion` | GSAP, anime.js, CSS scroll-driven and Elementor Interactions — premium motion within the budget: after LCP, transform/opacity only, reduced-motion aware; 12+ recipes with costs |
| agent `elementor-designer` | turns a brief into direction, tokens and a page plan, then builds it |
| agent `elementor-motion-designer` | adds animation and micro-interactions to built pages, measures, undoes on regression |
| agent `elementor-ux-reviewer` | read-only review of a built page: design quality, UX, WCAG 2.2 AA, motion budget, the 95+ rules |
| agent `elementor-auditor` | runs the audit and returns findings |
| agent `elementor-debloater` | applies fixes one at a time with before/after measurement |
| agent `elementor-builder` | builds pages that follow the rules and audits them before handover |
| `references/levers.md` | the verified playbook: score math, triage, every lever with gain/risk/how-to/verify, cache-plugin matrix, never-auto-enable list |
| `references/breakage-checklist.md` | the safety harness run after every change |
| `references/design-to-elementor.md` | building lean sites from Figma / XD / references |
| `references/sites-in-the-wild.md` | 32 public Elementor sites measured: what fast ones do right, slow ones wrong, 17 rules (raw data in `references/data/`) |

The references were produced by adversarially reviewed research (October 2026); each claim keeps its
sources and a confidence mark. Re-verify anything marked medium/low before relying on it.

## Requirements

- A Novamira MCP connection to the site (`mcp__novamira-<site>__…` tools).
- For Lighthouse: Node 18+, `npm i lighthouse@12` (13.x renamed the audits the script reads), Chromium (`CHROME_PATH`).
- Optional: a PageSpeed Insights API key for field (CrUX) data; the anonymous quota returns HTTP 429 after a few calls.
