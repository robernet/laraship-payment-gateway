# Payment Reference Generator — Inline Invoice Creation + AutoPay Fields Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an admin generate a Payment Reference from either an existing unpaid Invoice or a brand-new one filled in inline, with optional AutoPay configuration, in a single page/submit — and fix the pre-existing bug where AutoPay fields are validated but never actually passed to `generateWithArtifacts()`.

**Architecture:** `PaymentReferenceRequest` gains an `invoice_mode` (`existing`|`new`) input that branches its validation rules. `PaymentReferencesController::store()` branches on the same field: `new` creates the Invoice first via the existing `InvoiceService`, `existing` looks it up by hash as today; both paths converge into the unchanged `generateWithArtifacts()` call, now actually given the AutoPay args. The Blade view gets a radio toggle between the two invoice blocks and an AutoPay checkbox block, shown/hidden with plain jQuery (already used by the admin theme).

**Tech Stack:** Laravel 11 (PHP 8.3), Blade, jQuery (admin theme), PHPUnit feature tests against a real MySQL database (`LazilyRefreshDatabase`).

## Global Constraints

- Hashid strings for every resource reference in URLs/payloads — never the raw BIGINT PK (per `docs/api-contract.md`).
- Money in integer minor units + currency, never floats.
- No new JS dependency — reuse the jQuery already loaded by the admin theme.
- Follow existing Blade/controller/request conventions in this module exactly (see `invoices/create_edit.blade.php` and `InvoiceRequest` for the patterns being mirrored).
- This is an admin-panel-only change. The API (`POST /payment-references`) is untouched.
- Tests are PHPUnit feature tests using the existing `PaymentReferencesAdminControllerTest` fixtures/helpers (`admin()`, `nonPrivilegedUser()`, `issuer()`, `link()`, `unpaidInvoice()`).

---

### Task 1: Backend — inline invoice creation + AutoPay wiring in `PaymentReferenceRequest` / `PaymentReferencesController`

**Files:**
- Modify: `backend/Corals/modules/PaymentGateway/Http/Requests/PaymentReferenceRequest.php`
- Modify: `backend/Corals/modules/PaymentGateway/Http/Controllers/PaymentReferencesController.php`
- Test: `backend/tests/Feature/PaymentGateway/PaymentReferencesAdminControllerTest.php`

**Interfaces:**
- Consumes: `InvoiceService::store($request, $modelClass, $additionalData = [])` (existing, in `Corals\Modules\PaymentGateway\Services\InvoiceService`) — accepts either a `Request` or a plain array as `$request`. `Issuer::findByHash()`, `Issuer::isAccessibleBy()`, `Invoice::findByHash()` (existing, unchanged). `PaymentReferenceService::generateWithArtifacts(Invoice $invoice, ReferenceGeneratorService $generator, BarcodeGeneratorService $barcodeGenerator, PayFormatGeneratorService $payFormatGenerator, bool $autopayEnabled = false, ?int $autopayPaymentNumber = null, ?int $autopayFrequencyDays = null): PaymentReference` (existing signature, unchanged — this task starts actually passing the last three args).
- Produces: `PaymentReferenceRequest::rules()` now branches on `invoice_mode` (`existing` default, or `new`). `PaymentReferencesController::create()` now also passes `$issuers` (Collection of accessible `Issuer` models) and `$isAdmin` (bool) to the view — consumed by Task 2's Blade changes.

- [ ] **Step 1: Write the failing tests**

Add these three tests to `backend/tests/Feature/PaymentGateway/PaymentReferencesAdminControllerTest.php`, right before the final closing `}` of the class (after `issuer_scoped_users_datatable_query_excludes_other_issuers_references`):

