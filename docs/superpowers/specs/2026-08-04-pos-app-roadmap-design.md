# POS App Roadmap — Design

Backend for this pass is frozen: `pos/login`, `payment-references/lookup/{reference}`, `POST /transactions`, `POST/PATCH /shifts` already exist and match `docs/api-contract.md`. This roadmap plans `apps/pos/` (currently only `lib/main.dart`) and the `packages/core` PaymentGateway scaffolding it depends on (currently only the generic `ApiClient`/`AuthService`/`TokenStore`/`Money`/`Paginated` scaffold — no domain models).

## Scope

Core cash-collection flow only, per `apps/pos/CLAUDE.md`'s stated screens: operator login → shift open → reference lookup → collect → receipt → shift transaction list → shift close. Explicitly out of scope for this roadmap: offline/poor-connectivity handling, receipt printing, multi-device (`Pos` model) identity, AutoPay/invoice-mode visibility, anything admin-adjacent.

## Known gap: no transaction list endpoint

`apps/pos/CLAUDE.md` lists "shift transaction list" as a screen, but `docs/api-contract.md`'s `Transaction` resource only defines `POST /transactions` — no `GET` list endpoint exists. Since backend is frozen this pass, the POS app resolves this by accumulating the shift's transactions **client-side**: each successful `POST /transactions` response is appended to a Riverpod-held list scoped to the current shift. No backend change needed. This list is lost on app restart mid-shift — acceptable for a POS simulator; revisit with a real `GET /transactions?shift_id=` endpoint if that matters later.

## Phases

Each phase is a vertical slice through `packages/core` + `apps/pos/lib`, independently runnable, sequential (no parallelizable phases — each depends on state the previous phase produced).

### Phase 0 — Foundation
- Run `/ui-catalog pos` (prerequisite — `apps/pos/ui-catalog.md` doesn't exist yet).
- `packages/core`: freezed models `Issuer`, `PaymentReference`, `Transaction`, `Shift` — fields exactly per `docs/api-contract.md`, `id`/`*_id` fields as `String` (Hashids). `PaymentGatewayService` (Dio, wraps `ApiClient`) with method stubs for phases 1–7's calls.
- `apps/pos/lib`: `go_router` route table, Riverpod `ProviderScope` at app root, Dio client wired to `--dart-define=API_BASE`, a 401 interceptor that clears the token and routes to login (per `docs/flutter-conventions.md`).

### Phase 1 — Login
- Screen: email, password, store picker → `POST /pos/login`.
- On success: persist token via `TokenStore`, hold `abilities` + `store_id` in an auth provider.
- Route guard: unauthenticated → login screen.

### Phase 2 — Shift open
- On first authenticated entry, if no open shift is held in state, force an "open shift" screen → `POST /shifts`.
- Shift state provider holds: the open `Shift`, and an empty local transaction list (see gap above).

### Phase 3 — Reference lookup
- Enter/scan reference screen → `GET /payment-references/lookup/{reference}`.
- Amount-due screen: amount, currency, due date, status. Handle "not found" and "already collected" (`status == collected`) as distinct states, not generic errors.

### Phase 4 — Collect & confirm
- Cash entry (amount defaults to the reference's `amount` — every reference is Invoice-backed with a fixed amount per the current contract) → confirm → `POST /transactions`.
- On success: append the returned `Transaction` to the shift's local list (Phase 2's provider).
- Surface `422` (amount mismatch, or overdue + issuer's `reject_late_payment`) as inline form errors, not a crash/generic error screen.

### Phase 5 — Receipt / confirmation
- Post-collect summary: reference, amount, folio (if present on the reference), collection timestamp.
- Actions: "collect another" (back to Phase 3) or "view shift" (Phase 6).

### Phase 6 — Shift transaction list
- Reads the client-side list accumulated in Phases 2/4. No new endpoint, no new service method — a screen over existing state.

### Phase 7 — Shift close
- Counted-cash input screen → `PATCH /shifts/{hashid}` with `{counted_amount}`.
- Display the server-computed `discrepancy_minor` from the response (over/short/exact) — never compute this client-side.

## Dependencies

Strictly sequential: 1 needs 0's client; 2 needs 1's token; 3 can run once 2 has an open shift (per app flow, though the lookup endpoint itself doesn't require one); 4 needs 3's looked-up reference and 2's shift state; 5 needs 4's result; 6 needs 4's accumulated list; 7 needs 2's shift + 6's context to be meaningful to the operator (not a hard API dependency, but the UX order).

## Testing

Per phase: one `flutter test` widget test covering the screen's happy path and one representative error state, plus a manual run against the local API. No integration/e2e harness for this pass — eight screens in a POS simulator don't warrant one; revisit if flakiness in manual QA becomes a recurring problem.

## Out of scope / explicitly deferred

- Offline queueing or retry for flaky in-store connectivity.
- Real receipt printing (simulated per `apps/pos/CLAUDE.md`: "simulates hardware... instead of integrating real peripherals").
- `Pos` device-identity login (separate from the operator email/password login `PosAuthController@login` implements today).
- AutoPay / invoice-mode awareness in the POS UI.
- A real `GET /transactions` list endpoint — revisit if the client-side transaction list (see gap above) proves insufficient.
