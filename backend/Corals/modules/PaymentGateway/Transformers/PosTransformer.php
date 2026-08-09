<?php

namespace Corals\Modules\PaymentGateway\Transformers;

use Corals\Foundation\Transformers\BaseTransformer;
use Corals\Modules\PaymentGateway\Models\Pos;

class PosTransformer extends BaseTransformer
{
    public function __construct($extras = [])
    {
        $this->resource_url = config('paymentgateway.models.pos.resource_url');

        parent::__construct($extras);
    }

    /**
     * @param Pos $pos
     * @return array
     * @throws \Throwable
     */
    public function transform(Pos $pos)
    {
        $transformedArray = [
            'id' => $pos->hashed_id,
            'name' => HtmlElement('a', ['href' => $pos->getShowURL()], $pos->name),
            'code' => $pos->code,
            'store_name' => $pos->store?->name,
            'created_at' => format_date($pos->created_at),
            'updated_at' => format_date($pos->updated_at),
            'action' => $this->actions($pos),
        ];

        return parent::transformResponse($transformedArray);
    }
}
