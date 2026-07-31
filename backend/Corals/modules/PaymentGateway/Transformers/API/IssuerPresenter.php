<?php

namespace Corals\Modules\PaymentGateway\Transformers\API;

use Corals\Foundation\Transformers\FractalPresenter;

class IssuerPresenter extends FractalPresenter
{
    /**
     * @return IssuerTransformer
     */
    public function getTransformer($extras = [])
    {
        return new IssuerTransformer($extras);
    }
}
