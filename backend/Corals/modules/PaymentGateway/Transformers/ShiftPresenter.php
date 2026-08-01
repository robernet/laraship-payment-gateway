<?php

namespace Corals\Modules\PaymentGateway\Transformers;

use Corals\Foundation\Transformers\FractalPresenter;

class ShiftPresenter extends FractalPresenter
{
    /**
     * @return ShiftTransformer
     */
    public function getTransformer($extras = [])
    {
        return new ShiftTransformer($extras);
    }
}
