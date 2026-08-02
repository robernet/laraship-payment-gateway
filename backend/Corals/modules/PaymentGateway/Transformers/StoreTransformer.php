<?php

namespace Corals\Modules\PaymentGateway\Transformers;

use Corals\Foundation\Transformers\BaseTransformer;
use Corals\Modules\PaymentGateway\Models\Store;

class StoreTransformer extends BaseTransformer
{
    public function __construct($extras = [])
    {
        $this->resource_url = config('paymentgateway.models.store.resource_url');

        parent::__construct($extras);
    }

    /**
     * @param Store $store
     * @return array
     * @throws \Throwable
     */
    public function transform(Store $store)
    {
        $show_url = $store->getShowURL();

        $transformedArray = [
            'id' => $store->hashed_id,
            'name' => HtmlElement('a', ['href' => $store->getShowURL()], $store->name),
            'created_at' => format_date($store->created_at),
            'updated_at' => format_date($store->updated_at),
            'action' => $this->actions($store),
        ];

        return parent::transformResponse($transformedArray);
    }
}
