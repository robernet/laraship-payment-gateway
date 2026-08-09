@extends('layouts.crud.show')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title_singular }}
        @endslot
        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_branch_show') }}
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
            <div class="col-md-6">
                <p><strong>{{ trans('PaymentGateway::attributes.branch.name') }}:</strong> {{ $branch->name }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>{{ trans('PaymentGateway::attributes.branch.store_id') }}:</strong> {{ $branch->store?->name }}</p>
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
                    <a href="{{ route('pos.create', ['branch_id' => $branch->hashed_id]) }}"
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
                @forelse ($branch->terminals()->orderBy('name')->get() as $terminal)
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

    @component('components.box')
        <div class="row mb-3">
            <div class="col-md-12">
                <h4 class="d-inline">{{ trans('PaymentGateway::module.branch.operators') }}</h4>
            </div>
        </div>

        @can('update', $branch)
            <form method="POST" action="{{ route('paymentgateway.branches.operators.assign', $branch->hashed_id) }}"
                  class="form-inline mb-3">
                @csrf
                <select name="user_id" class="form-control mr-2" required>
                    <option value="">--</option>
                    @foreach ($assignableUsers as $user)
                        <option value="{{ \Corals\Foundation\Facades\Hashids::encode($user->id) }}">
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary">
                    {{ trans('PaymentGateway::module.branch.add_operator') }}
                </button>
            </form>
        @endcan

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{{ trans('PaymentGateway::attributes.pos.name') }}</th>
                    <th>Email</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($branch->operators()->orderBy('name')->get() as $operator)
                    <tr>
                        <td>{{ $operator->name }}</td>
                        <td>{{ $operator->email }}</td>
                        <td class="text-right">
                            @can('update', $branch)
                                <form method="POST" class="d-inline"
                                      action="{{ route('paymentgateway.branches.operators.remove', [$branch->hashed_id, \Corals\Foundation\Facades\Hashids::encode($operator->id)]) }}"
                                      onsubmit="return confirm('Remove this operator from the branch?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">No operators yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endcomponent
@endsection
