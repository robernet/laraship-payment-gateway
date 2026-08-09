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
                <h4 class="d-inline">{{ trans('PaymentGateway::module.pos.title') }}</h4>
            </div>
            <div class="col-md-4 text-right">
                @can('create', \Corals\Modules\PaymentGateway\Models\Pos::class)
                    <a href="{{ route('pos.create', ['store_id' => $store->hashed_id]) }}"
                       class="btn btn-sm btn-primary">
                        {{ trans('Corals::labels.create_title', ['title' => trans('PaymentGateway::module.pos.title_singular')]) }}
                    </a>
                @endcan
            </div>
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{{ trans('PaymentGateway::attributes.pos.name') }}</th>
                    <th>{{ trans('PaymentGateway::attributes.pos.code') }}</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($store->terminals()->orderBy('name')->get() as $terminal)
                    <tr>
                        <td>{{ $terminal->name }}</td>
                        <td>{{ $terminal->code }}</td>
                        <td class="text-right">
                            @can('update', $terminal)
                                <form method="POST" class="d-inline"
                                      action="{{ route('paymentgateway.pos.regenerate_secret', $terminal->hashed_id) }}"
                                      onsubmit="return confirm('Regenerate this device\'s secret? The old one stops working immediately.')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-warning">Regenerate device secret</button>
                                </form>
                            @endcan
                            @can('destroy', $terminal)
                                <form method="POST" class="d-inline"
                                      action="{{ route('pos.destroy', $terminal->hashed_id) }}"
                                      onsubmit="return confirm('Delete this terminal?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">No terminals yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endcomponent
@endsection

