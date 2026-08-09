<?php

namespace Corals\Modules\PaymentGateway\Transformers;

use Corals\Foundation\Transformers\BaseTransformer;
use Corals\Modules\PaymentGateway\Models\Branch;

class BranchTransformer extends BaseTransformer
{
    public function __construct($extras = [])
    {
        $this->resource_url = config('paymentgateway.models.branch.resource_url');

        parent::__construct($extras);
    }

    /**
     * @param Branch $branch
     * @return array
     * @throws \Throwable
     */
    public function transform(Branch $branch)
    {
        $transformedArray = [
            'id' => $branch->hashed_id,
            'name' => HtmlElement('a', ['href' => $branch->getShowURL()], $branch->name),
            'store' => $branch->store?->name,
            'created_at' => format_date($branch->created_at),
            'updated_at' => format_date($branch->updated_at),
            'action' => $this->actions($branch),
        ];

        return parent::transformResponse($transformedArray);
    }
}
