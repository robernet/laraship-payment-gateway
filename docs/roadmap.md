# PaymentGateway Delivery Roadmap

Grounded in `backend/CLAUDE.md` (Implementation Guide — Payment Reference), `docs/api-contract.md`, `docs/flutter-conventions.md`, and `apps/pos/CLAUDE.md`. All four files exist and were read in full before drafting this roadmap.

## Conflicts & gaps found in the source docs

These are called out per the instruction to list conflicts rather than guess. None of them block starting Phase 1, but each needs an explicit decision recorded in "Open questions" below before the phase that touches it.

1. **`docs/api-contract.md` has no PaymentGateway resources yet.** It contains only the generic `### <Resource>` template block — no `Store`, `Issuer`, `PaymentReference`, `Transaction`, or `Shift` entries exist. This isn't a contradiction, but it means Phase 1 is the first time the contract's own rule ("every field the client reads MUST be documented here before it ships") gets applied to this domain. Treat every phase's contract edits as new content, not updates to existing entries.

2. **Two API surfaces are both called "the API" and it's easy to conflate them.** `backend/CLAUDE.md`'s Implementation Guide describes a `POST /auth/api/auth {User, Pswd} → {Token (JWT)}` flow against `https://qa.gateway.site`, with three downstream methods (`reference`, `barcode`, `payformat`). Read literally, this is a *different* boundary than `docs/api-contract.md`'s Sanctum bearer-token contract, which governs our own Laraship API as consumed by the Flutter POS app and admin panel. But the guide also says this backend "reimplements ClubPago's *Generador de Referencias*" — which could mean either (a) we are a client calling an external/upstream gateway at that JWT-authenticated URL to obtain barcodes/pay-formats/CLABE, or (b) we *are* the gateway, and that JWT/User-Pswd wire format is what issuers integrate against externally, while Sanctum is strictly for our own POS/admin clients. The roadmap below assumes **(a)**: our backend is a client of an external Reference Generator service for barcode/pay-format/CLABE/card-link generation, while POS and admin talk to our own Sanctum-secured API per the contract. This is flagged as **Blocker #1** — see Open Questions.

3. **Where SUB_ID (the issuer identifier) is stored is stated twice, inconsistently.** `backend/CLAUDE.md` says "First 6 digits (777 + SUB_ID) identify the issuer... **this value** will be stored in the Gateway section of Settings," then two sentences later says "The 777 is a 3 digit gateway identifier that will be stored in the Gateway section of Settings" — leaving it unclear whether SUB_ID is a global Settings value or a per-`Issuer` field. Per-issuer uniqueness is required for the prefix to route correctly, so this roadmap assumes **SUB_ID lives on the `Issuer` model** (assigned at issuer creation, unique, validated) and only the global `777` gateway prefix and the Generator API base URL live in Settings. Flagged as **Blocker #2**.

4. **`apps/pos/ui-catalog.md` does not exist yet.** `apps/pos/ui-kit.md` says it's "inert until" `/ui-catalog pos` is run. `reference/devkit/` is vendored and populated (`lib/config`, `lib/model`, `lib/ui`, `lib/cubit`), but the reuse inventory hasn't been generated. This is a **Phase 1 prerequisite**, not a blocker — run `/ui-catalog pos` before porting any DevKit screen.

5. **No `Corals\Modules\PaymentGateway` module exists yet** (`backend/Corals/modules/` has no PaymentGateway directory), and `packages/core/lib/` only has the generic scaffold (`ApiClient`, `AuthService`, `TokenStore`, `Money`, `Paginated`) — no domain models. Both are expected to be built starting Phase 1, not a gap in documentation, just a statement of current state so the plan doesn't assume existing scaffolding that isn't there.

