<?php

namespace Corals\Modules\PaymentGateway\Transformers\API;

use Corals\Foundation\Transformers\APIBaseTransformer;
use Corals\Modules\PaymentGateway\Models\Invoice;

class InvoiceTransformer extends APIBaseTransformer
{
    /**
     * @param Invoice $invoice
     * @return array
     * @throws \Throwable
     */
    public function transform(Invoice $invoice)
    {
        $transformedArray = [
            'id' => $invoice->hashed_id,
            'issuer_id' => $invoice->issuer?->hashed_id,
            'customer_id' => $invoice->customer_id,
            'amount_minor' => $invoice->amount_minor,
            'currency' => $invoice->currency,
            'due_date' => $invoice->due_date?->toDateString(),
            'description' => $invoice->description,
            'status' => $invoice->status,
            'created_at' => format_date($invoice->created_at),
            'updated_at' => format_date($invoice->updated_at),
        ];

        return parent::transformResponse($transformedArray);
    }
}
