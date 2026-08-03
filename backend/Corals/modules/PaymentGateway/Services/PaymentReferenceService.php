<?php

namespace Corals\Modules\PaymentGateway\Services;

use Corals\Foundation\Services\BaseServiceClass;
use Corals\Modules\PaymentGateway\Classes\BarcodeGeneratorService;
use Corals\Modules\PaymentGateway\Classes\PayFormatGeneratorService;
use Corals\Modules\PaymentGateway\Classes\ReferenceGeneratorService;
use Corals\Modules\PaymentGateway\Models\AutopaySchedule;
use Corals\Modules\PaymentGateway\Models\Invoice;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Illuminate\Support\Str;

class PaymentReferenceService extends BaseServiceClass
{
    /**
     * Generate a Reference for an Invoice, persist it, and synchronously
     * render its barcode + pay-format artifacts. Shared by the API and admin
     * controllers so the two surfaces can never drift.
     *
     * The issuer, identifier, amount, currency, and due date all come from
     * the Invoice - it's the unique identifier generation is keyed on, not a
     * caller-supplied customer id. Mode ("online" vs "batch") is derived
     * entirely from the issuer's own reference_layout - never a caller-supplied
     * flag.
     */
    public function generateWithArtifacts(
        Invoice $invoice,
        ReferenceGeneratorService $generator,
        BarcodeGeneratorService $barcodeGenerator,
        PayFormatGeneratorService $payFormatGenerator,
        bool $autopayEnabled = false,
        ?int $autopayPaymentNumber = null,
        ?int $autopayFrequencyDays = null
    ): PaymentReference {
        $issuer = $invoice->issuer;
        $amountMinor = $invoice->amount_minor;
        $currency = $invoice->currency;
        $dueDate = $invoice->due_date?->toDateString();

        $reference = $generator->generate($issuer, (string) $invoice->id, $amountMinor, $dueDate);

        $isBatchMode = data_get($issuer->reference_layout, 'amount_length')
            || data_get($issuer->reference_layout, 'embed_due_date');

        $paymentReference = PaymentReference::create([
            'issuer_id' => $issuer->id,
            'invoice_id' => $invoice->id,
            'reference' => $reference,
            'integration_mode' => $isBatchMode ? 'batch' : 'online',
            'status' => 'pending',
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'due_date' => $dueDate,
            'folio' => 'FOL-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'autopay_enabled' => $autopayEnabled,
            'autopay_payment_number' => $autopayPaymentNumber,
            'autopay_frequency_days' => $autopayFrequencyDays,
        ]);

        $paymentReference->update([
            'barcode_url' => $barcodeGenerator->generate($reference),
            'pay_format_url' => $payFormatGenerator->generate($paymentReference),
        ]);

        if ($autopayEnabled) {
            AutopaySchedule::create([
                'payment_reference_id' => $paymentReference->id,
                'status' => 'scheduled',
                'next_charge_date' => now()->addDays($autopayFrequencyDays),
            ]);
        }

        $this->setModel($paymentReference);

        return $paymentReference;
    }
}