```php
    #[Test]
    public function admin_can_generate_a_reference_from_a_newly_created_invoice_in_one_submit()
    {
        $admin = $this->admin('inline-invoice');
        $issuer = $this->issuer('inline-invoice');

        $response = $this->actingAs($admin)->post('/payment-references', [
            'invoice_mode' => 'new',
            'issuer_id' => $issuer->getHashedIdAttribute(),
            'customer_id' => 'cust-inline',
            'amount_input' => '150.00',
            'currency' => 'MXN',
            'due_date' => now()->addDays(5)->toDateString(),
            'description' => 'Inline invoice test',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('paymentgateway_invoices', [
            'issuer_id' => $issuer->id,
            'customer_id' => 'cust-inline',
            'amount_minor' => 15000,
            'currency' => 'MXN',
        ]);

        $invoice = Invoice::where('customer_id', 'cust-inline')->firstOrFail();

        $this->assertDatabaseHas('paymentgateway_payment_references', [
            'issuer_id' => $issuer->id,
            'invoice_id' => $invoice->id,
            'amount_minor' => 15000,
            'currency' => 'MXN',
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function admin_form_can_configure_autopay_on_generation()
    {
        $admin = $this->admin('autopay');
        $issuer = $this->issuer('autopay');
        $invoice = $this->unpaidInvoice($issuer, 'autopay');

        $response = $this->actingAs($admin)->post('/payment-references', [
            'invoice_mode' => 'existing',
            'invoice_id' => $invoice->getHashedIdAttribute(),
            'autopay_enabled' => '1',
            'autopay_payment_number' => 6,
            'autopay_frequency_days' => 30,
        ]);

        $response->assertRedirect();

        $paymentReference = PaymentReference::where('invoice_id', $invoice->id)->firstOrFail();

        $this->assertDatabaseHas('paymentgateway_payment_references', [
            'id' => $paymentReference->id,
            'autopay_enabled' => 1,
            'autopay_payment_number' => 6,
            'autopay_frequency_days' => 30,
        ]);

        $this->assertDatabaseHas('paymentgateway_autopay_schedules', [
            'payment_reference_id' => $paymentReference->id,
            'status' => 'scheduled',
        ]);
    }

    #[Test]
    public function enabling_autopay_without_payment_number_or_frequency_fails_validation()
    {
        $admin = $this->admin('autopay-invalid');
        $issuer = $this->issuer('autopay-invalid');
        $invoice = $this->unpaidInvoice($issuer, 'autopay-invalid');

        $response = $this->actingAs($admin)->post('/payment-references', [
            'invoice_mode' => 'existing',
            'invoice_id' => $invoice->getHashedIdAttribute(),
            'autopay_enabled' => '1',
        ]);

        $response->assertSessionHasErrors(['autopay_payment_number', 'autopay_frequency_days']);

        $this->assertDatabaseMissing('paymentgateway_payment_references', [
            'invoice_id' => $invoice->id,
        ]);
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --compact backend/tests/Feature/PaymentGateway/PaymentReferencesAdminControllerTest.php --filter="admin_can_generate_a_reference_from_a_newly_created_invoice_in_one_submit|admin_form_can_configure_autopay_on_generation|enabling_autopay_without_payment_number_or_frequency_fails_validation"`

Expected: FAIL — the first test fails because `invoice_mode=new` isn't handled (422 or redirect to `/payment-references` without creating anything); the second fails because `autopay_enabled`/`autopay_payment_number`/`autopay_frequency_days` are never persisted (the `assertDatabaseHas` on those columns fails, and no `paymentgateway_autopay_schedules` row exists); the third fails because there's currently no rule requiring the AutoPay sub-fields when `autopay_enabled` is true, so it passes validation instead of erroring.

- [ ] **Step 3: Update `PaymentReferenceRequest`**

Replace the `rules()` method (and add a `validationData()` override) in `backend/Corals/modules/PaymentGateway/Http/Requests/PaymentReferenceRequest.php`:

