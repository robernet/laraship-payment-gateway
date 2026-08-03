# Invoice-Based Payment Reference Generation (Admin Panel)

Status: implemented, then superseded on the API-scope point. The "Scope"
section below says `POST /payment-references` keeps accepting `customer_id`
and is untouched by this work — that was true when this spec was written,
but a later change extended the same invoice_id-based flow to the public
API too: `POST /payment-references` now requires `invoice_id` (no more
`customer_id`/`amount`/`currency`/`due_date` in the request body), and a new
`POST/GET /invoices` API surface exists so API-only issuers can create
Invoices without the admin panel. See `docs/api-contract.md` for the
current contract — treat every "API is unchanged" statement below as
historical context for the admin-panel work, not the current contract.

One deviation from the spec below (admin panel, still accurate): the "hide once
generated" and edit-lock rules can't check `Invoice.status` alone (an
invoice-backed reference doesn't flip the invoice to `'paid'` until it's
actually collected) - both now check `Invoice::paymentReference()->exists()`
directly. The edit-lock also had to move from `InvoicePolicy::update()` into
`InvoicesController::edit()`/`update()` - `BasePolicy::before()` short-circuits
the policy for admin-permission holders, so a check placed in the policy
method never ran for them.
Backend: Laraship (`backend/`), module `Corals\Modules\PaymentGateway`.
Builds on `docs/superpowers/specs/2026-08-02-issuer-admin-payment-reference-design.md`
(implemented) — reuses `Issuer::isAdminUser()`/`isAccessibleBy()`/`accessibleBy()`
and the 3-state issuer-picker Blade pattern introduced there.

## Problem

The admin-panel "Generate Payment Reference" form (`payment_references/create.blade.php`)
has a free-text **Customer ID** input, typed by whoever is generating the
reference. It isn't tied to any record — nothing stops a typo, a duplicate,
or an ID that doesn't correspond to any real amount owed. There's no way to
list "what does this customer still owe" before generating a reference for
it.

Goal: give each Issuer an **Invoices** admin page (create/list/view/edit),
and change the reference-generation form to pick an unpaid Invoice instead
of typing a Customer ID. The invoice supplies the reference's identifier,
amount, and due date; the reference stays linked back to it so it can be
marked paid once collected.

## Scope

**Admin panel only, as originally scoped.** `POST /payment-references` (the
public API, `docs/api-contract.md`) was meant to stay unchanged — keeping
`customer_id` exactly as documented then, `invoice_id` staying a purely
internal column never exposed on the API's `PaymentReference` response
shape. **This was superseded** — see the status note at the top of this
file and `docs/api-contract.md` for the current contract, where
`invoice_id` replaced `customer_id` on the public API as well.

## Data model

### `Invoice`

New table `paymentgateway_invoices`, new model
`Corals\Modules\PaymentGateway\Models\Invoice` — same shape/conventions as
`Issuer`/`PaymentReference` (auditable, soft-deletes, timestamps):

- `id` (bigint, PK)
- `issuer_id` (FK -> `paymentgateway_issuers`, `constrained()`)
- `customer_id` (string) - who the invoice is for; record-keeping only, no
  longer typed at reference-generation time
- `amount_minor` (int) - amount owed, minor units
- `currency` (string)
- `due_date` (date)
- `description` (text, nullable) - free-text memo
- `status` (string: `'unpaid'` | `'paid'`) - plain-string convention,
  matching `PaymentReference.status`

```php
public function issuer()
{
    return $this->belongsTo(Issuer::class, 'issuer_id');
}

public function paymentReference()
{
    return $this->hasOne(PaymentReference::class, 'invoice_id');
}
```

### `PaymentReference`

One new column via migration: `invoice_id` (bigint, nullable, indexed,
`constrained()` onto `paymentgateway_invoices`). Nullable because
API-generated references never have one.

