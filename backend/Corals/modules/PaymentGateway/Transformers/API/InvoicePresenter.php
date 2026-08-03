<?php

namespace Corals\Modules\PaymentGateway\Transformers\API;

use Corals\Foundation\Transformers\FractalPresenter;

class InvoicePresenter extends FractalPresenter
{
    /**
     * @return InvoiceTransformer
     */
    public function getTransformer($extras = [])
    {
        return new InvoiceTransformer($extras);
    }
}
