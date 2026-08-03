@AGENTS.md

# CLAUDE.md — PaymentGateway (backend)

> **This is the backend file** of a decoupled monorepo: Laraship API + admin here, the Flutter `pos` app under `apps/pos/`. The API contract at `@docs/api-contract.md` governs the boundary.
>
> **The `@AGENTS.md` import above** pulls in the Laravel Boost baseline (package versions, Boost tools, PHP / Laravel / PHPUnit rules). Boost owns and regenerates that file — never hand-edit it; put durable rules here instead. Anything below this line overrides the baseline on conflict.

---

## Behavioral Guidelines

Rules that govern how Claude approaches every task — biasing toward caution and precision over speed.

### 1. Think Before Coding
Don't assume. Don't hide confusion. Surface tradeoffs. Before implementing: state assumptions explicitly and ask if uncertain; if multiple interpretations exist, present them rather than picking silently; if a simpler approach exists, say so and push back when warranted; if something is unclear, stop and name it.

### 2. Simplicity First
Minimum code that solves the problem — nothing speculative. No features beyond what was asked, no abstractions for single-use code, no unrequested "flexibility," no error handling for impossible scenarios. If 200 lines could be 50, rewrite. Ask: would a senior engineer call this overcomplicated?

### 3. Surgical Changes
Touch only what you must. Don't "improve" adjacent code, refactor what isn't broken, or restyle to taste — match existing style. Remove only the imports, variables, or functions your **own** changes orphaned; flag pre-existing dead code, don't delete it. Every changed line should trace directly to the request.

### 4. Goal-Driven Execution
Define success criteria, loop until verified. Turn tasks into verifiable goals ("add validation" -> write tests for invalid inputs, then make them pass). For multi-step work, state a brief plan with a verify check per step.

**Working when:** diffs carry fewer unnecessary changes, there are fewer rewrites from overcomplication, and clarifying questions come before implementation rather than after mistakes.

---

## About Laraship
Laraship is a modular Laravel platform by Corals for marketplaces, e-commerce, directories, reservations, subscriptions, and classifieds. Modules are pluggable and registered from the database at runtime.

---

## Overrides to the Boost baseline
- **Bundler:** Laraship uses **Laravel Mix (webpack), NOT Vite** — ignore any Vite guidance imported above. Dev: `npm run watch` (or `npm run dev`). Prod: `npm run production`. Compiled assets land in `public/assets/`.
- **Tests use a real database**, not SQLite in-memory — see `phpunit.xml`.

---

## Laraship Commands
- `php artisan corals:install` — interactive install wizard
- `php artisan make:module {ModuleName} {MainModel} [--modal]` — scaffold a module from the Foo template
- `php artisan corals:modules` — module manager (recovery mode, minimal boot)
- Tests: `php artisan test --compact` · single file `… tests/Feature/XTest.php` · filter `… --filter=testName`

---

## Architecture

### Two-tier module system
- **Core modules** (`Corals/core/`) always load via `CoralServiceProvider` -> `FoundationServiceProvider`: Foundation, User, Settings, Theme, Activity, Media, Menu, Utility.
- **Dynamic modules** (`Corals/modules/`) load at runtime via `ModulesServiceProvider`, which reads the `modules` DB table (`enabled=1`) and boots each module's provider. A disabled module loads nothing.
- Each module is self-contained: `routes/`, `resources/views/`, `database/migrations/`, `Models/`, `Http/Controllers/`, `DataTables/`, `Policies/`, `Transformers/`, `module.json`.

### Base classes (extend these — don't reinvent)
- `Corals\Foundation\Http\Controllers\BaseController` — admin controllers (theme, auth middleware, shared view data).
- `Corals\Foundation\Http\Controllers\APIBaseController` / `APIPublicController` — API endpoints (authenticated / public).
- `Corals\Foundation\DataTables\BaseDataTable` — list views (Yajra DataTables).
- `Corals\Foundation\Policies\BasePolicy` — model policies.
- `Corals\Foundation\View\Transformers\Transformer` — League Fractal; API resource output.

### Hook system (WordPress-style — extend without touching core)
```php
Actions::add_action('hook', [$this, 'method'], $priority);
Actions::dispatch('hook', [$arg1, $arg2]);
Filters::add_filter('hook', [$this, 'method'], $priority);
$value = Filters::do_filter('hook', $value, ...$extra);
```

### Theme system
Themes live in `resources/themes/{theme}/`; the `Theme` facade resolves view paths so any view can be overridden; each theme has a `theme.json`. The admin theme is DB-configurable per session.

### Admin / Web frontend (Laraship built-in)
The server-rendered admin UI — **distinct from the decoupled `pos` app**: Vue 2 + Vuex 3, Bootstrap 4 + jQuery 3, laravel-echo + socket.io for realtime, axios for HTTP. Entry points declared in `webpack.mix.js`.

