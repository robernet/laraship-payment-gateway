@extends('layouts.crud.create_edit')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title_singular }}
        @endslot
        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_pos_create_edit') }}
        @endslot
    @endcomponent
@endsection

@section('content')
    @parent
    <div class="row">
        <div class="col-md-12">
            @component('components.box')
                {!! CoralsForm::openForm($pos) !!}
                <div class="row">
                    <div class="col-md-4">
                        {!! CoralsForm::select(
                            'store_id',
                            'PaymentGateway::attributes.pos.store_id',
                            $stores->pluck('name', 'hashed_id'),
                            true,
                            old('store_id', ($selectedStoreId ?? null) ?: $pos->store?->getHashedIdAttribute())
                        ) !!}
                    </div>
                    <div class="col-md-4">
                        {!! CoralsForm::text('name', 'PaymentGateway::attributes.pos.name', true, null) !!}
                    </div>
                    <div class="col-md-4">
                        {!! CoralsForm::text('code', 'PaymentGateway::attributes.pos.code', true, null) !!}
                    </div>
                </div>

                {!! CoralsForm::customFields($pos) !!}

                <div class="row">
                    <div class="col-md-12">
                        {!! CoralsForm::formButtons() !!}
                    </div>
                </div>
                {!! CoralsForm::closeForm($pos) !!}
            @endcomponent
        </div>
    </div>
@endsection

@section('js')
@endsection