6. **Corrections to `backend/CLAUDE.md`'s stated conventions, confirmed against the actual vendored framework** (`backend/Corals/core/*`, `backend/vendor/corals/foo` scaffold template):
   - **Transformers extend `Corals\Foundation\Transformers\APIBaseTransformer`**, not `Corals\Foundation\View\Transformers\Transformer` (that class is an unrelated PHP→JS variable-binding helper). `Phase 1` below is corrected.
   - **Hashids go through `Corals\Foundation\Traits\HashTrait`** (models `use HashTrait;`) plus global helpers `hashids_encode()` / `hashids_decode()` — not the `hashids()->encode()/decode()` facade syntax `backend/CLAUDE.md` describes. The trait provides `resolveRouteBinding()`, `getHashedIdAttribute()`, and `findByHash()` already; don't hand-roll route binding.
   - **There's a Service + Presenter + DataTable layer `backend/CLAUDE.md` never mentions.** The real per-resource shape (from the `make:module` scaffold template) is: thin `APIBaseController` methods that delegate to a `{Model}Service` (holds `store()`/`update()`/`destroy()`/`getModelDetails()`), a `{Model}Presenter` (wraps the transformer for the service), and a `{Model}sDataTable` for `index()`. Policies use permission strings shaped `Module::resource.action` (e.g. `PaymentGateway::issuer.view`), checked via `$user->can(...)`, with `BasePolicy::before()` auto-granting admins unless the ability is in `$skippedAbilities`. Form Requests extend `Corals\Foundation\Http\Requests\BaseRequest` and use `$this->isStore()`/`$this->isUpdate()`/`$this->setModel(...)` helpers, not hand-rolled `authorize()` logic. Models extend `Corals\Foundation\Models\BaseModel` (`PresentableTrait`, `LogsActivity`, a `$config` string pointing at a model-config file, `$guarded = ['id']`), and migrations call a shared `commonColumns()` helper (`properties` JSON, `auditable()`, `softDeletes()`, `timestamps()`) rather than defining every column inline. **Phase 1's backend-work list undercounted this — expect one Service + one Presenter + one DataTable per resource in addition to the model/controller/transformer/policy/migration already listed**, which meaningfully increases Phase 1's real size.
   - **Naming collision:** `vendor/corals/payment` (already installed, v10.0.8) has its own `Corals\Modules\Payment\Common\Issuer` class and a `GatewayInterface`/`AbstractGateway`/`GatewayFactory` trio for card-processor abstraction (Omnipay-style). It is conceptually unrelated to this project's `Issuer` (a merchant/emisor Eloquent model), but the name collision is real — namespace carefully, and revisit `AbstractGateway`/`GatewayFactory` before Phase 4 (card link/MSI/AutoPay), since it may already provide reusable plumbing there.
   - **No dynamic module currently exists** in `Corals/modules/` to copy from — the closest real patterns are the *core* modules (`Corals/core/User`, `Settings`, etc.) and the `make:module` scaffold template itself (`vendor/corals/foo/src`, with `Bar`/`Baz` as the two example-resource templates the command renames).
   - **Settings storage is DB-backed**, via the `Settings` facade (`\Settings::get('key', $default)`) backed by `Corals/core/Settings/Models/Setting.php`. Sufficient for Phase 1's read of the `777` gateway prefix; how new Settings *sections* register in the admin UI wasn't investigated (deferred to Phase 3, not a Phase 1 blocker).
   - **No existing Sanctum ability-array usage** anywhere in the codebase (only bare `createToken('Corals-API')` with no abilities) — Phase 1's scoped POS tokens (`payment:lookup`, etc.) are this codebase's first use of ability-scoped tokens. Stock Sanctum syntax applies; there's no local convention to match or break.

---

## Phase 1 — Issuer setup → generate a reference → POS collects cash

**Goal:** the thinnest complete path through the domain, with everything downstream (SPEI, card link, MSI, AutoPay, reporting) deferred.

**Vertical slice:** an admin creates an Issuer with a reference layout; the platform generates a valid Reference (Mod10 check digit, issuer-defined length, "online" integration mode — customer id only, no embedded amount/date); a POS operator looks up that reference, sees an amount, and collects cash against it, producing a settled `Transaction` tied to an open `Shift`.

