# Project instructions for AI

**Before starting work on ANY task, first read `docs/design-system.md`.** It is the single
source of truth for this theme — stack, environments, content model, design tokens, CSS/Blade
conventions, providers, and gotchas. Also honour the rules under `.claude/rules/`.

Key points:
- Coding conventions & preferences live in `docs/design-system.md` §14 — follow them (element
  choice by semantics, theme tokens over raw values, minimal comments, hooks in classes, …).
- **Verify, don't assume:** there is no unit-test suite. A change counts as done only after
  `.claude/rules/verification.md` — build clean, rendered DOM checked in **both** languages,
  empty-field edge cases exercised. Reading the diff is not verification.
- **Document as you build:** when you add a feature, subpage, provider, or design principle,
  record it in `docs/design-system.md` in the same change.
- Custom wp-admin CPTs/options pages join the ARPI menu group — see §10.
