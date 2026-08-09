@extends('layouts.crud.show')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title_singular }}
        @endslot

        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_pos_show') }}
        @endslot
    @endcomponent
@endsection

@section('content')
    @if (session('device_secret'))
        <div class="alert alert-warning">
            <strong>Device secret:</strong> <code>{{ session('device_secret') }}</code>
            <br>Copy this now - it will not be shown again.
        </div>
    @endif

    @component('components.box')
        <div class="row">
            <div class="col-md-6">
                <p><strong>{{ trans('PaymentGateway::attributes.pos.name') }}:</strong> {{ $pos->name }}</p>
                <p><strong>{{ trans('PaymentGateway::attributes.pos.code') }}:</strong> {{ $pos->code }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>{{ trans('PaymentGateway::attributes.pos.store_id') }}:</strong> {{ $pos->store->name }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('paymentgateway.pos.regenerate_secret', $pos->hashed_id) }}"
              onsubmit="return confirm('Regenerate this device\'s secret? The old one stops working immediately.')">
            @csrf
            <button type="submit" class="btn btn-warning">Regenerate device secret</button>
        </form>
    @endcomponent
@endsection
