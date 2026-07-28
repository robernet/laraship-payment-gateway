@AGENTS.md

# CLAUDE.md — Laraship project template

> **Placement.** Use as the repository-root `CLAUDE.md` for a Laraship monolith (backend + built-in Vue admin in one repo). For a decoupled client (Flutter / SPA), move this file to `/backend/CLAUDE.md`, keep a thin root `CLAUDE.md` that imports `@AGENTS.md` and `@docs/api-contract.md`, and add `/frontend/CLAUDE.md` for the client.
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
The server-rendered admin UI — **distinct from any decoupled client app**: Vue 2 + Vuex 3, Bootstrap 4 + jQuery 3, laravel-echo + socket.io for realtime, axios for HTTP. Entry points declared in `webpack.mix.js`.

### Identifiers — Hashids (project default)
- Public URLs and every API payload use a **Hashid string** derived from the internal BIGINT primary key. The raw integer PK never crosses the API boundary — not in URLs, payloads, logs, or errors.
- Encode / decode with the framework helper: `hashids()->encode($id)` / `hashids()->decode($hash)`.
- Route binding: decode the hashid to the integer id — via a model `resolveRouteBinding()` override or in the controller — following the pattern in existing modules.
- Transformers emit the hashid as the resource's `id`, never the raw integer.

> **UUID exception.** Switch to a UUID + BIGINT dual key only for a module that needs globally-unique, client- or offline-generatable ids (sync, idempotent creates, multi-source writes). That is a per-module exception, not the template default.

### API conventions
- Fractal transformers for output; API versioning under `/api/v1` (follow existing routes if they differ).
- Auth: **Laravel Sanctum** personal access tokens — see `docs/api-contract.md` when a decoupled client exists.

---

## Project-specific — fill in per project
- **Project:** <name>
- **Primary module(s):** `Corals\Modules\<Module>`  *(confirm namespace casing against the `Corals/modules/` directory)*
- **Main models:** <Model, Model>
- **Decoupled client?** <none | Flutter | SPA> -> if yes, this file lives at `/backend/CLAUDE.md` and the API contract governs the boundary.
- **Identifier scheme:** Hashids (default) — list any module that opts into the UUID exception.
