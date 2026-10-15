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

## Requirements

- A Novamira MCP connection to the site (`mcp__novamira-<site>__…` tools).
- For Lighthouse: Node 18+, `npm i lighthouse`, Chromium (`CHROME_PATH`).
