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
    @if (session('device_secret'))
        <div class="alert alert-warning">
            <strong>Device secret:</strong> <code>{{ session('device_secret') }}</code>
            <br>Copy this now - it will not be shown again.
        </div>
    @endif

    @component('components.box')
        <div class="row">
            <div class="col-md-12">
                <p><strong>{{ trans('PaymentGateway::attributes.store.name') }}:</strong> {{ $store->name }}</p>
            </div>
        </div>
    @endcomponent

    @component('components.box')
        <div class="row mb-3">
            <div class="col-md-8">
                <h4 class="d-inline">{{ trans('PaymentGateway::module.branch.title') }}</h4>
            </div>
            <div class="col-md-4 text-right">
                @can('create', \Corals\Modules\PaymentGateway\Models\Branch::class)
                    <a href="{{ route('branches.create', ['store_id' => $store->hashed_id]) }}"
                       class="btn btn-sm btn-primary">
                        {{ trans('Corals::labels.create_title', ['title' => trans('PaymentGateway::module.branch.title_singular')]) }}
                    </a>
                @endcan
            </div>
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{{ trans('PaymentGateway::attributes.branch.name') }}</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($store->branches()->orderBy('name')->get() as $branch)
                    <tr>
                        <td>{{ $branch->name }}</td>
                        <td class="text-right">
                            <a href="{{ $branch->getShowURL() }}" class="btn btn-sm btn-secondary">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center text-muted">No branches yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endcomponent
@endsection

