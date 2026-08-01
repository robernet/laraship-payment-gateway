<?php

namespace Corals\Modules\PaymentGateway\Transformers;

use Corals\Foundation\Transformers\BaseTransformer;
use Corals\Modules\PaymentGateway\Models\Transaction;

class TransactionTransformer extends BaseTransformer
{
    public function __construct($extras = [])
    {
        $this->resource_url = config('paymentgateway.models.transaction.resource_url');

        parent::__construct($extras);
    }

    /**
     * @param Transaction $transaction
     * @return array
     * @throws \Throwable
     */
    public function transform(Transaction $transaction)
    {
        $transformedArray = [
            'id' => $transaction->id,
            'reference' => HtmlElement('a', ['href' => $transaction->getShowURL()], $transaction->paymentReference?->reference),
            'store_name' => $transaction->shift?->store?->name,
            'operator_name' => $transaction->shift?->operator?->name,
            'amount_minor' => $transaction->amount_minor,
            'currency' => $transaction->currency,
            'status' => $transaction->status,
            'collected_at' => format_date($transaction->collected_at),
        ];

        return parent::transformResponse($transformedArray);
    }
}
