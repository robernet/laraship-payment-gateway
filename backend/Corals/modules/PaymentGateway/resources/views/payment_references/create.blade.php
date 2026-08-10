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
                var newActive = mode === 'new';
                // Disable the inactive block's fields: a hidden `required` control (invoice_id)
                // otherwise blocks submission with an unfocusable-validation error, and stray
                // cross-mode fields would be submitted too.
                $('#new-invoice-block').toggle(newActive).find(':input').prop('disabled', !newActive);
                $('#existing-invoice-block').toggle(!newActive).find(':input').prop('disabled', newActive);
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
