@extends('layouts.crud.create_edit')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title_singular }}
        @endslot
        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_store_create_edit') }}
        @endslot
    @endcomponent
@endsection

@section('content')
    @parent
    <div class="row">
        <div class="col-md-12">
            @component('components.box')
                {!! CoralsForm::openForm($store) !!}
                <div class="row">
                    <div class="col-md-4">
                        {!! CoralsForm::text('name', 'PaymentGateway::attributes.store.name' ,true,null) !!}
                    </div>
                </div>

                {!! CoralsForm::customFields($store) !!}

                <div class="row">
                    <div class="col-md-12">
                        {!! CoralsForm::formButtons() !!}
                    </div>
                </div>
                {!! CoralsForm::closeForm($store) !!}
            @endcomponent
        </div>
    </div>
@endsection

@section('js')
@endsection
