@extends('layouts.crud.show')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title }}
        @endslot
        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_reports') }}
        @endslot
    @endcomponent
@endsection

@section('content')
    @component('components.box')
        <form method="GET" action="{{ url('reports') }}" class="form-inline mb-3">
            <label class="mr-2">{{ trans('PaymentGateway::labels.from') }}</label>
            <input type="date" name="from" value="{{ $from }}" class="form-control mr-3">
            <label class="mr-2">{{ trans('PaymentGateway::labels.to') }}</label>
            <input type="date" name="to" value="{{ $to }}" class="form-control mr-3">
            <button type="submit" class="btn btn-primary">{{ trans('PaymentGateway::labels.filter') }}</button>
        </form>
    @endcomponent

    @component('components.box')
        <h5>Issuer Totals</h5>
        <table class="table">
            <thead>
                <tr>
                    <th>{{ trans('PaymentGateway::attributes.store.name') }}</th>
                    <th>Transactions</th>
                    <th>Total (minor units)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($issuerTotals as $row)
                    <tr>
                        <td>{{ $row->issuer_name }}</td>
                        <td>{{ $row->transaction_count }}</td>
                        <td>{{ $row->total_minor }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No data.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endcomponent

    @component('components.box')
        <h5>Store Totals</h5>
        <table class="table">
            <thead>
                <tr>
                    <th>{{ trans('PaymentGateway::attributes.store.name') }}</th>
                    <th>Transactions</th>
                    <th>Total (minor units)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($storeTotals as $row)
                    <tr>
                        <td>{{ $row->store_name }}</td>
                        <td>{{ $row->transaction_count }}</td>
                        <td>{{ $row->total_minor }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No data.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endcomponent
@endsection
