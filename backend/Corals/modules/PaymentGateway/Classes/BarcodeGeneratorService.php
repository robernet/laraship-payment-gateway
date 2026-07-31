<?php

namespace Corals\Modules\PaymentGateway\Classes;

use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeGeneratorService
{
    /**
     * Render a Code 128 PNG barcode for the given Reference and store it on
     * the public disk. Returns the public URL.
     */
    public function generate(string $reference): string
    {
        $png = (new BarcodeGeneratorPNG())->getBarcode($reference, BarcodeGeneratorPNG::TYPE_CODE_128);

        $path = 'paymentgateway/barcodes/' . $reference . '.png';

        Storage::disk('public')->put($path, $png);

        return Storage::disk('public')->url($path);
    }
}
