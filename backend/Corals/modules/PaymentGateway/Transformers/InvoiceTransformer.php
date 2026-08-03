<?php

namespace Corals\Modules\PaymentGateway\Transformers;

use Corals\Foundation\Transformers\BaseTransformer;
use Corals\Modules\PaymentGateway\Models\Invoice;

class InvoiceTransformer extends BaseTransformer
{
    public function __construct($extras = [])
    {
        $this->resource_url = config('paymentgateway.models.invoice.resource_url');

        parent::__construct($extras);
    }

    /**
     * @param Invoice $invoice
     * @return array
     * @throws \Throwable
     */
    public function transform(Invoice $invoice)
    {
        $transformedArray = [
            'id' => $invoice->hashed_id,
            'issuer_name' => HtmlElement('a', ['href' => $invoice->getShowURL()], $invoice->issuer?->name),
            'customer_id' => $invoice->customer_id,
            'amount_minor' => $invoice->amount_minor,
            'currency' => $invoice->currency,
            'due_date' => $invoice->due_date?->toDateString(),
            'status' => $invoice->status,
            'created_at' => format_date($invoice->created_at),
            'action' => $this->actions($invoice),
        ];

        return parent::transformResponse($transformedArray);
    }
}