```php
    /**
     * The web admin form submits a decimal amount as amount_input when
     * generating from a newly-created invoice (invoice_mode=new) - convert
     * it to amount_minor before validation runs, same as InvoiceRequest does.
     */
    public function validationData()
    {
        if ($this->isStore()
            && $this->input('invoice_mode', 'existing') === 'new'
            && $this->filled('amount_input')
            && !$this->filled('amount_minor')) {
            $this->merge(['amount_minor' => (int) round((float) $this->input('amount_input') * 100)]);
        }

        return parent::validationData();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The web admin form and the API both generate from an Invoice
     * (invoice_id) by default - it supplies the issuer, identifier, amount,
     * currency, and due date. When invoice_mode=new (admin form only), the
     * caller is instead creating that Invoice inline, so the Invoice's own
     * fields are required here and validated the same way InvoiceRequest
     * validates them.
     *
     * @return array
     */
    public function rules()
    {
        $this->setModel(PaymentReference::class);
        $rules = parent::rules();

        if ($this->isStore()) {
            $rules['invoice_mode'] = ['sometimes', 'in:existing,new'];

            if ($this->input('invoice_mode', 'existing') === 'new') {
                $rules = array_merge($rules, [
                    'issuer_id' => ['required'],
                    'customer_id' => ['required', 'string', 'max:255'],
                    'amount_minor' => ['required', 'integer', 'min:1'],
                    'currency' => ['required', 'string', 'size:3'],
                    'due_date' => ['required', 'date'],
                    'description' => ['nullable', 'string'],
                ]);
            } else {
                $rules['invoice_id'] = ['required'];
            }

            // AutoPay - schema/validation only (Phase 4 scaffold, no live gateway wiring yet).
            // required_if (not just required_with) so enabling autopay without both
            // sub-fields fails validation instead of reaching generateWithArtifacts()
            // with a null frequency, which would crash on now()->addDays(null).
            $rules = array_merge($rules, [
                'autopay_enabled' => ['sometimes', 'boolean'],
                'autopay_payment_number' => ['required_if:autopay_enabled,1', 'integer', 'min:1'],
                'autopay_frequency_days' => ['required_if:autopay_enabled,1', 'integer', 'min:1'],
            ]);
        }

        return $rules;
    }
```

- [ ] **Step 4: Update `PaymentReferencesController`**

In `backend/Corals/modules/PaymentGateway/Http/Controllers/PaymentReferencesController.php`:

Add the `InvoiceService` import and inject it alongside `PaymentReferenceService`:

```php
use Corals\Modules\PaymentGateway\Services\InvoiceService;
use Corals\Modules\PaymentGateway\Services\PaymentReferenceService;

class PaymentReferencesController extends BaseController
{
    protected $paymentReferenceService;
    protected $invoiceService;

    public function __construct(PaymentReferenceService $paymentReferenceService, InvoiceService $invoiceService)
    {
        $this->paymentReferenceService = $paymentReferenceService;
        $this->invoiceService = $invoiceService;

        $this->resource_url = config('paymentgateway.models.payment_reference.resource_url');

        $this->resource_model = new PaymentReference();

        $this->title = trans('PaymentGateway::module.payment_reference.title');
        $this->title_singular = trans('PaymentGateway::module.payment_reference.title_singular');

        parent::__construct();
    }
```

Replace the body of `create()` to also load `$issuers`/`$isAdmin` (Task 2 needs these view variables; harmless to add now even before the Blade template uses them):

```php
    public function create(PaymentReferenceRequest $request)
    {
        $user = $request->user();

        $accessibleIssuerIds = Issuer::accessibleBy($user)->pluck('id');

        $invoices = Invoice::query()
            ->whereIn('issuer_id', $accessibleIssuerIds)
            ->where('status', 'unpaid')
            ->whereDoesntHave('paymentReference')
            ->with('issuer')
            ->orderBy('due_date')
            ->get();

        $groupByIssuer = Issuer::isAdminUser($user) || $accessibleIssuerIds->count() > 1;

        $issuers = Issuer::accessibleBy($user)->orderBy('name')->get();
        $isAdmin = Issuer::isAdminUser($user);

        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.create_title', ['title' => $this->title_singular]),
        ]);

        return view('PaymentGateway::payment_references.create')->with(compact('invoices', 'groupByIssuer', 'issuers', 'isAdmin'));
    }
```

Replace the body of `store()`:

