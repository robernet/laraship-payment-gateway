<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .box { border: 1px solid #333; padding: 16px; width: 100%; }
        .row { margin-bottom: 8px; }
        .label { font-weight: bold; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Payment Slip</h2>
        <div class="row"><span class="label">Reference:</span> {{ $paymentReference->reference }}</div>
        <div class="row"><span class="label">Issuer:</span> {{ $paymentReference->issuer->name }}</div>
        @if ($paymentReference->amount_minor)
            <div class="row">
                <span class="label">Amount due:</span>
                {{ number_format($paymentReference->amount_minor / 100, 2) }} {{ $paymentReference->currency }}
            </div>
        @else
            <div class="row"><span class="label">Amount due:</span> Any amount accepted</div>
        @endif
        @if ($paymentReference->due_date)
            <div class="row"><span class="label">Due date:</span> {{ $paymentReference->due_date->format('Y-m-d') }}</div>
        @endif
        @if (!empty($barcodeDataUri))
            <div class="row" style="margin-top: 16px; text-align: center;">
                <img src="{{ $barcodeDataUri }}" alt="{{ $paymentReference->reference }}" style="max-width: 100%;">
            </div>
        @endif
    </div>
</body>
</html>
