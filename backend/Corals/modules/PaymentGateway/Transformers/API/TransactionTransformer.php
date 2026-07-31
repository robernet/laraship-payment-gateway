<?php

namespace Corals\Modules\PaymentGateway\Transformers\API;

use Corals\Foundation\Transformers\APIBaseTransformer;
use Corals\Modules\PaymentGateway\Models\Transaction;

class TransactionTransformer extends APIBaseTransformer
{
    /**
     * @param Transaction $transaction
     * @return array
     * @throws \Throwable
     */
    public function transform(Transaction $transaction)
    {
        $transformedArray = [
            'id' => $transaction->id,
            'payment_reference_id' => $transaction->paymentReference?->id,
            'shift_id' => $transaction->shift?->id,
            'amount' => $transaction->amount_minor,
            'currency' => $transaction->currency,
            'collected_at' => format_date($transaction->collected_at),
            'status' => $transaction->status,
            'created_at' => format_date($transaction->created_at),
            'updated_at' => format_date($transaction->updated_at),
        ];

        return parent::transformResponse($transformedArray);
    }
}
