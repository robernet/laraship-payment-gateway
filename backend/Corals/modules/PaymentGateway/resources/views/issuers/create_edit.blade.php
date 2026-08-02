@extends('layouts.crud.create_edit')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title_singular }}
        @endslot
        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_issuer_create_edit') }}
        @endslot
    @endcomponent
@endsection

@section('content')
    @parent
    <div class="row">
        <div class="col-md-12">
            @component('components.box')
                {!! CoralsForm::openForm($issuer) !!}
                <div class="row">
                    <div class="col-md-4">
                        {!! CoralsForm::text('name', 'PaymentGateway::attributes.issuer.name', true, null) !!}
                    </div>
                    <div class="col-md-4">
                        {!! CoralsForm::number('sub_id', 'PaymentGateway::attributes.issuer.sub_id', true, null, ['min' => 0, 'max' => 999]) !!}
                    </div>
                    <div class="col-md-4">
                        {!! CoralsForm::checkbox('reject_late_payment', 'PaymentGateway::attributes.issuer.reject_late_payment', old('reject_late_payment', $issuer->reject_late_payment)) !!}
                    </div>
                </div>

                <hr>
                <h5>{{ trans('PaymentGateway::attributes.issuer.reference_layout') }}</h5>
                <div class="row">
                    <div class="col-md-4">
                        {!! CoralsForm::number(
                            'reference_layout[identifier_length]',
                            'PaymentGateway::attributes.issuer.identifier_length',
                            true,
                            old('reference_layout.identifier_length', data_get($issuer->reference_layout, 'identifier_length')),
                            ['min' => 1, 'max' => 22]
                        ) !!}
                    </div>
                    <div class="col-md-4">
                        {!! CoralsForm::number(
                            'reference_layout[amount_length]',
                            'PaymentGateway::attributes.issuer.amount_length',
                            false,
                            old('reference_layout.amount_length', data_get($issuer->reference_layout, 'amount_length')),
                            ['min' => 1, 'max' => 15]
                        ) !!}
                    </div>
                    <div class="col-md-4">
                        {!! CoralsForm::checkbox(
                            'reference_layout[embed_due_date]',
                            'PaymentGateway::attributes.issuer.embed_due_date',
                            old('reference_layout.embed_due_date', data_get($issuer->reference_layout, 'embed_due_date'))
                        ) !!}
                    </div>
                </div>

                {!! CoralsForm::customFields($issuer) !!}

                <div class="row">
                    <div class="col-md-12">
                        {!! CoralsForm::formButtons() !!}
                    </div>
                </div>
                {!! CoralsForm::closeForm($issuer) !!}
            @endcomponent
        </div>
    </div>
@endsection

@section('js')
@endsection
