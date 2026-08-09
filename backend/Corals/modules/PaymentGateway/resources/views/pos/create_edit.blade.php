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
                        <div class="form-group">
                            <label for="branch_id">{{ trans('PaymentGateway::attributes.pos.branch_id') }} <span class="text-danger">*</span></label>
                            <select name="branch_id" id="branch_id" class="form-control" required>
                                <option value="">--</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->hashed_id }}"
                                        @if ((isset($selectedBranchId) && $selectedBranchId === $branch->hashed_id) || (isset($pos) && $pos->branch_id === $branch->id)) selected @endif>
                                        {{ $branch->name }} — {{ $branch->store?->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
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
