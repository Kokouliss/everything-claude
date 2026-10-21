# elementor-lean

Claude Code plugin for building, auditing and debloating Elementor sites so they score high on
PageSpeed. Talks to the sites through the Novamira WordPress connection (MCP).

## Install

```
claude plugin marketplace add Kokouliss/everything-claude
claude plugin install elementor-lean@everything-claude
```

For claude.ai Skills instead: zip each folder under `skills/` and upload it.

## What's inside

| Piece | Use |
|---|---|
| skill `elementor-audit` | read-only audit: server side (`scripts/audit.php` via Novamira) + Lighthouse (`scripts/lighthouse.sh`) → ranked findings |
| skill `elementor-debloat` | reversible fixes (`scripts/debloat.php`, with `undo`) + the manual playbook in `references/fixes.md` |
| skill `elementor-build` | rules for building lean pages and the handover checklist |
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
