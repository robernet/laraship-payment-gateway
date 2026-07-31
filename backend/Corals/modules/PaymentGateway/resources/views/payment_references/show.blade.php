@extends('layouts.crud.show')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title_singular }}
        @endslot
        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_payment_reference_show') }}
        @endslot
    @endcomponent
@endsection

@section('content')
    @parent
    <div class="row">
        <div class="col-md-12">
            @component('components.box')
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>{{ trans('PaymentGateway::attributes.payment_reference.reference') }}:</strong> {{ $paymentReference->reference }}</p>
                        <p><strong>{{ trans('PaymentGateway::attributes.payment_reference.folio') }}:</strong> {{ $paymentReference->folio }}</p>
                        <p><strong>{{ trans('PaymentGateway::attributes.payment_reference.status') }}:</strong> {{ $paymentReference->status }}</p>
                        <p><strong>{{ trans('PaymentGateway::attributes.payment_reference.integration_mode') }}:</strong> {{ $paymentReference->integration_mode }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><img src="{{ $paymentReference->barcode_url }}" alt="Barcode"></p>
                        <p><a href="{{ $paymentReference->pay_format_url }}" target="_blank">{{ trans('PaymentGateway::attributes.payment_reference.pay_format_url') }}</a></p>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
@endsection

@section('js')
@endsection
