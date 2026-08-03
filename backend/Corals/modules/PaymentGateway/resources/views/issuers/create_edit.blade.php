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
                        {!! CoralsForm::number('sub_id', 'PaymentGateway::attributes.issuer.sub_id', true, null, ['min' => 0, 'max' => 999, 'id' => 'reference-preview-sub-id']) !!}
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
                            ['min' => 1, 'max' => 22, 'id' => 'reference-preview-identifier-length']
                        ) !!}
                    </div>
                    <div class="col-md-4">
                        {!! CoralsForm::number(
                            'reference_layout[amount_length]',
                            'PaymentGateway::attributes.issuer.amount_length',
                            false,
                            old('reference_layout.amount_length', data_get($issuer->reference_layout, 'amount_length')),
                            ['min' => 1, 'max' => 15, 'id' => 'reference-preview-amount-length']
                        ) !!}
                    </div>
                    <div class="col-md-4">
                        {!! CoralsForm::checkbox(
                            'reference_layout[embed_due_date]',
                            'PaymentGateway::attributes.issuer.embed_due_date',
                            old('reference_layout.embed_due_date', data_get($issuer->reference_layout, 'embed_due_date')),
                            1,
                            ['id' => 'reference-preview-embed-due-date']
                        ) !!}
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <label>{{ trans('PaymentGateway::attributes.issuer.reference_format_preview') }}</label>
                        <div>
                            <code id="reference-format-preview" style="font-size: 1.1rem; letter-spacing: 1px;"></code>
                        </div>
                        <div class="text-muted text-sm" id="reference-format-preview-legend"></div>
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
    <script>
        // Sample reference format preview - mirrors ReferenceGeneratorService::generate()
        // (PREFIX + SUB_ID + IDENTIFIER + [AMOUNT] + [DUE_DATE] + Mod10 check digit).
        // PREFIX comes from the "Payment Gateway ID" setting (Settings > PaymentGateway, code=paymentgateway_id).
        (function () {
            function zeroPad(value, length) {
                value = String(value);
                return value.length >= length ? value.slice(0, length) : '0'.repeat(length - value.length) + value;
            }

            // Always 3 digits - zero-padded the same way ReferenceGeneratorService::generate() pads it.
            var gatewayPrefix = zeroPad("{{ \Settings::get('paymentgateway_id', '777') }}", 3);

            function mod10CheckDigit(digits) {
                var sum = 0;
                var alternate = true;

                for (var i = digits.length - 1; i >= 0; i--) {
                    var digit = parseInt(digits.charAt(i), 10);

                    if (alternate) {
                        digit *= 2;
                        if (digit > 9) {
                            digit -= 9;
                        }
                    }

                    sum += digit;
                    alternate = !alternate;
                }

                return (10 - (sum % 10)) % 10;
            }

            function segment(value, title) {
                return '<span title="' + title + '">' + value + '</span>&nbsp;';
            }

            function updatePreview() {
                var subId = zeroPad($('#reference-preview-sub-id').val() || 0, 3);
                var identifierLength = parseInt($('#reference-preview-identifier-length').val(), 10) || 0;
                var amountLength = parseInt($('#reference-preview-amount-length').val(), 10) || 0;
                var embedDueDate = $('#reference-preview-embed-due-date').is(':checked');

                if (identifierLength < 1) {
                    $('#reference-format-preview').html('');
                    $('#reference-format-preview-legend').text('');
                    return;
                }

                var identifier = zeroPad('42', identifierLength);
                var amount = amountLength > 0 ? zeroPad('15230', amountLength) : '';
                var dueDate = embedDueDate ? '{{ now()->format("Ymd") }}' : '';

                var payload = gatewayPrefix + subId + identifier + amount + dueDate;
                var checkDigit = mod10CheckDigit(payload);

                var html = segment(gatewayPrefix, 'Gateway prefix (Settings > Payment Gateway ID)')
                    + segment(subId, 'Issuer Sub ID')
                    + segment(identifier, 'Identifier (sample customer id)');

                var legend = 'PREFIX(3) + SUB_ID(3) + IDENTIFIER(' + identifierLength + ')';

                if (amount) {
                    html += segment(amount, 'Amount (sample, minor units)');
                    legend += ' + AMOUNT(' + amountLength + ')';
                }

                if (dueDate) {
                    html += segment(dueDate, 'Due date (YYYYMMDD)');
                    legend += ' + DUE_DATE(8)';
                }

                html += segment(checkDigit, 'Mod10 check digit');
                legend += ' + CHECK(1) = ' + (payload.length + 1) + ' digits';

                $('#reference-format-preview').html(html);
                $('#reference-format-preview-legend').text(legend);
            }

            $(document)
                .on('input change', '#reference-preview-sub-id, #reference-preview-identifier-length, #reference-preview-amount-length, #reference-preview-embed-due-date', updatePreview)
                .ready(updatePreview);
        })();
    </script>
@endsection
