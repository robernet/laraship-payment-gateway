<?php

namespace Corals\Modules\PaymentGateway\Transformers;

use Corals\Foundation\Transformers\BaseTransformer;
use Corals\Modules\PaymentGateway\Models\PaymentReference;

class PaymentReferenceTransformer extends BaseTransformer
{
    public function __construct($extras = [])
    {
        $this->resource_url = config('paymentgateway.models.payment_reference.resource_url');

        parent::__construct($extras);
    }

    /**
     * @param PaymentReference $paymentReference
     * @return array
     * @throws \Throwable
     */
    public function transform(PaymentReference $paymentReference)
    {
        $transformedArray = [
            'id' => $paymentReference->id,
            'reference' => HtmlElement('a', ['href' => $paymentReference->getShowURL()], $paymentReference->reference),
            'issuer_name' => $paymentReference->issuer?->name,
            'status' => $paymentReference->status,
            'amount_minor' => $paymentReference->amount_minor,
            'currency' => $paymentReference->currency,
            'due_date' => $paymentReference->due_date?->toDateString(),
            'created_at' => format_date($paymentReference->created_at),
        ];

        return parent::transformResponse($transformedArray);
    }
}
