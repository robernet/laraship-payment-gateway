<?php

namespace Corals\Modules\PaymentGateway\Classes;

use Barryvdh\DomPDF\Facade\Pdf;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Illuminate\Support\Facades\Storage;

class PayFormatGeneratorService
{
    /**
     * Render the payment-slip PDF for a PaymentReference and store it on the
     * public disk. Returns the public URL.
     */
    public function generate(PaymentReference $paymentReference): string
    {
        $pdf = Pdf::loadView('PaymentGateway::payment_references.pay_format', [
            'paymentReference' => $paymentReference,
        ]);

        $path = 'paymentgateway/pay-formats/' . $paymentReference->reference . '.pdf';

        Storage::disk('public')->put($path, $pdf->output());

        return Storage::disk('public')->url($path);
    }
}
