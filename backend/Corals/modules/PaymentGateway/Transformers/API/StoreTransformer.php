<?php

namespace Corals\Modules\PaymentGateway\Transformers\API;

use Corals\Foundation\Transformers\APIBaseTransformer;
use Corals\Modules\PaymentGateway\Models\Store;

class StoreTransformer extends APIBaseTransformer
{
    /**
     * @param Store $store
     * @return array
     * @throws \Throwable
     */
    public function transform(Store $store)
    {
        $transformedArray = [
            'id' => $store->hashed_id,
            'name' => $store->name,
            'created_at' => format_date($store->created_at),
            'updated_at' => format_date($store->updated_at),
        ];

        return parent::transformResponse($transformedArray);
    }
}
