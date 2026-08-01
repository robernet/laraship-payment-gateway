@extends('layouts.crud.show')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title_singular }}
        @endslot
        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_transaction_show') }}
        @endslot
    @endcomponent
@endsection

@section('content')
    @component('components.box')
        <div class="row">
            <div class="col-md-6">
                <p><strong>{{ trans('PaymentGateway::attributes.transaction.reference') }}:</strong> {{ $transaction->paymentReference?->reference }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.transaction.store') }}:</strong> {{ $transaction->shift?->store?->name }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.transaction.operator') }}:</strong> {{ $transaction->shift?->operator?->name }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>{{ trans('PaymentGateway::attributes.transaction.amount') }}:</strong> {{ $transaction->amount_minor }} {{ $transaction->currency }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.transaction.status') }}:</strong> {{ $transaction->status }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.transaction.collected_at') }}:</strong> {{ format_date($transaction->collected_at) }}</p>
            </div>
        </div>
    @endcomponent
@endsection
