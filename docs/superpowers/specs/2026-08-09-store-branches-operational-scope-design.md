# Store Branches — Branch as the operational unit

**Date:** 2026-08-09
**Status:** Design approved, pending spec review
**Scope:** Backend + `docs/api-contract.md` + admin UI. Flutter (`apps/pos`) follows in a later PR.

## Problem

Today POS terminals, operators, shifts, and transactions attach directly to a
`Store`. Auth scopes to the store: `POST /pos/login` takes `store_id`, tokens
carry a synthetic `store:{hashid}` ability, and `POST /shifts` opens against
`store_id`. A merchant with multiple physical locations has no way to organize
those terminals and staff.

## Goal

Introduce a **Branch** layer. `Store` becomes the merchant/company — a pure
container. `Branch` becomes the physical PDV: POS terminals, operators, and
shifts attach to a branch. A Store has many Branches; a Branch belongs to one
Store. The Branch is the **operational scope** — login, tokens, shifts, and
transactions all key off a branch instead of a store.

## Locked decisions (from brainstorming)

1. **Branch is the operational scope** — not just an admin grouping.
2. **Fresh start, no backfill** — additive columns only, no destructive
   migration. Existing POS/operators/shifts keep their `store_id` and are
   legacy; admins re-assign POS & operators to branches by hand.
3. **This PR = backend + contract + admin UI.** Flutter follows.
4. **`store_id` stays denormalized**, not normalized away (see Schema).
5. **Operators are assigned per-branch** (new pivot), not per-store.

## Schema (all additive)

### New: `paymentgateway_branches`
Mirrors `paymentgateway_stores`.
- `id`
- `store_id` — FK → `paymentgateway_stores`
- `name` (string)
- `properties` (text, nullable, json cast)
- `auditable()`, `softDeletes()`, `timestamps()`

### New: `paymentgateway_operator_branches` (pivot)
Mirrors `paymentgateway_operator_stores`.
- `id`
- `user_id` — `unsignedInteger`, FK → `users` (users.id is INT, not BIGINT)
- `branch_id` — FK → `paymentgateway_branches`
- `timestamps()`
- unique(`user_id`, `branch_id`)

The old `paymentgateway_operator_stores` table + `OperatorStore` model stay in
place, untouched, as legacy. No rename (rename would force a backfill we
explicitly declined).

### Altered: `paymentgateway_pos`
- add `branch_id` — nullable FK → `paymentgateway_branches`

Existing rows keep `store_id` only (`branch_id` null = legacy, needs manual
re-assign). New POS are created under a branch and set **both**:
`branch_id` and `store_id = branch.store_id`.

### Altered: `paymentgateway_shifts`
- add `branch_id` — nullable FK → `paymentgateway_branches`

New shifts set `branch_id` and keep `store_id = branch.store_id`.

### On the denormalized `store_id`

`store_id` on `pos` and `shifts` is `NOT NULL` today, and `ReportController`
groups by it. Rather than modify those columns to nullable and rework the
report, new rows carry `store_id = branch.store_id` as a denormalized copy.

`// ponytail: store_id denormalized from branch.store_id to avoid a
// column-modify migration + report refactor; drop it and join through branch
// when it becomes a maintenance burden.`

## Auth + API contract changes

Update `docs/api-contract.md` alongside the backend.

### `POST /pos/login`
- Body: `{email, password, branch_id}` (was `store_id`).
- Resolve branch by hashid; `422` if not found.
- Assignment check against `paymentgateway_operator_branches` (was
  `operator_stores`); `422` if the operator is not assigned to the branch.
- Abilities: `payment:lookup`, `payment:collect`, `transaction:read-own`,
  `shift:manage`, `branch:{hashid}` (was `store:{hashid}`).
- Response: `{token, abilities, branch_id, store_id}` (`store_id` =
  branch.store_id, for client context).

### `POST /pos/device-login`
- Inputs unchanged: `{code, device_secret}` — the POS is identified by `code`
  and is itself tied to a branch.
- Abilities: same set as an operator token plus `branch:{hashid}` (from
  `pos.branch`) **and** `pos:{hashid}`.
