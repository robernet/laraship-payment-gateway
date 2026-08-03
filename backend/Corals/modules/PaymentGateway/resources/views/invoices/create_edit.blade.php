@extends('layouts.crud.create_edit')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title_singular }}
        @endslot
        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_invoice_create_edit') }}
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
                <form method="POST" action="{{ $invoice->exists ? $invoice->getShowURL() : url(config('paymentgateway.models.invoice.resource_url')) }}">
                    @csrf
                    @if ($invoice->exists)
                        @method('PUT')
                    @endif
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.invoice.issuer_id') }}</label>
                                @if ($isAdmin || $issuers->count() > 1)
                                    <select name="issuer_id" class="form-control" required>
                                        <option value="">-</option>
                                        @foreach ($issuers as $issuer)
                                            <option value="{{ $issuer->getHashedIdAttribute() }}" @selected($invoice->issuer_id === $issuer->id)>{{ $issuer->name }}</option>
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
                                <label>{{ trans('PaymentGateway::attributes.invoice.customer_id') }}</label>
                                <input type="text" name="customer_id" class="form-control" maxlength="255" value="{{ old('customer_id', $invoice->customer_id) }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.invoice.amount') }}</label>
                                <input type="number" name="amount_input" class="form-control" step="0.01" min="0.01" placeholder="150.00" value="{{ old('amount_input', $invoice->exists ? $invoice->amount_minor / 100 : null) }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.invoice.currency') }}</label>
                                <input type="text" name="currency" class="form-control" maxlength="3" value="{{ old('currency', $invoice->currency ?? 'MXN') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.invoice.due_date') }}</label>
                                <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $invoice->due_date?->toDateString()) }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>{{ trans('PaymentGateway::attributes.invoice.description') }}</label>
                                <textarea name="description" class="form-control">{{ old('description', $invoice->description) }}</textarea>
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
