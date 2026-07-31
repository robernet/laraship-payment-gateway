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
                <form method="POST" action="{{ url(config('paymentgateway.models.payment_reference.resource_url')) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.payment_reference.issuer_id') }}</label>
                                <select name="issuer_id" class="form-control" required>
                                    <option value="">-</option>
                                    @foreach ($issuers as $issuer)
                                        <option value="{{ $issuer->getHashedIdAttribute() }}">{{ $issuer->name }}</option>
                                    @endforeach
                                </select>
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
                                <label>{{ trans('PaymentGateway::attributes.payment_reference.amount') }}</label>
                                <input type="number" name="amount" class="form-control" min="1" placeholder="Minor units - required only for batch-mode issuers">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.payment_reference.currency') }}</label>
                                <input type="text" name="currency" class="form-control" maxlength="3" placeholder="MXN">
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
