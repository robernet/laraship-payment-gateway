@extends('layouts.crud.show')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title_singular }}
        @endslot

        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_store_show') }}
        @endslot
    @endcomponent
@endsection

@section('content')
    @component('components.box')
        <div class="row">
            <div class="col-md-12">
                <!-- show store details here -->
            </div>
        </div>
    @endcomponent
@endsection

