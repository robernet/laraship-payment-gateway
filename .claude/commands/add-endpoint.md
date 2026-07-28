---
description: Scaffold a new API endpoint across the stack — contract, Laravel backend, and one or more Flutter apps — in one pass.
argument-hint: <resource> [verb] [fields...] [--apps customer,driver]
allowed-tools: Read, Edit, Write, Bash(dart run build_runner build:*), Bash(php artisan optimize:clear)
---

# Add endpoint: $ARGUMENTS

Treat `$ARGUMENTS` as a free-form spec. Parse from it: the **resource** name, the HTTP **verb(s)** (default to a sensible CRUD subset if unspecified), any **fields** with types, and an optional **`--apps`** list naming which clients under `apps/` need the client-side code. If the resource or intent is ambiguous, ask one clarifying question before writing anything.

## 0. Ground yourself
Read @docs/api-contract.md in full first. The response envelope, error format, Hashid rule, Sanctum auth, date format, and money convention all come from there — do not invent alternatives.

## 1. Contract first
Add or update the endpoint entry in `docs/api-contract.md`:
- Path under `/api/v1`, addressed by `{hashid}`.
- Request body fields (with types), the success shape (inside `data`), and pagination for any list endpoint.
- Ids as hashids, money as integer minor units + `currency`, dates ISO 8601 UTC.

Show me the contract diff before proceeding.

## 2. Backend — Laraship module
Following `backend/CLAUDE.md`, scaffold in the module (use `php artisan make:module` output or sibling-module patterns):
- **Migration** — standard BIGINT `id` primary key + your columns. No uuid column.
- **Model** — decode the hashid to the integer id for route binding (a `resolveRouteBinding()` override or controller decode), matching existing modules.
- **Form Request** — validation for every input field.
- **Controller** — extend `APIBaseController` (or `APIPublicController` for public); thin, delegates to the service.
- **Service** — business logic plus any gateway or FCM calls.
- **Fractal Transformer** — extend `Transformer`; emit the hashid as `id`. Never output the raw integer PK.
- **Route** — register under the module's `/api/v1` routes.

## 3. Shared client model
Add or update the freezed model + service method in `packages/core` (the shared Dart package), matching the contract — `id` as a String hashid, money as int minor units. Run codegen there: `dart run build_runner build --delete-conflicting-outputs`. All apps consume this; don't duplicate it per app.

## 4. Per-app wiring
If `--apps` was given, wire each named app; otherwise **ask which apps under `apps/` need this** before touching any. For each target app, following `apps/<app>/CLAUDE.md` and @docs/flutter-conventions.md:
- Under `apps/<app>/lib/features/<resource>/`: a Riverpod provider/controller over the `packages/core` service, plus the screen(s) — composed by borrowing from that app's `ui-catalog.md`, not by hand-rolling UI.
- Only build the surface that app actually needs; apps may expose different subsets of the resource.

## 5. Close the loop
- Confirm every field is identical in the contract, the transformer, and the `packages/core` model.
- Do NOT run migrations or touch production — leave `php artisan migrate` for me to run and review.
- Summarize the files created/edited (backend, core, each app) so this lands as a single PR.
