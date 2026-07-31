<?php

namespace Corals\Modules\PaymentGateway\Services;

use Corals\Foundation\Services\BaseServiceClass;
use Corals\Modules\PaymentGateway\Classes\BarcodeGeneratorService;
use Corals\Modules\PaymentGateway\Classes\PayFormatGeneratorService;
use Corals\Modules\PaymentGateway\Classes\ReferenceGeneratorService;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Illuminate\Support\Str;

class PaymentReferenceService extends BaseServiceClass
{
    /**
     * Generate a Reference for an issuer/customer, persist it, and synchronously
     * render its barcode + pay-format artifacts. Shared by the API and admin
     * controllers so the two surfaces can never drift.
     *
     * Mode ("online" vs "batch") is derived entirely from the issuer's own
     * reference_layout - never a caller-supplied flag.
     */
    public function generateWithArtifacts(
        Issuer $issuer,
        string $customerId,
        ?int $amountMinor,
        ?string $currency,
        ?string $dueDate,
        ReferenceGeneratorService $generator,
        BarcodeGeneratorService $barcodeGenerator,
        PayFormatGeneratorService $payFormatGenerator
    ): PaymentReference {
        $reference = $generator->generate($issuer, $customerId, $amountMinor, $dueDate);

        $isBatchMode = data_get($issuer->reference_layout, 'amount_length')
            || data_get($issuer->reference_layout, 'embed_due_date');

        $paymentReference = PaymentReference::create([
            'issuer_id' => $issuer->id,
            'reference' => $reference,
            'integration_mode' => $isBatchMode ? 'batch' : 'online',
            'status' => 'pending',
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'due_date' => $dueDate,
            'folio' => 'FOL-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
        ]);

        $paymentReference->update([
            'barcode_url' => $barcodeGenerator->generate($reference),
            'pay_format_url' => $payFormatGenerator->generate($paymentReference),
        ]);

        $this->setModel($paymentReference);

        return $paymentReference;
    }
}
