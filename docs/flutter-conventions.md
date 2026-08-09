# Flutter conventions — shared across all client apps

Imported by every app's `CLAUDE.md`. Holds the rules common to all Flutter clients that hit the Laraship API. App-specific details live in each `apps/<app>/CLAUDE.md`; the UI kit lives in each `apps/<app>/ui-kit.md`.

## Assumed stack
- State: Riverpod · HTTP: Dio · Models: freezed + json_serializable · Routing: go_router

## Shared code
- The API client, auth, and freezed models live in `packages/core/` — a local Dart package every app depends on. Reuse it; never re-implement the client or models per app.

## Data & network layer
- The resource identifier is `id` — a **Hashid string**. Never expect or send integer ids.
- Deserialize against the contract's success envelope; handle the error format centrally in a Dio interceptor.
- Base URL from `assets/config/api_config.json` (bundled, loaded at startup — see `docs/api-contract.md`); version is a fixed `/api/v1` prefix in `ApiClient`. Attach the Sanctum bearer token via interceptor; on `401` clear the token and route to login — Sanctum tokens don't refresh.
- Money arrives as integer minor units + `currency`; format for display only, never store as double.

## State management
- Riverpod. UI kits are reference-only (below), so their Bloc/Cubit code is never imported.

## Structure
- Feature-first per app: `lib/features/<feature>/{data,domain,presentation}`.

## UI kit — reference / resources provider
Each app declares a vendored, read-only reference kit in its `ui-kit.md` (with a generated `ui-catalog.md`). The kit is a source to borrow from, NOT the app skeleton:
- Before building a screen/widget, check the app's `ui-catalog.md` for something to adapt.
- Borrow and adapt — port only what a screen needs into the app's own `lib/`, rewritten to fit its theme and Riverpod state. Don't bulk-copy; kits carry duplicated and unnecessary widgets.
- Pull across only the assets/packages a ported widget actually uses.
- Never wire a reference dir into the build: no `import 'package:<kit>/...'`, no kit entries in the app's `pubspec.yaml`.
- Keep each app's `lib/` clean and deduplicated.

## Commands (run inside an app dir)
- Run: `flutter run` (edit `assets/config/api_config.json` for a different backend URL, then hot-restart)
- Analyze: `flutter analyze` — clean before commit.
- Test: `flutter test`
- Codegen after model changes: `dart run build_runner build --delete-conflicting-outputs`

## Adding a screen that calls the API
1. Confirm the endpoint in `docs/api-contract.md`.
2. Check the app's `ui-catalog.md` for a layout/widget to adapt.
3. Model (freezed, `id` as String) from `packages/core` -> service (Dio) -> Riverpod provider -> screen.
4. No field the contract doesn't define.
