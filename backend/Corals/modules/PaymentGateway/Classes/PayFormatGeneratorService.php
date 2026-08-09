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
        // The barcode PNG is written to the public disk before pay-format
        // renders (see PaymentReferenceService), so embed it as a data URI -
        // dompdf renders those without needing remote-file access enabled.
        $barcodePath = 'paymentgateway/barcodes/' . $paymentReference->reference . '.png';
        $barcodeDataUri = Storage::disk('public')->exists($barcodePath)
            ? 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($barcodePath))
            : null;

        $pdf = Pdf::loadView('PaymentGateway::payment_references.pay_format', [
            'paymentReference' => $paymentReference,
            'barcodeDataUri' => $barcodeDataUri,
        ]);

        $path = 'paymentgateway/pay-formats/' . $paymentReference->reference . '.pdf';

        Storage::disk('public')->put($path, $pdf->output());

        return Storage::disk('public')->url($path);
    }
}
