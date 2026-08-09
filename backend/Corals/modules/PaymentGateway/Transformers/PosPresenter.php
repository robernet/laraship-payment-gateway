<?php

namespace Corals\Modules\PaymentGateway\Transformers;

use Corals\Foundation\Transformers\FractalPresenter;

class PosPresenter extends FractalPresenter
{
    /**
     * @return PosTransformer
     */
    public function getTransformer($extras = [])
    {
        return new PosTransformer($extras);
    }
}
