<?php

namespace Corals\Modules\PaymentGateway\Transformers;

use Corals\Foundation\Transformers\FractalPresenter;

class TransactionPresenter extends FractalPresenter
{
    /**
     * @return TransactionTransformer
     */
    public function getTransformer($extras = [])
    {
        return new TransactionTransformer($extras);
    }
}
