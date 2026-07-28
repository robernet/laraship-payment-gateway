# SECURITY — credential rotation

The template archive that was shared included live secrets. Treat them as exposed,
rotate now, then keep them out of every future export.

## Rotate now
- **backend/.env** (was packed in the zip, ~2.6 KB):
  - `APP_KEY` — run `php artisan key:generate` (this invalidates existing encrypted
    values and sessions).
  - `DB_PASSWORD` — change it on the database server, then update `.env`.
  - Any `MAIL_*`, queue, cache, storage, or third-party API keys present — reissue them.
- **backend/auth.json** (~0.2 KB): Composer credentials for the Corals / CodeCanyon
  private repo. Reset them in your CodeCanyon / Laraship account, then re-run
  `composer config` locally. This file is NOT flagged as sensitive by common tools
  (neutral name), so it leaks quietly — that's why it's listed explicitly in both
  `.gitignore` and `.graphifyignore`.
- **backend/.mcp.json**: if any MCP server entry carries a token, rotate it.

## Keep them out going forward
- `.env` and `auth.json` are git-ignored; commit `.env.example` only.
- Never distribute the template as a raw folder-zip — zipping ignores `.gitignore`
  and repacks `vendor/`, `.env`, and `auth.json`. Use `scripts/make-project.sh`
  (clean `git archive` export) or ship it as a git template repo.
- `.graphifyignore` also excludes these, so secrets never enter the knowledge graph.
