<?php

namespace Corals\Modules\PaymentGateway\Facades;

use Illuminate\Support\Facades\Facade;

class PaymentGateway extends Facade
{
    /**
     * @return mixed
     */
    protected static function getFacadeAccessor()
    {
        return \Corals\Modules\PaymentGateway\Classes\PaymentGateway::class;
    }
}
