@extends('layouts.crud.index')

@php($hideCreate = true)

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title }}
        @endslot
        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_issuers') }}
        @endslot
    @endcomponent
@endsection
