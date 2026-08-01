@extends('layouts.crud.show')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title_singular }}
        @endslot
        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_shift_show') }}
        @endslot
    @endcomponent
@endsection

@section('content')
    @component('components.box')
        <div class="row">
            <div class="col-md-6">
                <p><strong>{{ trans('PaymentGateway::attributes.shift.store') }}:</strong> {{ $shift->store?->name }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.shift.operator') }}:</strong> {{ $shift->operator?->name }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.shift.opened_at') }}:</strong> {{ format_date($shift->opened_at) }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.shift.closed_at') }}:</strong> {{ $shift->closed_at ? format_date($shift->closed_at) : '-' }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>{{ trans('PaymentGateway::attributes.shift.counted_amount_minor') }}:</strong> {{ $shift->counted_amount_minor ?? '-' }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.shift.discrepancy_minor') }}:</strong>
                    <span class="{{ $shift->discrepancy_minor > 0 ? 'text-success' : ($shift->discrepancy_minor < 0 ? 'text-danger' : '') }}">
                        {{ $shift->discrepancy_minor ?? '-' }}
                    </span>
                </p>
            </div>
        </div>
    @endcomponent

    @component('components.box')
        <h5>{{ trans('PaymentGateway::module.transaction.title') }}</h5>
        <table class="table">
            <thead>
                <tr>
                    <th>{{ trans('PaymentGateway::attributes.transaction.reference') }}</th>
                    <th>{{ trans('PaymentGateway::attributes.transaction.amount') }}</th>
                    <th>{{ trans('PaymentGateway::attributes.transaction.collected_at') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($shift->transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->paymentReference?->reference }}</td>
                        <td>{{ $transaction->amount_minor }} {{ $transaction->currency }}</td>
                        <td>{{ format_date($transaction->collected_at) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endcomponent
@endsection
