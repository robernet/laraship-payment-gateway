@extends('layouts.crud.show')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title_singular }}
        @endslot

        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_invoice_show') }}
        @endslot
    @endcomponent
@endsection

@section('content')
    @component('components.box')
        <div class="row">
            <div class="col-md-6">
                <p><strong>{{ trans('PaymentGateway::attributes.invoice.issuer_id') }}:</strong> {{ $invoice->issuer->name }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.invoice.customer_id') }}:</strong> {{ $invoice->customer_id }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.invoice.status') }}:</strong> {{ $invoice->status }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>{{ trans('PaymentGateway::attributes.invoice.amount') }}:</strong> {{ number_format($invoice->amount_minor / 100, 2) }} {{ $invoice->currency }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.invoice.due_date') }}:</strong> {{ $invoice->due_date?->toDateString() }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.invoice.description') }}:</strong> {{ $invoice->description ?? '-' }}</p>
            </div>
        </div>
    @endcomponent

    @if ($invoice->paymentReference)
        @component('components.box')
            <h5>{{ trans('PaymentGateway::module.payment_reference.title_singular') }}</h5>
            <p>
                <a href="{{ $invoice->paymentReference->getShowURL() }}">{{ $invoice->paymentReference->reference }}</a>
                - {{ $invoice->paymentReference->status }}
            </p>
        @endcomponent
    @endif
@endsection
