<?php

namespace Corals\Modules\PaymentGateway\Transformers;

use Corals\Foundation\Transformers\FractalPresenter;

class StorePresenter extends FractalPresenter
{
    /**
     * @return StoreTransformer
     */
    public function getTransformer($extras = [])
    {
        return new StoreTransformer($extras);
    }
}
