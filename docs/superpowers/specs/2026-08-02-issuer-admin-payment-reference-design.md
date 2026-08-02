# Issuer & Admin Payment Reference Generation

Status: implemented (commit e1aa255). One deviation from the spec below: the
blanket `Administrations::admin.paymentgateway` permission (this module's
existing admin pattern - see `IssuersAdminControllerTest`) is also treated as
admin access, not just `isSuperUser()`/`payment_reference.create` - otherwise
a normal admin with only the blanket permission got an empty issuer list and
a 500 on `create`. See `Issuer::isAdminUser()`.
Backend: Laraship (`backend/`), module `Corals\Modules\PaymentGateway`.

## Problem

`POST /api/v1/payment-references` (the API) already lets a non-admin user
generate a reference for an issuer they're linked to via
`paymentgateway_issuer_users` — that check was added in a prior session
(`Http/Controllers/API/PaymentReferencesController@store`).

The admin-panel web equivalent (`Http/Controllers/PaymentReferencesController`,
`resources/views/payment_references/create.blade.php`) never got the same
treatment:

- `create()`/`store()` are gated by `PaymentReferencePolicy::create()`, which
  checks *only* the admin permission `PaymentGateway::payment_reference.create`
  — an issuer-linked non-admin user gets a flat 403 just loading the page.
- `store()` itself has **no** issuer-link check at all beyond that policy gate
  — today, any user who *does* have the admin permission can submit for any
  issuer, but there is no server-side proof of "this user may act for this
  issuer" independent of full admin rights.
- The form always lists every issuer, has no AutoPay fields (fine — Phase 4 is
  scaffold-only, intentionally excluded here), asks for `amount` in raw minor
  units, and displays no validation errors on a failed submit.

Goal: let both admins and issuer-linked users reach this page and generate a
reference, with the *same* authorization rule enforced in both the API and
the web controller — one rule, not two that can drift apart.

## Shared authorization: `Issuer::isAccessibleBy()` / `Issuer::accessibleBy()`

Extract the API controller's existing inline check into two methods on the
`Issuer` model (`Corals/modules/PaymentGateway/Models/Issuer.php`), so the API
and the web controller call the exact same logic instead of maintaining two
copies:

```php
public function isAccessibleBy(User $user): bool
{
    return isSuperUser($user)
        || $user->hasPermissionTo('PaymentGateway::payment_reference.create')
        || IssuerUser::query()
            ->where('user_id', $user->id)
            ->where('issuer_id', $this->id)
            ->exists();
}

public static function accessibleBy(User $user)
{
    if (isSuperUser($user) || $user->hasPermissionTo('PaymentGateway::payment_reference.create')) {
        return static::query();
    }

    return static::query()->whereIn('id', IssuerUser::query()
        ->where('user_id', $user->id)
        ->pluck('issuer_id'));
}
```

This is a pure behavior-preserving extraction for the API side — `store()`
there changes from its inline `isSuperUser($user) && ... IssuerUser::query()...`
block to `if (!$issuer->isAccessibleBy($user)) { abort_if(...); }`, same
outcome, same 403 message. No change to the API contract or its tests.

Both methods live on `Issuer` (not `PaymentReferenceService`) because they're
a predicate about an issuer/user pair, reusable anywhere an issuer needs
access-checking (this feature's `create()`, `index()`, `show()`, and the
existing `store()`), not lifecycle logic tied to generating a reference.

## Web-side changes

### Policy — `PaymentReferencePolicy`

- `create($user)`: `$user->can('PaymentGateway::payment_reference.create') || Issuer::accessibleBy($user)->exists()`.
- `view($user)`: same OR condition (currently admin-permission-or-`payment:lookup`-ability
  only — POS's `payment:lookup` ability stays as-is, this just adds the issuer-link path
  for admin-panel access).

This only affects the web admin-panel path. The API's `PaymentReferenceRequest::authorize()`
already returns `true` unconditionally for `isStore()` and defers entirely to
the controller — untouched, no API behavior change.

### Controller — `Http/Controllers/PaymentReferencesController`

