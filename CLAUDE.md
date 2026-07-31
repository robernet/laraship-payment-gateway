@AGENTS.md
@docs/api-contract.md

# CLAUDE.md — PaymentGateway (monorepo root)

One Laraship API, multiple Flutter clients, bound by the API contract imported above.

## Layout
- `backend/` — Laraship / Laravel API. Conventions in `backend/CLAUDE.md`.
- `apps/pos/` — Flutter clients, one folder each. Conventions in each `apps/pos/CLAUDE.md`.
- `docs/` — shared source of truth: `api-contract.md` (the API) and `flutter-conventions.md` (rules common to all clients).
- `packages/core/` — shared Dart package (API client, auth, freezed models) every app depends on.
- `reference/devkit/` — read-only vendored UI kits; borrowed from, never built.

Current apps (edit per project):
- `apps/pos/`      — POS Simulator

Scoped `CLAUDE.md` files load only when Claude touches their directory; `AGENTS.md` and the contract load here at launch.

## Shared invariants
- The API contract is the single source of truth; an endpoint change updates the contract, the backend, and each affected app in the **same PR**.
- Resources cross the API as **Hashid strings** — never the raw BIGINT primary key. See the contract for identifier and Sanctum auth rules.
- Shared client code lives in `packages/core` — don't re-implement per app.
- Never commit `.env`, signing keys, keystores, or service-account JSON.

## Workspace
- Manage the Dart/Flutter workspace (apps + packages) with melos.

## Git
- Conventional commits, scoped: `feat(PaymentGateway):`, `fix(api):`, `chore(pos):`
- One logical change per PR; backend + affected apps together when an endpoint changes.