```php
// Models/PaymentReference.php
public function invoice()
{
    return $this->belongsTo(Invoice::class, 'invoice_id');
}
```

## Invoices admin CRUD

Mirrors the `Issuer` admin CRUD's file layout and the authorization
established for Payment References in the prior spec - no new auth
primitives:

- `Http/Controllers/InvoicesController.php` - `index`, `create`, `store`,
  `show`, `edit`, `update`. **No `destroy`** - financial records aren't
  deleted from this screen.
- `Http/Requests/InvoiceRequest.php` - `setModel(Invoice::class)` +
  `isAuthorized()`, following `IssuerRequest`'s shape. Rules: `issuer_id`
  required, `customer_id` required string, `amount_input` (decimal, same
  `validationData()` conversion pattern as `PaymentReferenceRequest`) ->
  `amount_minor`, `currency` required, `due_date` required date,
  `description` nullable string.
- `DataTables/InvoicesDataTable.php` - `query()` scoped like
  `PaymentReferencesDataTable`: `Issuer::isAdminUser($user)` sees
  everything, otherwise `whereIn('issuer_id', Issuer::accessibleBy($user)->pluck('id'))`.
- `resources/views/invoices/{create_edit,show,index}.blade.php`.
- `Route::resource('invoices', 'InvoicesController')->except(['destroy'])`
  in `routes/web.php`.
- Breadcrumbs (`paymentgateway_invoices`, `_create_edit`, `_show`) in
  `routes/paymentgateway_breadcrumbs.php`.
- `'invoice' => ['presenter' => InvoicePresenter::class, 'resource_url' => 'invoices']`
  block in `config/paymentgateway.php`.
- `'invoice'` added to `PaymentGatewayPermissionsDatabaseSeeder`'s `$models`
  array, so `PaymentGateway::invoice.{view,create,update,delete,restore,hardDelete}`
  get seeded like every other model's permissions.

**Policy** (`Policies/InvoicePolicy.php`), same shape as
`PaymentReferencePolicy`:

```php
public function view(User $user)
{
    return $user->can('PaymentGateway::invoice.view')
        || Issuer::accessibleBy($user)->exists();
}

public function create(User $user)
{
    return $user->can('PaymentGateway::invoice.create')
        || Issuer::accessibleBy($user)->exists();
}

public function update(User $user, Invoice $invoice)
{
    if ($invoice->paymentReference()->exists()) {
        return false;
    }

    return $user->can('PaymentGateway::invoice.update')
        || $invoice->issuer->isAccessibleBy($user);
}
```

**Issuer field on the Invoice create form** reuses the exact 3-state
picker built for the Payment Reference form in the prior spec (full
`<select>` for admins, scoped `<select>` for multi-issuer users, hidden
auto-fill for single-issuer users) - moved here since the Payment
Reference form no longer needs it (see below).

**Edit lock:** `InvoicesController::edit()`/`update()` (and the policy's
`update()` above) reject once a Payment Reference has been generated for
the invoice (`$invoice->paymentReference()->exists()`) - `403`. Keeps a
referenced invoice's amount/due date from drifting away from what's
already been generated/handed to the customer.

## Payment Reference create-form changes

`resources/views/payment_references/create.blade.php`: the Issuer 3-state
field and the Customer ID text input are both removed. Replaced by one
**Invoice** `<select name="invoice_id" required>`, populated from
`status = 'unpaid'` invoices the user can access
(`Invoice::whereIn('issuer_id', Issuer::accessibleBy($user)->pluck('id'))->where('status', 'unpaid')`).
Grouped by issuer name via `<optgroup>` when the user can see invoices from
more than one issuer (i.e. `Issuer::isAdminUser($user)` or linked to 2+
issuers); a flat list otherwise. No JS/AJAX, consistent with this form's
existing static-fields convention.

Amount, Currency, and Due Date fields become **read-only**, pre-filled
from the invoice's own values (still rendered so the operator can see what
they're generating for) - not submitted as separate editable inputs.

