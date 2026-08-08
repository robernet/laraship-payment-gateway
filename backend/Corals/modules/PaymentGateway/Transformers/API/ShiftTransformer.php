<?php

namespace Corals\Modules\PaymentGateway\Transformers\API;

use Corals\Foundation\Facades\Hashids;
use Corals\Foundation\Transformers\APIBaseTransformer;
use Corals\Modules\PaymentGateway\Models\Shift;

class ShiftTransformer extends APIBaseTransformer
{
    /**
     * @param Shift $shift
     * @return array
     * @throws \Throwable
     */
    public function transform(Shift $shift)
    {
        // operator is a core User model, not a PaymentGateway model with
        // ApiHashTrait, so it can't use ->hashed_id: on `api` middleware
        // routes the vendor HashTrait it does have passes raw ints through
        // unchanged (see ApiHashTrait's docblock) - encode directly instead.
        $transformedArray = [
            'id' => $shift->hashed_id,
            'store_id' => $shift->store?->hashed_id,
            'operator_id' => $shift->operator_id ? Hashids::encode($shift->operator_id) : null,
            'opened_at' => $shift->opened_at?->toIso8601String(),
            'closed_at' => $shift->closed_at?->toIso8601String(),
            'counted_amount_minor' => $shift->counted_amount_minor,
            'discrepancy_minor' => $shift->discrepancy_minor,
            'created_at' => $shift->created_at?->toIso8601String(),
            'updated_at' => $shift->updated_at?->toIso8601String(),
        ];

        return parent::transformResponse($transformedArray);
    }
}
