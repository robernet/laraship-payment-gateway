@extends('layouts.crud.show')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title_singular }}
        @endslot

        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_issuer_show') }}
        @endslot
    @endcomponent
@endsection

@section('content')
    @component('components.box')
        <div class="row">
            <div class="col-md-6">
                <p><strong>{{ trans('PaymentGateway::attributes.issuer.name') }}:</strong> {{ $issuer->name }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.issuer.sub_id') }}:</strong> {{ $issuer->sub_id }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.issuer.reject_late_payment') }}:</strong> {{ $issuer->reject_late_payment ? trans('Corals::labels.confirmation.yes') : '-' }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>{{ trans('PaymentGateway::attributes.issuer.identifier_length') }}:</strong> {{ data_get($issuer->reference_layout, 'identifier_length') }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.issuer.amount_length') }}:</strong> {{ data_get($issuer->reference_layout, 'amount_length') ?? '-' }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.issuer.embed_due_date') }}:</strong> {{ data_get($issuer->reference_layout, 'embed_due_date') ? trans('Corals::labels.confirmation.yes') : '-' }}</p>
            </div>
        </div>
    @endcomponent

    @component('components.box')
        <h5>{{ trans('PaymentGateway::module.payment_reference.title') }}</h5>
        <table class="table">
            <thead>
                <tr>
                    <th>{{ trans('PaymentGateway::attributes.payment_reference.reference') }}</th>
                    <th>{{ trans('PaymentGateway::attributes.payment_reference.status') }}</th>
                    <th>{{ trans('PaymentGateway::attributes.payment_reference.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($issuer->paymentReferences as $paymentReference)
                    <tr>
                        <td>{{ $paymentReference->reference }}</td>
                        <td>{{ $paymentReference->status }}</td>
                        <td>{{ $paymentReference->amount_minor }} {{ $paymentReference->currency }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endcomponent
@endsection