### Identifiers — Hashids (project default)
- Public URLs and every API payload use a **Hashid string** derived from the internal BIGINT primary key. The raw integer PK never crosses the API boundary — not in URLs, payloads, logs, or errors.
- Encode / decode with the framework helper: `hashids()->encode($id)` / `hashids()->decode($hash)`.
- Route binding: decode the hashid to the integer id — via a model `resolveRouteBinding()` override or in the controller — following the pattern in existing modules.
- Transformers emit the hashid as the resource's `id`, never the raw integer.
- NOTE: the **payment Reference** below is a domain identifier (the ClubPago number), NOT the API `id`. API routes still address resources by Hashid; the Reference is a field.

### API conventions
- Fractal transformers for output; API versioning under `/api/v1` (follow existing routes if they differ).
- Auth: **Laravel Sanctum** personal access tokens — see `docs/api-contract.md`.

---

## Project-specific
- **Project:** PaymentGateway
- **Primary module(s):** `Corals\Modules\PaymentGateway`
- **Main models:** Store, Issuer, PaymentReference, Transaction, Shift
- **Decoupled client?** Flutter — the `pos` app (operator terminal). This file governs the API side; `docs/api-contract.md` governs the boundary.
- **Identifier scheme:** Hashids (default) — no UUID exceptions.

---

## Implementation Guide — Payment Reference (ClubPago model)

This backend reimplements ClubPago's *Generador de Referencias* (cash-payment network). An **Issuer** (Emisor — a merchant on the platform) creates payment **References**; the end **Customer** pays them in cash at retail chains (Soriana, Walmart…), by SPEI (CLABE), or by card link. Admin panel manages issuers/references; the `pos` app is a point-of-sale (PDV) collecting against a reference.

**Reference** — numeric string, **max 29 digits**:
`PREFIX(777) + SUB_ID(3 = issuer) + IDENTIFIER(customer/payment id [+ optional amount] [+ optional due date], left-zero-padded to the issuer's defined length) + DV(1)`
- First 6 digits (`777` + SUB_ID) identify the issuer; last digit is the check digit.
- The `777` is the gateway prefix, stored in **Settings** (`PaymentGateway` category, setting code `paymentgateway_id` — seeded by `PaymentGatewaySettingsDatabaseSeeder`, default `000`, edit under Settings > Payment Gateway ID) and read via `\Settings::get('paymentgateway_id', '777')` in `ReferenceGeneratorService::generate()`. The Create/Edit Issuer form (`issuers/create_edit.blade.php`) renders a live sample of the full reference — prefix + sub ID + identifier + optional amount/due-date + Mod10 check digit — as Identifier length/Amount length/Embed due date are edited, so this is the one place to verify the prefix actually in effect.
- **DV = Mod10 / Luhn** over the preceding digits — catches miskeyed manual entry.
- Each issuer **defines its reference layout** (which fields, what lengths) at setup; the platform stores that spec and validates against it.
- **Amount embedded → payment must match exactly; omitted → informational, and partial/overdue payments are accepted.** Same for **due date**: embed only to reject late payment. Amounts are integer **minor units** (152.30 → `15230`), consistent with the API contract's money rule.

**Integration modes.** *Batch*: the reference must embed exact amount + due date for validation. *Online*: the platform validates amount/validity in real time, so the reference can carry just the customer id.

**Generator API** (base QA `https://qa.gateway.site`; auth first, then 3 methods):
- base QA value will be stored in the Gayeway section of the Settings.
- `POST /auth/api/auth` `{User, Pswd}` → `{Message, Token (JWT), Expiration}`. Send `Authorization: Bearer <token>`; `401` on missing/invalid/expired → re-authenticate.
- Methods: **reference**, **barcode** (`/referencegenerator/svc/generator/barcode`, PNG), **pay format** (`/referencegenerator/svc/generator/payformat`, PDF slip). Same request body for all three:
  - required: `Description`, `Amount` (>0), `Account` (customer id)
  - optional: `CustomerEmail`, `CustomerName`, `ExpirationDate` (`null` if unused), `RequestClabe` (SPEI CLABE), `RequestPayTD` (card-payment URL), `RequestMSI` (csv of 3/6/9/12), `RequestTDAutoPay` (+ `PaymentNumber`, `Paymentfrequency` required when true)
  - response: `Reference`, `BarCode` (url), `PayFormat` (url), `Clabe`, `PaymentTD` (url), `Folio`, `Date`, `Message`, `Error`
- SLA target: 99% uptime, ≤5 s per call.

**Card options** (`RequestPayTD`): **MSI** interest-free months 3/6/9/12 (per-period minimums), and **AutoPay** recurring charges — customer must authorize, 3D Secure, N charges every M days (`99` = indefinite), 3 retries 48 h apart, cancellable. Mexico cards only.

**Model mapping.** Issuer = Emisor · PaymentReference = the reference + its layout spec · Transaction = a received/settled payment · Store = PDV · Shift = operator cash session.
