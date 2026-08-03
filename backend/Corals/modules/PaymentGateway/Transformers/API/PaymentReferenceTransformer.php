<?php

namespace Corals\Modules\PaymentGateway\Transformers\API;

use Corals\Foundation\Transformers\APIBaseTransformer;
use Corals\Modules\PaymentGateway\Models\PaymentReference;

class PaymentReferenceTransformer extends APIBaseTransformer
{
    /**
     * @param PaymentReference $paymentReference
     * @return array
     * @throws \Throwable
     */
    public function transform(PaymentReference $paymentReference)
    {
        $transformedArray = [
            'id' => $paymentReference->hashed_id,
            'reference' => $paymentReference->reference,
            'issuer_id' => $paymentReference->issuer?->hashed_id,
            'invoice_id' => $paymentReference->invoice?->hashed_id,
            'integration_mode' => $paymentReference->integration_mode,
            'status' => $paymentReference->status,
            'amount' => $paymentReference->amount_minor,
            'currency' => $paymentReference->currency,
            'due_date' => $paymentReference->due_date?->toDateString(),
            'folio' => $paymentReference->folio,
            'barcode_url' => $paymentReference->barcode_url,
            'pay_format_url' => $paymentReference->pay_format_url,
            'pay_td_url' => $paymentReference->pay_td_url,
            'autopay_enabled' => (bool) $paymentReference->autopay_enabled,
            'autopay_payment_number' => $paymentReference->autopay_payment_number,
            'autopay_frequency_days' => $paymentReference->autopay_frequency_days,
            'created_at' => format_date($paymentReference->created_at),
            'updated_at' => format_date($paymentReference->updated_at),
        ];

        return parent::transformResponse($transformedArray);
    }
}
