# API Contract — <project>

Single source of truth for the HTTP boundary between the Laraship backend and any decoupled client (Flutter / SPA). Change this file **first**; both sides follow it. Import it from the root `CLAUDE.md` (`@docs/api-contract.md`) so it is always in context.

## Base
- Base URL from environment. Never hardcode.
- Version prefix: `/api/v1` (follow existing routes if they differ).
- JSON only. Requests send `Accept: application/json`.

## Auth — Laravel Sanctum (token mode)
- Personal access tokens for mobile / SPA clients — not the cookie/SPA-session mode.
- Header: `Authorization: Bearer <plain-text-token>`
- Issue on login via `createToken(name, [abilities])`; scope with abilities; revoke the current token on logout.
- `401` -> missing, expired, or revoked token; client re-authenticates.

## Identifiers — Hashids
- Every resource is addressed by its **Hashid string**, e.g. `/api/v1/<resource>/{hashid}`.
- The backend decodes with `hashids()->decode()` to the internal BIGINT id; the raw integer never appears in URLs, payloads, logs, or errors.
- Payloads expose the hashid as `id`. Foreign keys are exposed as the related resource's hashid (e.g. `owner_id` = the owner's hashid).

## Success envelope
```json
{ "data": { }, "meta": { } }
```
Collections carry pagination in `meta`.

## Error envelope
```json
{ "message": "Human-readable message", "errors": { "field": ["reason"] } }
```
Codes: `422` validation, `401` auth, `403` forbidden, `404` not found, `500` server.

## Pagination (list endpoints)
- Query: `?page=1&per_page=20`
- `meta`: `{ "current_page", "per_page", "total", "last_page" }`

## Conventions
- Dates: ISO 8601, UTC.
- Money: integer **minor units** + `currency` (e.g. `{ "amount": 150000, "currency": "MXN" }`) — never floats.
- Every field the client reads MUST be documented here before it ships.

---

## Example resource — copy this block per resource
### <Resource>
- `GET /<resource>` — list / filter (paginated)
- `GET /<resource>/{hashid}`
- `POST /<resource>` · `PATCH /<resource>/{hashid}` · `DELETE /<resource>/{hashid}`
- Fields (types; ids as hashids; money as minor units):
  - `id` (hashid, string)
  - `<field>` (`<type>`)
  - `<relation>_id` (hashid, string)

---

## PaymentGateway resources (Phase 1 + Phase 2)

### Auth (POS)
- `POST /pos/login` — `{email, password, store_id}` → `{token, abilities, store_id}`. Issues a Sanctum token scoped to one store: abilities `payment:lookup`, `payment:collect`, `transaction:read-own`, `shift:manage`, plus a synthetic `store:{hashid}` ability checked on shift-open. `store_id` must be a store the operator is assigned to (`422` otherwise).

### Issuer
- `GET/POST /issuers` · `GET/PATCH/DELETE /issuers/{hashid}`
- Fields:
  - `id` (hashid, string)
  - `name` (string)
  - `sub_id` (int, 0-999, unique) — issuer identifier embedded in the reference prefix
  - `reference_layout` (object): `identifier_length` (int, required), `amount_length` (int, optional — presence makes this issuer batch-mode for amount), `embed_due_date` (bool, optional — presence makes this issuer batch-mode for due date)
  - `reject_late_payment` (bool) — when true and a reference's `due_date` has passed, collection is rejected (`422`); when false, overdue references remain collectible (due date is informational only)

### PaymentReference
- `POST /payment-references` (generate) — `{issuer_id, customer_id, amount?, currency?, due_date?}`. `amount`/`due_date` are **required** if the issuer's `reference_layout` declares `amount_length`/`embed_due_date` respectively (validated at generation time, not by a client-chosen mode). Synchronously renders the barcode + pay-format artifacts before responding — no polling, no `artifacts_status` field (see Phase 3 note below).
- `GET /payment-references/lookup/{reference}` — POS lookup by the raw Reference **string**, not the hashid.
- Fields:
  - `id` (hashid, string)
  - `reference` (string) — the domain Reference, max 29 digits
  - `issuer_id` (hashid, string)
  - `integration_mode` (`online`|`batch`) — server-derived from the issuer's layout, never client-supplied
  - `status` (`pending`|`collected`)
  - `amount` (int, minor units, nullable)
  - `currency` (string, nullable)
  - `due_date` (date, nullable)
  - `folio` (string) — internally-generated tracking id, format `FOL-YYYYMMDD-XXXXXX`
  - `barcode_url` (string) — public URL to a Code 128 PNG barcode of the reference
  - `pay_format_url` (string) — public URL to a rendered payment-slip PDF
- Collection-time behavior (enforced on `POST /transactions`, not a field): if `amount` is set on the reference, the collected amount must match **exactly** (`422` otherwise); if unset, any positive amount is accepted (partial or full). If `due_date` has passed and the issuer's `reject_late_payment` is true, collection is rejected (`422`).
- **Authorization** (Phase 3): non-admin callers must be linked to the target issuer via `paymentgateway_issuer_users` (`403` otherwise) — we are the Reference Generator service, offered to issuers directly, not just admins. `clabe` and a legacy JWT/User-Pswd issuer-auth surface (for existing ClubPago-integrated issuers) are deliberately **not** implemented yet — see `docs/roadmap.md` for the open questions this raised.

### Transaction
- `POST /transactions` (collect) — `{payment_reference_id, amount, currency}`. The shift is always the requesting operator's own currently-open shift — never client-supplied.
- Fields:
  - `id` (hashid, string)
  - `payment_reference_id` (hashid, string)
  - `shift_id` (hashid, string)
  - `amount` (int, minor units)
  - `currency` (string)
  - `collected_at` (datetime)
  - `status` (string)

### Shift
- `POST /shifts` (open) — `{store_id}`. Requires the operator's token to carry a `store:{hashid}` ability matching this store (`403` otherwise).
- `PATCH /shifts/{hashid}` (close) — only the operator who opened it may close it. Body: `{counted_amount}` (int, minor units, required) — the cash the operator counted; the server computes and stores `discrepancy_minor` against the shift's actual collected total.
- Fields:
  - `id` (hashid, string)
  - `store_id` (hashid, string)
  - `operator_id` (hashid, string)
  - `opened_at` (datetime)
  - `closed_at` (datetime, nullable)
  - `counted_amount_minor` (int, minor units, nullable) — cash counted by the operator at close time, set on `PATCH /shifts/{hashid}`
  - `discrepancy_minor` (int, minor units, nullable) — `counted_amount_minor` minus the sum of the shift's collected transactions; positive = over, negative = short, `0` = exact. Computed server-side, never client-supplied. Assumes all of a shift's transactions share a single currency — nothing currently enforces this, so a mixed-currency shift would sum meaninglessly (pre-existing Phase 2 gap, not fixed here).