**Backend work:**
- Scaffold the module: `php artisan make:module PaymentGateway Store --no-interaction` (adjust main model per Boost's `make:module --help`), producing `Corals/modules/PaymentGateway/` with `routes/`, `Models/`, `Http/Controllers/`, `Transformers/`, `Policies/`, `database/migrations/`, `module.json`.
- Migrations (BIGINT PK + Hashids, no UUID): `stores`, `issuers` (includes `sub_id` unsigned integer, unique, 3-digit range 0–999; `reference_layout` JSON describing field lengths per the Implementation Guide), `payment_references` (`issuer_id` FK, `reference` string unique, `integration_mode` enum `online`|`batch`, `status`, nullable `amount_minor`, nullable `currency`, nullable `due_date`), `transactions` (`payment_reference_id` FK, `shift_id` FK, `amount_minor`, `currency`, `collected_at`, `status`), `shifts` (`store_id` FK, `operator_id` FK to `users`, `opened_at`, `closed_at`, nullable).
- Models: `Store`, `Issuer`, `PaymentReference`, `Transaction`, `Shift` under `Corals\Modules\PaymentGateway\Models`, each with `resolveRouteBinding()` decoding the Hashid to the integer PK (follow the pattern from an existing Corals core module — check `Corals/core/User/Models/User.php` for the exact convention before writing these).
- `ReferenceGeneratorService` (plain service class, not a controller): builds `PREFIX(777, from Settings) + SUB_ID(issuer.sub_id, zero-padded 3) + IDENTIFIER(customer id, zero-padded to issuer.reference_layout length) + DV(1)`, computes DV via Mod10/Luhn over the preceding digits. Unit-testable in isolation — no HTTP, no external gateway call in this phase.
- Form Requests: `StoreIssuerRequest`, `GenerateReferenceRequest` (customer id required; amount/due-date fields present but rejected in this phase — `online` mode only), `CollectPaymentRequest` (amount, store id).
- Controllers extending `Corals\Foundation\Http\Controllers\APIBaseController`: `IssuerController` (admin-facing CRUD, `BaseController`-based for the Vue admin, not the API — confirm which base class Corals uses for admin-only resource controllers before writing), `PaymentReferenceController@store` (generate), `PaymentReferenceController@show` (POS lookup by reference string, not Hashid — this is the one endpoint where the raw Reference string is the lookup key, per the "NOTE" in `backend/CLAUDE.md` that the Reference is a domain identifier, not the API `id`), `TransactionController@store` (POS collect), `ShiftController@store`/`@update` (open/close).
- Fractal Transformers: `IssuerTransformer`, `PaymentReferenceTransformer` (emits Hashid `id` **and** the `reference` field side by side — never conflate them), `TransactionTransformer`, `ShiftTransformer`.
- Policies: `IssuerPolicy` (admin-only), `PaymentReferencePolicy` (POS can view/collect only references belonging to its scoped store's issuers — confirm store↔issuer relationship before writing the policy), `ShiftPolicy` (an operator can only manage their own shift).
- Auth: issue Sanctum tokens with the exact abilities already specified in `apps/pos/CLAUDE.md` — `payment:lookup`, `payment:collect`, `transaction:read-own`, `shift:manage` — scoped to a single store at token creation (store id embedded some way the controller can check, e.g. token `name` or a `store_id` ability-adjacent claim — decide the mechanism when writing the login endpoint, since Sanctum abilities alone don't carry arbitrary data).

**API contract additions** (new entries in `docs/api-contract.md`, following the `<Resource>` template):
- `POST /api/v1/auth/login` — operator login, returns Sanctum token scoped as above (if not already documented elsewhere as a generic auth endpoint — check before duplicating).
- `Issuer`: `GET/POST /api/v1/issuers`, `GET/PATCH/DELETE /api/v1/issuers/{hashid}`. Fields: `id` (hashid), `name`, `sub_id` (int, 3-digit), `reference_layout` (object).
- `PaymentReference`: `POST /api/v1/payment-references` (generate), `GET /api/v1/payment-references/lookup/{reference}` (POS lookup by the raw Reference string — document explicitly that this one route takes the domain Reference, not a Hashid). Fields: `id` (hashid), `reference` (string), `issuer_id` (hashid), `integration_mode`, `status`, `amount` (minor units, nullable), `currency`, `due_date` (nullable).
- `Transaction`: `POST /api/v1/transactions`. Fields: `id` (hashid), `payment_reference_id` (hashid), `shift_id` (hashid), `amount`, `currency`, `collected_at`, `status`.
- `Shift`: `POST /api/v1/shifts` (open), `PATCH /api/v1/shifts/{hashid}` (close). Fields: `id` (hashid), `store_id` (hashid), `operator_id` (hashid), `opened_at`, `closed_at`.

**packages/core additions:**
- Freezed models (`id` as `String`): `Issuer`, `PaymentReference`, `Transaction`, `Shift`, mirroring the contract fields exactly — no field the contract doesn't define.
- `PaymentGatewayService` (Dio, using `ApiClient`): `lookupReference(String reference)`, `collectPayment(...)`, `openShift(...)`, `closeShift(...)`.
- Run `dart run build_runner build --delete-conflicting-outputs` after adding the freezed models.

**pos app work:**
- Run `/ui-catalog pos` first (prerequisite — currently missing per gap #4 above).
- Screens, ported from `reference/devkit/lib/ui` per the borrow-don't-import rule: operator login, reference entry/lookup, amount-due display, cash collect & confirm, receipt/confirmation, shift open/close.
- Riverpod providers wrapping `PaymentGatewayService`: `referenceLookupProvider`, `collectPaymentProvider`, `shiftProvider`.
- Structure: `lib/features/payment_reference/{data,domain,presentation}`, `lib/features/shift/{data,domain,presentation}`, per `docs/flutter-conventions.md`.

**Dependencies:** none — this is the first phase.

**Verification:**
- Backend: PHPUnit feature test generating a reference for a known issuer/customer id and asserting the Mod10 check digit by hand-computed expectation; feature test for the full collect flow (login → lookup → collect) asserting a `Transaction` row and correct Sanctum ability enforcement (a token without `payment:collect` gets `403`).
- pos: `flutter test` widget test for the collect-confirm screen; manual check — run the pos app against the local API, generate a reference via `tinker` or an admin action, look it up and collect cash in the app, confirm the `Transaction` appears in the database.

---

## Phase 2 — Batch integration mode (embedded amount + due date)

**Goal:** support issuers whose references must carry the exact amount and/or due date for offline validation at retail chains.

**Vertical slice:** an issuer configured for `batch` mode generates a reference with amount and due date embedded; POS collection now enforces exact-amount match (or accepts partial/overdue payment when those fields are omitted, per issuer config) instead of accepting any amount.

**Backend work:**
- Extend `ReferenceGeneratorService` to embed amount (integer minor units) and due date into the identifier segment per `issuer.reference_layout`, still zero-padded to the issuer's defined total length.
- Validation rule in `CollectPaymentRequest`/`TransactionController`: if `payment_references.amount_minor` is set, collected amount must match exactly; if `due_date` is set and passed, reject (or flag) per issuer's late-payment policy (a new `issuer.reject_late_payment` boolean — add via migration).
- Extend `PaymentReferencePolicy` if late/partial rules need to be issuer-configurable per-request rather than global.

**API contract additions:**
- `PaymentReference` fields already added in Phase 1 (`amount`, `due_date`) become populated instead of always-null; document the validation behavior (exact-match vs partial/overdue) as prose under the resource block, since it's business logic the client needs to know, not a new field.
- `Issuer` gains `reject_late_payment` (boolean).

**packages/core additions:**
- Update `Issuer` and `PaymentReference` freezed models for the new field; no new service methods needed (same endpoints, richer payloads).

**pos app work:**
- Amount-due screen must handle three states: exact amount required (input locked), no amount (operator enters cash amount, partial allowed), overdue (blocked or warned, per contract's documented behavior).

**Dependencies:** Phase 1 (reference generation, collection flow, contract entries for `PaymentReference` and `Issuer` must exist first).

**Verification:**
- Backend: feature tests for exact-match rejection (wrong amount → `422`), partial payment acceptance when amount omitted, and overdue rejection when due date passed and `reject_late_payment` is true.
- pos: manual check — generate a batch-mode reference with a fixed amount, confirm the collect screen locks the amount field and rejects a mismatched entry.

---

## Phase 3 — External Reference Generator integration (barcode, pay-format, CLABE)

**Goal:** resolve Blocker #1 and wire the actual outbound integration for barcode images and payment-slip PDFs.

**Vertical slice:** generating a reference optionally also fetches a barcode PNG and pay-format PDF from the external Generator API, and stores their URLs on the `PaymentReference`, viewable in the admin panel and (as links) in the POS receipt screen.

**Backend work:**
- `GeneratorApiClient` (Guzzle-based, config-driven from Settings' Gateway section — base URL, credentials): implements the `POST /auth/api/auth` JWT login (cache the token until `Expiration`, re-auth on `401`), then the three methods (`reference`, `barcode`, `payformat`) against the same request body described in `backend/CLAUDE.md`.
- Add `barcode_url`, `pay_format_url`, `folio` columns to `payment_references` (migration).
- Wrap the external call in a queued job (`GenerateReferenceArtifactsJob`) so reference creation doesn't block on the external API's SLA (target 99% uptime, ≤5s/call, per the doc — still worth decoupling); on failure, retry per Laravel's queue backoff and surface a `status` of `artifacts_pending` vs `artifacts_ready` on the resource.
- `RequestClabe` flag on generation: when true, capture and store the returned `Clabe` for SPEI display.

**API contract additions:**
- `PaymentReference` gains `barcode_url`, `pay_format_url`, `folio`, `clabe` (nullable), `artifacts_status` (`pending`|`ready`|`failed`).

**packages/core additions:**
- Update `PaymentReference` freezed model with the new fields; no new network calls from the client — the client only ever talks to our own API, never the external gateway directly.

**pos app work:**
- Receipt/confirmation screen displays barcode/pay-format links when `artifacts_status == ready`; shows a "generating…" state otherwise (poll or accept eventual consistency — decide polling interval when implementing).

**Dependencies:** Phase 1 (needs `PaymentReference` to exist). Independent of Phase 2 (can be built in parallel if resourcing allows, but sequenced after for a single-threaded team since it resolves Blocker #1, which is more foundational).

**Verification:**
- Backend: HTTP-mocked feature test for `GeneratorApiClient` (fake the JWT login + three endpoints), assert retry-on-401 re-authenticates, assert queued job updates `artifacts_status`.
- Manual check: point at the real QA gateway (`https://qa.gateway.site`) with test credentials, generate a reference, confirm a real barcode PNG URL resolves.

---

## Phase 4 — Card link, MSI, AutoPay

**Goal:** the remaining card-based payment options from the Implementation Guide.

**Vertical slice:** an issuer can request a card-payment link (`RequestPayTD`) with optional interest-free months (`RequestMSI`) or recurring AutoPay (`RequestTDAutoPay`), and the resulting card transaction settles as a `Transaction` (likely via webhook, not POS cash collection).

**Backend work:**
- Extend `GenerateReferenceRequest`/`ReferenceGeneratorService` call to the external API with `RequestPayTD`, `RequestMSI` (csv 3/6/9/12, per-period minimum validation — needs a minimum-amount table per MSI period, source TBD), `RequestTDAutoPay` + `PaymentNumber`/`Paymentfrequency` (required together, validate as a pair in the Form Request).
- Webhook endpoint (new, unauthenticated but signature-verified — verification mechanism not specified anywhere in the read docs, flag as open question) to receive card-settlement callbacks and create/update `Transaction` records without POS involvement.
- AutoPay retry/cancellation state machine on `Transaction` (3 retries, 48h apart, cancellable) — likely a new `autopay_schedules` table rather than overloading `transactions`.

**API contract additions:**
- `PaymentReference` gains `pay_td_url`, `msi_months` (nullable int), `autopay_enabled` (bool), `autopay_payment_number`, `autopay_frequency_days`.
- New `AutopaySchedule` resource block: status, next charge date, retry count.

**packages/core / pos app work:**
- Admin-only surface mostly (card link display, AutoPay schedule status) — pos app scope here is minimal-to-none unless the operator needs to see "this reference is card-linked" as context; confirm with product before building POS UI for this phase.

**Dependencies:** Phase 3 (needs `GeneratorApiClient` and the artifacts pipeline).

**Verification:**
- Backend: feature tests for MSI minimum-amount validation, AutoPay pair-field validation, webhook signature rejection (once the mechanism is decided), retry scheduling logic.

---

## Phase 5 — Admin reporting & shift reconciliation

**Goal:** the operational surface that makes the platform usable day-to-day for issuers and store managers, not just capable of processing one payment.

**Vertical slice:** an admin views issuer-level and store-level transaction reports, and shift reconciliation (expected vs. collected cash) is computed and displayed at shift close.

**Backend work:**
- `BaseDataTable`-derived list views for `PaymentReference`, `Transaction`, `Shift` (Yajra DataTables per Laraship convention).
- Reconciliation calculation on `ShiftController@update` (close): sum of `transactions` in the shift window vs. a reported cash-count field (new `shifts.counted_amount_minor` column) with a `discrepancy_minor` computed field.
- Reporting queries (issuer totals, store totals, date-range filters) — likely a `ReportController` or extending existing Corals reporting patterns if the framework has one (check `Corals/core` for an existing reporting module before building a bespoke one).

**API contract additions:**
- `Shift` gains `counted_amount_minor`, `discrepancy_minor`.
- New read-only report endpoints, shape TBD until the DataTable/report design is settled — do not guess field names here; write them when the admin UI work starts.

**packages/core / pos app work:**
- pos: shift-close screen now prompts for counted cash and displays the discrepancy.

**Dependencies:** Phase 1 (shifts/transactions must exist). Can start once Phase 1 ships; doesn't strictly need Phases 2–4, but is sequenced last because it's the least urgent for proving the core payment flow works.

**Verification:**
- Backend: feature test asserting discrepancy calculation for a shift with known transactions and a mismatched counted amount.
- Manual check: close a shift in the pos app with a deliberately wrong cash count, confirm the admin panel shows the correct discrepancy.

---

## Open questions

1. **Blocker #1 — RESOLVED (Phase 3).** We *are* the gateway: the Reference Generator is a service offered directly to Issuers (via API and a web admin page), plus a separate admin-facing web page for platform staff. Phase 3 built the Sanctum-based issuer-auth path (a `paymentgateway_issuer_users` pivot scoping which issuers a User may generate references for — mirrors the Phase 1 `operator_stores` pattern) and the admin Blade page. **Left open, not built:**
   - **Legacy JWT/User-Pswd wire format** (`POST /auth/api/auth` etc., matching `backend/CLAUDE.md`'s Generator API doc) for existing ClubPago-integrated issuers to point at us without changing their integration code. Not built — no confirmed existing issuers to migrate yet; revisit if/when one exists.
   - **CLABE generation.** The original plan assumed an external gateway would return a real bank-issued CLABE for SPEI. Now that we're the gateway, generating a *real* CLABE requires actual banking-rail/SPEI partner integration we don't have — deliberately not faked with a synthetic value (a money/security-path shortcut is not something to fake). Needs a real decision: partner integration, or drop CLABE/SPEI from scope entirely.
   - Async `artifacts_status` (`pending`/`ready`/`failed`) from the original Phase 3 plan was dropped — barcode (Code 128 PNG, via `picqer/php-barcode-generator`) and pay-format (PDF, via already-installed `barryvdh/laravel-dompdf`) now generate synchronously in-request, since there's no external SLA/latency to hide behind a queue anymore.
2. **Blocker #2 — Where does `sub_id` live?** Confirmed assumption in this roadmap: per-`Issuer` field, not global Settings. Needs sign-off since `backend/CLAUDE.md`'s wording supports either reading.
3. **POS store-scoping mechanism for Sanctum tokens.** Abilities (`payment:lookup`, etc.) are specified, but Sanctum abilities don't carry a `store_id`. Decide the mechanism (custom token attribute, a dedicated `operator_store_assignments` table checked in policies, etc.) before writing the Phase 1 login endpoint.
4. **Phase 4 — SCAFFOLD ONLY, no live gateway wiring.** Built: `pay_td_url`/`autopay_enabled`/`autopay_payment_number`/`autopay_frequency_days` columns on `payment_references`, a new `paymentgateway_autopay_schedules` table (status, next charge date, retry count), and request validation for the AutoPay pair. **Not built, and needs a real decision before wiring anything live:**
   - **Which processor.** `corals/payment-stripe` is already installed and real (`Gateway.php`, webhook signature middleware, subscription job handlers) — usable for card-link + AutoPay. A MercadoPago module also exists, built for a different project (`laraship-prepagomart/Corals/modules/Payment/MercadoPago.zip`), not yet brought into this repo. Neither is wired here yet.
   - **MSI dropped entirely, deliberately.** Not a Stripe feature (Mexican-market/acquiring-bank specific); no `msi_months` field was added. Revisit only alongside a Mexican-specific processor.
   - **Webhook signature verification** for card settlements — if Stripe is chosen, `corals/payment-stripe`'s own `StripeVerifySignature` middleware likely covers this already; still needs confirming/wiring, not designing from scratch.
6. **DevKit license** — `apps/pos/ui-kit.md` has an unfilled placeholder: "License: `<confirm the license covers this app's commercial use>`." Resolve before shipping any pos build derived from ported DevKit screens.

## Suggested first phase to start on

**Phase 1.** It's the only phase with zero unresolved blockers standing directly in its path (Blockers #1 and #2 affect Phase 3 and the `Issuer` schema respectively — #2 needs a quick decision before writing the `issuers` migration, but it's a five-minute call, not a design exercise). Start with the `/ui-catalog pos` prerequisite and the module scaffold in parallel, since neither blocks the other.
