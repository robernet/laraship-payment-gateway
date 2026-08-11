<?php

namespace Corals\Modules\PaymentGateway\Transformers;

use Corals\Foundation\Transformers\BaseTransformer;
use Corals\Modules\PaymentGateway\Models\Shift;

class ShiftTransformer extends BaseTransformer
{
    public function __construct($extras = [])
    {
        $this->resource_url = config('paymentgateway.models.shift.resource_url');

        parent::__construct($extras);
    }

    /**
     * @param Shift $shift
     * @return array
     * @throws \Throwable
     */
    public function transform(Shift $shift)
    {
        $transformedArray = [
            'id' => $shift->hashed_id,
            'store_name' => HtmlElement('a', ['href' => $shift->getShowURL()], $shift->store?->name),
            'operator_name' => $shift->operator?->name,
            'opened_at' => format_date($shift->opened_at),
            'closed_at' => $shift->closed_at ? format_date($shift->closed_at) : null,
            'counted_amount_minor' => $shift->counted_amount_minor !== null ? number_format($shift->counted_amount_minor / 100, 2) : null,
            'discrepancy_minor' => $shift->discrepancy_minor !== null ? number_format($shift->discrepancy_minor / 100, 2) : null,
        ];

        return parent::transformResponse($transformedArray);
    }
}
