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
