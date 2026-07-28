# Graphify — run at the repo root

Graphify indexes the whole monorepo into a queryable graph so agents can trace
`apps/<app> -> packages/core -> backend endpoint -> docs/api-contract.md` without
grepping. Run it at the **root**, not inside `backend/` — a backend-only graph
misses the cross-language traversal that is the whole reason to use it here.

## Setup (per clone)
```
uv tool install graphifyy      # PyPI package name has two y's
graphify install               # register the /graphify skill with Claude Code
/graphify .                    # build at the root
/graphify query "how does POS auth reach the backend?"
graphify hook install          # rebuild on git commit (a per-AI-turn hook is too slow)
```

## Excludes
`.graphifyignore` at the root keeps the graph small and clean: `reference/`
(third-party kit), `backend/vendor/`, build/platform dirs, and — defensively —
secrets. Graphify also honors `.gitignore` (the two are merged, `.graphifyignore`
wins on conflicts). Do NOT use `!` negation patterns: a single negation disables
directory pruning and the scan descends into everything (graphify #882 / #1276),
which is how monorepo-root scans blow up to hundreds of MB.

## Notes
- Commit the root `graphify-out/` so every agent and teammate shares one map.
- Delete any old `backend/graphify-out/` — it's a stale, backend-only graph
  (`scripts/make-project.sh` removes it on export).
- Neutrally-named secrets (e.g. `auth.json`) are not auto-detected as sensitive,
  so they're listed explicitly in `.graphifyignore`.
