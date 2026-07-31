<?php

namespace Corals\Modules\PaymentGateway\Observers;

use Corals\Modules\PaymentGateway\Models\Store;

class StoreObserver
{
    /**
     * @param Store $store
     */
    public function created(Store $store)
    {
    }
}