- `create()`: build `$issuers = Issuer::accessibleBy($user)->orderBy('name')->get()`.
  Pass an `$isAdmin` flag (or just check `$issuers->count() > 1 || isSuperUser/hasPermission`)
  to the view so it knows whether to show the full picker, a scoped picker, or
  an auto-selected single issuer.
- `store()`: after resolving `$issuer` via `findByHash`, add
  `abort_if(!$issuer->isAccessibleBy($request->user()), 403, 'This user is not linked to the requested issuer.')`
  — same message as the API, for consistency.
  Also: convert the form's decimal amount to minor units here (see Form
  section) before calling `generateWithArtifacts()`.
- `index()`: inject the DataTable's query with
  `->whereIn('issuer_id', Issuer::accessibleBy($user)->pluck('id'))` for
  non-admin users (admins see everything, unchanged).
- `show()`: `abort_if(!$paymentReference->issuer->isAccessibleBy($user), 403)`.

There is no API list/show endpoint for `PaymentReference` to keep aligned
with here (the contract only has `POST /payment-references` and
`GET /payment-references/lookup/{reference}`, which has its own unrelated
`payment:lookup`-ability check) — `index`/`show` scoping is purely a web-side
concern.

### Form — `resources/views/payment_references/create.blade.php`

- **Issuer field**: three states, computed in the controller and passed to the view.
  - Admin: unchanged full `<select>` of all issuers.
  - Issuer user linked to exactly one issuer: hidden `<input>` with that
    issuer's hashid, plus a plain "Generating for: {name}" line — no dropdown.
  - Issuer user linked to 2+: `<select>` scoped to just `$issuers` (already
    filtered by `accessibleBy()` in the controller — same markup as admin's,
    just a shorter option list).
- **Amount**: change the input to a decimal field (`type="number" step="0.01"`,
  name `amount_input`, placeholder "150.00"). Do **not** rename or repurpose
  the `amount` field the shared `PaymentReferenceRequest`/API expects in minor
  units — add a small `validationData()` override on `PaymentReferenceRequest`:
  if `amount_input` is present and `amount` is not, compute
  `amount = (int) round($this->input('amount_input') * 100)` before validation
  runs. The API never sends `amount_input`, so this is additive and inert for
  API requests — same target field, same `integer|min:1` rule, same downstream
  handling either way. No JS.
- **Currency**: add `value="MXN"` to the existing free-text input. Purely a
  pre-filled default in the HTML — the API's `required_with:amount` rule is
  unchanged, since the web form still submits an explicit value.
- **No AutoPay fields** — intentionally excluded (Phase 4 scaffold, nothing
  charges yet); the shared request already treats them as optional, so
  omitting them from this form is fully compatible with the API accepting
  them from other clients.
- **No dynamic/AJAX layout fetching** — all fields stay static; a batch-mode
  issuer's missing `amount`/`due_date` still surfaces as the existing 422-style
  validation error from `PaymentReferenceRequest::withValidator()`.
- **Add visible error display**: an `@if ($errors->any())` block at the top of
  the form listing `$errors->all()`, since none exists today and the "rely on
  server validation" approach requires the user to actually see the error.

## Testing

New feature test (mirrors `IssuersAdminControllerTest`'s auth-setup pattern),
covering:

1. Admin sees the full issuer picker on `create`, can generate for any issuer.
2. User linked to exactly one issuer: no picker shown, `store()` succeeds
   for that issuer via the hidden `issuer_id` field the form auto-fills.
3. User linked to two issuers: scoped picker shown (contains both, excludes
   a third unrelated issuer), can generate for either.
4. User with no admin permission and no issuer link: 403 on `GET create` and
   `POST store`.
5. `index`/`show`: an issuer user cannot see another issuer's reference (403
   on `show`, absent from their `index` listing).
6. Decimal amount conversion: submitting `amount_input=150.00` results in a
   `PaymentReference` with `amount_minor = 15000`.
7. Missing-amount validation error is visible in the rendered `create` page
   HTML after a failed submit for a batch-mode issuer.

Existing tests unaffected: `PaymentReferenceValidationTest` (API-only,
untouched request shape), `CollectFlowTest`, `ShiftReconciliationTest` — none
of these touch the web `PaymentReferencesController` or submit `amount_input`.