```php
    public function store(
        PaymentReferenceRequest $request,
        ReferenceGeneratorService $generator,
        BarcodeGeneratorService $barcodeGenerator,
        PayFormatGeneratorService $payFormatGenerator
    ) {
        try {
            if ($request->input('invoice_mode', 'existing') === 'new') {
                $issuer = Issuer::findByHash($request->get('issuer_id'));

                abort_if(!$issuer, 404);
            } else {
                $invoice = Invoice::findByHash($request->get('invoice_id'));

                abort_if(!$invoice, 404);

                $issuer = $invoice->issuer;
            }

            abort_if(!$issuer->isAccessibleBy($request->user()), 403, 'This user is not linked to the requested issuer.');

            if ($request->input('invoice_mode', 'existing') === 'new') {
                $invoiceData = $request->only(['customer_id', 'amount_minor', 'currency', 'due_date', 'description']);

                $invoice = $this->invoiceService->store($invoiceData, Invoice::class, ['issuer_id' => $issuer->id]);
            }

            abort_if($invoice->paymentReference()->exists(), 422, 'This invoice already has a Payment Reference.');

            $paymentReference = $this->paymentReferenceService->generateWithArtifacts(
                $invoice,
                $generator,
                $barcodeGenerator,
                $payFormatGenerator,
                $request->boolean('autopay_enabled'),
                $request->filled('autopay_payment_number') ? (int) $request->input('autopay_payment_number') : null,
                $request->filled('autopay_frequency_days') ? (int) $request->input('autopay_frequency_days') : null
            );

            flash(trans('Corals::messages.success.created', ['item' => $this->title_singular]))->success();
        } catch (\Exception $exception) {
            log_exception($exception, PaymentReference::class, 'store');
        }

        return redirectTo(isset($paymentReference) ? $paymentReference->getShowURL() : $this->resource_url);
    }
```

Note why the `invoice_id`/`amount_minor` fields are passed as a plain array (`$request->only([...])`) to `InvoiceService::store()` rather than the whole `$request`: `BaseServiceClass::getRequestData()` does `$request->except($this->excludedRequestParams)` when given a Request object, which would carry `invoice_mode`, `autopay_enabled`, etc. into `Invoice::query()->create()` and fail with an unknown-column error, since those aren't columns on `paymentgateway_invoices`.

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --compact backend/tests/Feature/PaymentGateway/PaymentReferencesAdminControllerTest.php`

Expected: PASS — all tests in the file, including the pre-existing ones (to confirm the `existing` mode default path is unchanged) and the three new ones.

- [ ] **Step 6: Commit**

```bash
git add backend/Corals/modules/PaymentGateway/Http/Requests/PaymentReferenceRequest.php backend/Corals/modules/PaymentGateway/Http/Controllers/PaymentReferencesController.php backend/tests/Feature/PaymentGateway/PaymentReferencesAdminControllerTest.php
git commit -m "feat(PaymentGateway): generate a reference from a new invoice inline, wire AutoPay fields through"
```

---

### Task 2: View — invoice-mode toggle, AutoPay fields, and lang keys

**Files:**
- Modify: `backend/Corals/modules/PaymentGateway/resources/views/payment_references/create.blade.php`
- Modify: `backend/Corals/modules/PaymentGateway/resources/lang/en/attributes.php`
- Modify: `backend/Corals/modules/PaymentGateway/resources/lang/en/module.php`
- Test: `backend/tests/Feature/PaymentGateway/PaymentReferencesAdminControllerTest.php`

**Interfaces:**
- Consumes: `$invoices`, `$groupByIssuer` (existing), `$issuers`, `$isAdmin` (added in Task 1's `create()`) — all passed into `payment_references/create.blade.php`. Field names posted by the form (`invoice_mode`, `issuer_id`, `customer_id`, `amount_input`, `currency`, `due_date`, `description`, `autopay_enabled`, `autopay_payment_number`, `autopay_frequency_days`) must match what Task 1's `PaymentReferenceRequest`/`PaymentReferencesController::store()` read.
- Produces: nothing consumed by later tasks — this is the last task.

- [ ] **Step 1: Write the failing test**

Add this test to `backend/tests/Feature/PaymentGateway/PaymentReferencesAdminControllerTest.php`, after the tests added in Task 1:

```php
    #[Test]
    public function create_page_renders_the_new_invoice_toggle_and_autopay_fields()
    {
        $admin = $this->admin('render-toggle');
        $this->issuer('render-toggle');

        $this->actingAs($admin)->get('/payment-references/create')
            ->assertStatus(200)
            ->assertSee('name="invoice_mode"', false)
            ->assertSee('name="autopay_enabled"', false)
            ->assertSee('name="autopay_payment_number"', false)
            ->assertSee('name="autopay_frequency_days"', false);
    }
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact backend/tests/Feature/PaymentGateway/PaymentReferencesAdminControllerTest.php --filter=create_page_renders_the_new_invoice_toggle_and_autopay_fields`

Expected: FAIL — none of those `name="..."` attributes exist in the current `create.blade.php`.

- [ ] **Step 3: Add the new lang keys**

In `backend/Corals/modules/PaymentGateway/resources/lang/en/attributes.php`, extend the `payment_reference` array:

```php
    'payment_reference' => [
        'issuer_id' => 'Issuer',
        'invoice_id' => 'Invoice',
        'invoice_mode' => 'Invoice source',
        'generating_for' => 'Generating for',
        'amount' => 'Amount (minor units)',
        'currency' => 'Currency',
        'due_date' => 'Due date',
        'reference' => 'Reference',
        'folio' => 'Folio',
        'status' => 'Status',
        'integration_mode' => 'Integration mode',
        'pay_format_url' => 'Payment slip (PDF)',
        'autopay_enabled' => 'Enable AutoPay',
        'autopay_payment_number' => 'AutoPay charges',
        'autopay_frequency_days' => 'AutoPay frequency (days)',
    ],
