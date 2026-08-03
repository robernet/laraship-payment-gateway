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
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.payment_reference.invoice_id') }}</label>
                                <select name="invoice_id" class="form-control" required>
                                    <option value="">-</option>
                                    @if ($groupByIssuer)
                                        @foreach ($invoices->groupBy(fn ($invoice) => $invoice->issuer->name) as $issuerName => $issuerInvoices)
                                            <optgroup label="{{ $issuerName }}">
                                                @foreach ($issuerInvoices as $invoice)
                                                    <option value="{{ $invoice->getHashedIdAttribute() }}">{{ $invoice->customer_id }} - {{ $invoice->amount_minor }} {{ $invoice->currency }} ({{ $invoice->due_date?->toDateString() }})</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    @else
                                        @foreach ($invoices as $invoice)
                                            <option value="{{ $invoice->getHashedIdAttribute() }}">{{ $invoice->customer_id }} - {{ $invoice->amount_minor }} {{ $invoice->currency }} ({{ $invoice->due_date?->toDateString() }})</option>
                                        @endforeach
                                    @endif
                                </select>
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
@endsection
