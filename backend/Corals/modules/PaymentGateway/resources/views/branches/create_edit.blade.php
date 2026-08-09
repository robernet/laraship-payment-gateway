@extends('layouts.crud.create_edit')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title_singular }}
        @endslot
        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_branch_create_edit') }}
        @endslot
    @endcomponent
@endsection

@section('content')
    @parent
    <div class="row">
        <div class="col-md-12">
            @component('components.box')
                {!! CoralsForm::openForm($branch) !!}
                <div class="row">
                    <div class="col-md-4">
                        {!! CoralsForm::text('name', 'PaymentGateway::attributes.branch.name', true, null) !!}
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="store_id">{{ trans('PaymentGateway::attributes.branch.store_id') }}</label>
                            @if (!isset($branch) || !$branch->exists)
                                <span class="text-danger">*</span>
                                <select name="store_id" id="store_id" class="form-control" required>
                                    <option value="">--</option>
                                    @foreach ($stores as $store)
                                        <option value="{{ $store->hashed_id }}"
                                            @if (isset($selectedStoreId) && $selectedStoreId === $store->hashed_id) selected @endif>
                                            {{ $store->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <p>{{ $branch->store?->name }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                {!! CoralsForm::customFields($branch) !!}

                <div class="row">
                    <div class="col-md-12">
                        {!! CoralsForm::formButtons() !!}
                    </div>
                </div>
                {!! CoralsForm::closeForm($branch) !!}
            @endcomponent
        </div>
    </div>
@endsection

@section('js')
@endsection
