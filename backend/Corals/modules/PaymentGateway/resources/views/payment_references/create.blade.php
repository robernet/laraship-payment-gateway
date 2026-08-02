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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.payment_reference.issuer_id') }}</label>
                                @if ($isAdmin || $issuers->count() > 1)
                                    <select name="issuer_id" class="form-control" required>
                                        <option value="">-</option>
                                        @foreach ($issuers as $issuer)
                                            <option value="{{ $issuer->getHashedIdAttribute() }}">{{ $issuer->name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="hidden" name="issuer_id" value="{{ $issuers->first()->getHashedIdAttribute() }}">
                                    <p class="form-control-static">{{ trans('PaymentGateway::attributes.payment_reference.generating_for') }}: {{ $issuers->first()->name }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.payment_reference.customer_id') }}</label>
                                <input type="text" name="customer_id" class="form-control" maxlength="22" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.payment_reference.amount_input') }}</label>
                                <input type="number" name="amount_input" class="form-control" step="0.01" min="0.01" placeholder="150.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.payment_reference.currency') }}</label>
                                <input type="text" name="currency" class="form-control" maxlength="3" value="MXN">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.payment_reference.due_date') }}</label>
                                <input type="date" name="due_date" class="form-control">
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