`Http/Controllers/PaymentReferencesController::create()`: query changes
from `Issuer::accessibleBy($user)` to
`Invoice::whereIn('issuer_id', Issuer::accessibleBy($user)->pluck('id'))->where('status', 'unpaid')->with('issuer')->get()`
grouped by issuer for the view.

`Invoice` uses `ApiHashTrait` (same as `Issuer`/`PaymentReference`) even
though this feature is admin-panel-only - it still gets its own
`show`/`edit` routes (`/invoices/{invoice}`), and every other
route-bound model in this module is addressed by hashid; there's no reason
for `Invoice` to be the exception. Its `<select>` option values on the
Payment Reference create form are the invoice's hashid, same as the old
Issuer `<select>` used `getHashedIdAttribute()`.

`store()`: resolves `$invoice = Invoice::findByHash($request->get('invoice_id'))`,
derives `$issuer = $invoice->issuer`, keeps the existing
`abort_if(!$issuer->isAccessibleBy($request->user()), 403, ...)` check, and
calls `generateWithArtifacts()` with:

- identifier param = `(string) $invoice->id` (zero-padded internally by
  `ReferenceGeneratorService::generate()`, exactly as `customer_id` is
  today - no change to that service)
- `amount` = `$invoice->amount_minor`
- `currency` = `$invoice->currency`
- `due_date` = `$invoice->due_date`

Then sets `$paymentReference->invoice_id = $invoice->id` (via the create
array in `PaymentReferenceService::generateWithArtifacts()`, which already
takes a full attribute array).

## Lifecycle: marking an Invoice paid

`Http/Controllers/API/TransactionsController.php:72` is the single
existing point where a `PaymentReference` becomes `status = 'collected'`
(the POS collect flow, `POST /transactions`). Immediately after that
update, if `$paymentReference->invoice_id` is set:

```php
$paymentReference->update(['status' => 'collected']);

if ($paymentReference->invoice_id) {
    $paymentReference->invoice->update(['status' => 'paid']);
}
```

Because the invoice's `amount_minor` is always set on an invoice-backed
reference (auto-filled, never null), `PaymentReference`'s existing
collection rule ("if `amount` is set, the collected amount must match
exactly") already applies unchanged - no partial-payment case to handle
for invoice-backed references.

## Testing

New feature test (mirrors `PaymentReferencesAdminControllerTest`'s
pattern), covering:

1. Admin can create an Invoice for any issuer; issuer-linked non-admin
   user can create one only for their linked issuer(s) (same 3-state
   picker behavior as the Payment Reference form).
2. Invoice create/list/show scoping matches `Issuer::accessibleBy()` -
   an issuer-linked user cannot see or create invoices for an unrelated
   issuer.
3. Payment Reference create page's Invoice dropdown lists only
   `status = 'unpaid'` invoices the user can access, grouped by issuer for
   multi-issuer/admin users.
4. Generating a reference from an invoice: the resulting `PaymentReference`
   has `invoice_id` set, and `amount_minor`/`currency`/`due_date` match the
   invoice's own values exactly.
5. Once a Payment Reference exists for an Invoice, that invoice no longer
   appears in the unpaid dropdown, and `InvoicesController::edit()`/
   `update()` return 403 for it.
6. Collecting a Transaction against an invoice-backed reference (`POST
   /transactions`) flips the linked Invoice's `status` to `'paid'`;
   collecting against a non-invoice (API-generated) reference is
   unaffected (no invoice to update).
7. Superseded — `POST /payment-references` (the API) was originally meant
   to stay untouched (still accepting `customer_id`, producing a reference
   with `invoice_id` null); it was later changed to require `invoice_id`
   the same as the admin panel. See `docs/api-contract.md` and
   `PaymentReferenceValidationTest`/`CollectFlowTest` for the current
   behavior.