- Response: `{token, abilities, branch_id, store_id}`.

### `POST /shifts`
- Body: `{branch_id}` (was `store_id`).
- Token must carry `branch:{hashid}` matching the requested branch (`403`
  otherwise).
- Records `branch_id`, `store_id = branch.store_id`, `opened_at`, and the
  opener identity (`pos_id` for a device token, `operator_id` for a user token
  — unchanged rule).

### Shift resource fields
- add `branch_id` (hashid, string)
- keep `store_id`, `operator_id` (nullable), `pos_id` (nullable),
  `opened_at`, `closed_at`, `counted_amount_minor`, `discrepancy_minor`.

### No `GET /branches` yet
Login takes `branch_id` as a raw hashid param exactly as `store_id` was —
YAGNI. A listing/selection endpoint is deferred to the Flutter follow-up. The
contract gets a short **Branch** note (operational scope, admin-managed,
listing deferred), not a full resource section.

## Admin UI

### New Branch resource
Cloned from the Store/Pos pattern:
- `Models/Branch.php` (`ApiHashTrait`, `PresentableTrait`, `LogsActivity`;
  `store()` belongsTo, `terminals()` hasMany Pos on `branch_id`, `operators()`
  belongsToMany User through `operator_branches`).
- `Http/Controllers/BranchesController.php` — resourceful CRUD +
  `assignOperator` / `removeOperator` (moved from `StoresController`, now
  writing `operator_branches`).
- `Http/Requests/BranchRequest.php`, `Policies/BranchPolicy.php`,
  `DataTables/BranchesDataTable.php`, `Transformers/BranchPresenter.php`.
- Config registration (`config/paymentgateway.php` models block), permission
  registration, menu entry (Branches), routes in `routes/web.php`,
  `resources/lang/en/attributes.php` + `module.php` strings.
- Views: `branches/index.blade.php`, `branches/create_edit.blade.php`,
  `branches/show.blade.php`.

### Store show page (`stores/show.blade.php`)
- **Add** a "Branches" panel: list the store's branches, link to each branch's
  show page, create-branch button (pre-filled `store_id`).
- **Remove** the POS panel and the Operators panel (they move to Branch).

### Branch show page (`branches/show.blade.php`)
- **Receives** the POS panel (device-secret flash, create/delete terminal,
  regenerate secret) — now scoped to `branch_id`.
- **Receives** the Operators panel (assign/remove operator) — now writing
  `operator_branches`.

### PosController
- `create` reads `branch_id` (query param) instead of `store_id`; store the POS
  with `branch_id` and `store_id = branch.store_id`.

### StoresController
- Drop `assignOperator` / `removeOperator` and the `assignableUsers` load from
  `show` (those move to `BranchesController`). `show` now loads the store's
  branches.

## Testing

One runnable feature-test check that fails if branch auth breaks:
- Update `tests/Feature/PaymentGateway/PosDeviceAuthTest.php` for branch-scoped
  device login (token carries `branch:{hashid}`, shift opens against branch).
- Operator login: assigned-to-branch succeeds and issues a `branch:{hashid}`
  token; not-assigned returns `422`.
- `POST /shifts` with a matching `branch_id` succeeds and records `branch_id` +
  denormalized `store_id`; mismatched branch ability returns `403`.

## Out of scope

- Flutter `apps/pos` changes (follow-up PR).
- `GET /branches` API listing (follow-up, with Flutter).
- Backfilling existing POS/operators/shifts onto branches.
- Normalizing `store_id` away.
- Migrating/renaming the legacy `operator_stores` table.

## Deferred follow-up: branch geofencing

Planned but explicitly out of this spec. A later phase adds a per-branch
location gate: the branch stores `latitude` / `longitude` / a cover radius
(default 20 m, adjustable per branch for large-floor stores), and the operator
app sends its GPS coordinates so the server can reject access when the operator
is off-premise. Open questions parked at brainstorming (enforcement point,
device-login applicability, behavior when coordinates are unset). Known ceiling:
client-reported GPS is spoofable without device attestation.
