# Payment Reference Generator — inline invoice creation + AutoPay fields

## Problem

The admin "Reference Generator" (`GET/POST /payment-references/create`) only lets an
operator pick from a list of **existing** unpaid invoices. To generate a reference
for a brand-new customer, the operator must first visit the separate Invoice form
(`/invoices/create`), save it, then come back to the Reference Generator and find it
in the dropdown — two pages, two submits, for what is conceptually one action:
"generate a reference for this payment."

Separately, `PaymentReferenceRequest` already validates `autopay_enabled` /
`autopay_payment_number` / `autopay_frequency_days`, and
`PaymentReferenceService::generateWithArtifacts()` already accepts and persists them
(creating an `AutopaySchedule` row), but:
- the admin form never renders inputs for them, and
- `PaymentReferencesController::store()` never passes them through to the service —
  so today they are silently dropped even if somehow submitted.

## Goal

One page, one submit: the Reference Generator lets the operator either pick an
existing unpaid invoice or fill in a new one inline, optionally configure AutoPay,
and on submit the invoice (if new) and the reference are created together. The
result page (already correct, unchanged) shows the barcode image and PDF
download link.

Out of scope: any change to reference-digit derivation (issuer prefix, identifier,
amount, due date, check digit) — all of that continues to come from the Issuer's
`reference_layout` and the linked Invoice, never from a manually typed field.

## Design

### 1. Request validation — `PaymentReferenceRequest`

Add an `invoice_mode` input, `existing` (default) or `new`:

- `invoice_mode=existing` (today's behavior, unchanged): `invoice_id` required.
- `invoice_mode=new`: `issuer_id`, `customer_id`, `amount_input` (converted to
  `amount_minor` the same way `InvoiceRequest::validationData()` does), `currency`,
  `due_date` required; `description` nullable. `invoice_id` is not required in this
  mode.
- AutoPay rules (`autopay_enabled`, `autopay_payment_number`,
  `autopay_frequency_days`) are unchanged and apply regardless of `invoice_mode`.

### 2. Controller — `PaymentReferencesController`

- Inject `InvoiceService` alongside the existing `PaymentReferenceService`.
- `create()`: also loads `$issuers = Issuer::accessibleBy($user)->orderBy('name')->get()`
  and `$isAdmin = Issuer::isAdminUser($user)`, passed to the view for the "new
  invoice" sub-form (mirrors `InvoicesController::create()`).
- `store()`:
  - If `invoice_mode === 'new'`: resolve `Issuer::findByHash($request->get('issuer_id'))`,
    `abort_if` missing (404) or not `isAccessibleBy($request->user())` (403), then
    `$invoice = $this->invoiceService->store($request, Invoice::class, ['issuer_id' => $issuer->id]);`
    (same call `InvoicesController::store()` already makes).
  - If `invoice_mode === 'existing'`: today's `Invoice::findByHash($request->get('invoice_id'))`
    path, unchanged.
  - From there, unchanged: `abort_if(!$issuer->isAccessibleBy(...))`,
    `abort_if($invoice->paymentReference()->exists(), 422, ...)`, then
    `generateWithArtifacts()` — now called **with** the autopay args pulled from
    the request (`autopay_enabled` boolean, `autopay_payment_number`,
    `autopay_frequency_days`), fixing the current silent drop.

### 3. View — `resources/views/payment_references/create.blade.php`

- Two radio inputs, `invoice_mode`, `existing` (checked by default) / `new`.
- Plain `<script>` (jQuery, matching the rest of the admin theme — no new JS
  dependency) toggles two blocks based on the radio selection, and a third
  toggle for the AutoPay number/frequency inputs based on the `autopay_enabled`
  checkbox. Toggled-off blocks keep their inputs but the fields are only
  `required` when their block is active (set/cleared via the same script), so
  browser-side validation doesn't block submission of the hidden block.
- "Existing invoice" block: today's grouped `<select name="invoice_id">`,
  unchanged.
- "New invoice" block: same fields/layout as `invoices/create_edit.blade.php`
  (`issuer_id` select — hidden+static when only one accessible issuer,
  `customer_id`, `amount_input`, `currency`, `due_date`, `description`).
- AutoPay block (independent of the existing/new toggle, always visible):
  checkbox `autopay_enabled`; checking it reveals `autopay_payment_number` +
  `autopay_frequency_days`.
- New keys in `resources/lang/en/attributes.php` under `payment_reference`:
  `invoice_mode`, `autopay_enabled`, `autopay_payment_number`,
  `autopay_frequency_days`; two label strings for the radio options ("Use
  existing invoice" / "New invoice") added to `resources/lang/en/module.php`.

### 4. Tests — `tests/Feature/PaymentGateway/PaymentReferencesAdminControllerTest.php`

Two additions to the existing suite (same fixtures/helpers already in the file):

- `admin_can_generate_a_reference_from_a_newly_created_invoice_in_one_submit`:
  posts to `/payment-references` with `invoice_mode=new` plus issuer/customer/
  amount/currency/due_date, asserts a `paymentgateway_invoices` row and a
  `paymentgateway_payment_references` row both exist and are linked.
- `admin_form_can_configure_autopay_on_generation`: posts with
  `autopay_enabled=1` + `autopay_payment_number` + `autopay_frequency_days`
  (either invoice mode) and asserts a `paymentgateway_autopay_schedules` row is
  created — this is the regression check for the currently-dropped args.

No changes to `InvoicesAdminControllerTest` — the standalone Invoice
create/edit flow is untouched.

## Non-goals / explicitly not changing

- The `show.blade.php` result page (barcode image + PDF link) — already correct.
- Reference-digit generation, issuer `reference_layout`, Mod10 check digit — all
  untouched, still fully derived server-side.
- The API (`POST /payment-references`) — this is an admin-panel-only UI/flow
  change; the API contract already takes `invoice_id` and the same AutoPay
  fields per `docs/api-contract.md`.
