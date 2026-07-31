<?php

namespace Corals\Modules\PaymentGateway\Providers;

use Corals\Modules\PaymentGateway\Models\Store;
use Corals\Modules\PaymentGateway\Observers\StoreObserver;
use Illuminate\Support\ServiceProvider;

class PaymentGatewayObserverServiceProvider extends ServiceProvider
{
    /**
     * Register Observers
     */
    public function boot()
    {
        Store::observe(StoreObserver::class);
    }
}
