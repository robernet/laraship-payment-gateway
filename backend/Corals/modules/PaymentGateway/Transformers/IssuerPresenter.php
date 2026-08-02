<?php

namespace Corals\Modules\PaymentGateway\Transformers;

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
