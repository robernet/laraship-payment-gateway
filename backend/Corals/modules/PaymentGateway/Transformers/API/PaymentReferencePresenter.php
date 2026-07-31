<?php

namespace Corals\Modules\PaymentGateway\Transformers\API;

use Corals\Foundation\Transformers\FractalPresenter;

class PaymentReferencePresenter extends FractalPresenter
{
    /**
     * @return PaymentReferenceTransformer
     */
    public function getTransformer($extras = [])
    {
        return new PaymentReferenceTransformer($extras);
    }
}