```

In `backend/Corals/modules/PaymentGateway/resources/lang/en/module.php`, extend the `payment_reference` array:

```php
    'payment_reference' => [
        'title' => 'Payment References',
        'title_singular' => 'Payment Reference',
        'use_existing_invoice' => 'Use existing invoice',
        'new_invoice' => 'New invoice',
    ],
```

- [ ] **Step 4: Rewrite `create.blade.php`**

Replace the full contents of `backend/Corals/modules/PaymentGateway/resources/views/payment_references/create.blade.php`:

```blade
@extends('layouts.crud.create_edit')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title_singular }}
        @endslot
        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_payment_reference_create_edit') }}
        @endslot
    @endcomponent
@endsection

@section('content')
    @parent
    <div class="row">
        <div class="col-md-12">
            @component('components.box')
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ url(config('paymentgateway.models.payment_reference.resource_url')) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="mr-3">
                                    <input type="radio" name="invoice_mode" value="existing" @checked(old('invoice_mode', 'existing') === 'existing')>
                                    {{ trans('PaymentGateway::module.payment_reference.use_existing_invoice') }}
                                </label>
                                <label>
                                    <input type="radio" name="invoice_mode" value="new" @checked(old('invoice_mode') === 'new')>
                                    {{ trans('PaymentGateway::module.payment_reference.new_invoice') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="existing-invoice-block" class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.payment_reference.invoice_id') }}</label>
                                <select name="invoice_id" class="form-control" required>
                                    <option value="">-</option>
                                    @if ($groupByIssuer)
                                        @foreach ($invoices->groupBy(fn ($invoice) => $invoice->issuer->name) as $issuerName => $issuerInvoices)
                                            <optgroup label="{{ $issuerName }}">
                                                @foreach ($issuerInvoices as $invoice)
                                                    <option value="{{ $invoice->getHashedIdAttribute() }}" @selected(old('invoice_id') === $invoice->getHashedIdAttribute())>{{ $invoice->customer_id }} - {{ $invoice->amount_minor }} {{ $invoice->currency }} ({{ $invoice->due_date?->toDateString() }})</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    @else
                                        @foreach ($invoices as $invoice)
                                            <option value="{{ $invoice->getHashedIdAttribute() }}" @selected(old('invoice_id') === $invoice->getHashedIdAttribute())>{{ $invoice->customer_id }} - {{ $invoice->amount_minor }} {{ $invoice->currency }} ({{ $invoice->due_date?->toDateString() }})</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="new-invoice-block" class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.invoice.issuer_id') }}</label>
                                @if ($isAdmin || $issuers->count() > 1)
                                    <select name="issuer_id" class="form-control">
                                        <option value="">-</option>
                                        @foreach ($issuers as $issuer)
                                            <option value="{{ $issuer->getHashedIdAttribute() }}" @selected(old('issuer_id') === $issuer->getHashedIdAttribute())>{{ $issuer->name }}</option>
                                        @endforeach
                                    </select>
                                @elseif ($issuers->isNotEmpty())
                                    <input type="hidden" name="issuer_id" value="{{ $issuers->first()->getHashedIdAttribute() }}">
                                    <p class="form-control-static">{{ trans('PaymentGateway::attributes.payment_reference.generating_for') }}: {{ $issuers->first()->name }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.invoice.customer_id') }}</label>
                                <input type="text" name="customer_id" class="form-control" maxlength="255" value="{{ old('customer_id') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.invoice.amount') }}</label>
                                <input type="number" name="amount_input" class="form-control" step="0.01" min="0.01" placeholder="150.00" value="{{ old('amount_input') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.invoice.currency') }}</label>
                                <input type="text" name="currency" class="form-control" maxlength="3" value="{{ old('currency', 'MXN') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.invoice.due_date') }}</label>
                                <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.invoice.description') }}</label>
                                <textarea name="description" class="form-control">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="autopay_enabled" id="autopay_enabled" value="1" @checked(old('autopay_enabled'))>
                                    {{ trans('PaymentGateway::attributes.payment_reference.autopay_enabled') }}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div id="autopay-fields" class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.payment_reference.autopay_payment_number') }}</label>
                                <input type="number" name="autopay_payment_number" class="form-control" min="1" value="{{ old('autopay_payment_number') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.payment_reference.autopay_frequency_days') }}</label>
                                <input type="number" name="autopay_frequency_days" class="form-control" min="1" value="{{ old('autopay_frequency_days') }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">{{ trans('Corals::labels.submit') }}</button>
                        </div>
                    </div>
                </form>
            @endcomponent
        </div>
    </div>
@endsection

@section('js')
    <script>
        (function ($) {
            function toggleInvoiceMode() {
                var mode = $('input[name="invoice_mode"]:checked').val();
                $('#new-invoice-block').toggle(mode === 'new');
                $('#existing-invoice-block').toggle(mode !== 'new');
            }

            function toggleAutopayFields() {
                $('#autopay-fields').toggle($('#autopay_enabled').is(':checked'));
            }

            $('input[name="invoice_mode"]').on('change', toggleInvoiceMode);
            $('#autopay_enabled').on('change', toggleAutopayFields);

            toggleInvoiceMode();
            toggleAutopayFields();
        })(jQuery);
    </script>
@endsection
```

(A block hidden via jQuery's `.toggle()`/`.hide()` gets `display: none`, which the HTML5 spec excludes from constraint validation — so the `required` attributes on `invoice_id` don't block submitting the "new invoice" block, and vice versa, with no extra JS needed to add/remove `required`.)

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --compact backend/tests/Feature/PaymentGateway/PaymentReferencesAdminControllerTest.php`

Expected: PASS — the whole file, confirming the new markup renders and nothing from Task 1 regressed.

- [ ] **Step 6: Manually verify in the browser**

Since this is a UI change, load `/payment-references/create` as an admin user with at least one unpaid invoice and one issuer:
- Confirm the "Use existing invoice" / "New invoice" radios toggle the two blocks with no page reload.
- Confirm checking "Enable AutoPay" reveals the two number fields.
- Submit the "New invoice" block end-to-end and confirm it redirects to the reference's `show` page with the barcode image and PDF link rendered (unchanged `show.blade.php`).

- [ ] **Step 7: Commit**

```bash
git add backend/Corals/modules/PaymentGateway/resources/views/payment_references/create.blade.php backend/Corals/modules/PaymentGateway/resources/lang/en/attributes.php backend/Corals/modules/PaymentGateway/resources/lang/en/module.php backend/tests/Feature/PaymentGateway/PaymentReferencesAdminControllerTest.php
git commit -m "feat(PaymentGateway): add invoice-mode toggle and AutoPay fields to the Reference Generator form"
```
