# Laraship multi-app template

One Laraship (Laravel) API + multiple Flutter clients, wired for Claude Code.
Identifiers are **Hashids**, API auth is **Laravel Sanctum**.

## Structure
```
├── AGENTS.md                     Boost-owned baseline (regenerated; don't hand-edit)
├── CLAUDE.md                     thin root: imports @AGENTS.md + @docs/api-contract.md
├── backend/CLAUDE.md             Laraship "brain" (module system, base classes, Hashids…)
├── docs/
│   ├── api-contract.md           shared API — the source of truth for every client
│   └── flutter-conventions.md    rules common to all Flutter apps (imported by each app)
├── packages/core/                shared Dart: API client, auth, freezed models
├── reference/                    read-only vendored UI kits (borrowed from, never built)
├── apps/
│   ├── _app-template/            copy this to add an app
│   ├── customer/  driver/  agent/  pos/     { CLAUDE.md, ui-kit.md }
└── .claude/commands/
    ├── add-endpoint.md           /add-endpoint — contract + backend + chosen apps
    └── ui-catalog.md             /ui-catalog <app> — index an app's reference kit
```

## First-time setup
1. Rename: fill `<project>` in `CLAUDE.md`, `melos.yaml`, and the app titles/roles.
2. Backend: drop your Laraship app under `backend/`; let Laravel Boost generate `AGENTS.md`.
3. Shared code: build the Dio + Sanctum + Hashids client and freezed models in `packages/core/`.
4. Add an app: `cp -r apps/_app-template apps/<name>`, then `flutter create` its project files and fill its `CLAUDE.md` + `ui-kit.md`.
5. UI kit (per app): vendor the kit into `reference/<kit>/` (keep `lib/`, `pubspec.yaml`, `assets/`), point `apps/<name>/ui-kit.md` at it, then run `/ui-catalog <name>`.
6. Build endpoints: `/add-endpoint <resource> [fields...] --apps customer,driver`.

## How Claude Code loads this
`AGENTS.md` and `docs/api-contract.md` load at launch via the root `CLAUDE.md`.
`backend/CLAUDE.md` and each `apps/<app>/CLAUDE.md` load only when Claude touches
that directory — so a session in `apps/pos/` never carries backend or other-app context.
