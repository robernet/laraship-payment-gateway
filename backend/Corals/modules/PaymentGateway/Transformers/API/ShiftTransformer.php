<?php

namespace Corals\Modules\PaymentGateway\Transformers\API;

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
        $transformedArray = [
            'id' => $shift->id,
            'store_id' => $shift->store?->id,
            'operator_id' => $shift->operator?->id,
            'opened_at' => format_date($shift->opened_at),
            'closed_at' => $shift->closed_at ? format_date($shift->closed_at) : null,
            'counted_amount_minor' => $shift->counted_amount_minor,
            'discrepancy_minor' => $shift->discrepancy_minor,
            'created_at' => format_date($shift->created_at),
            'updated_at' => format_date($shift->updated_at),
        ];

        return parent::transformResponse($transformedArray);
    }
}
