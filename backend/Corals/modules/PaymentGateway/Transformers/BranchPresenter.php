<?php

namespace Corals\Modules\PaymentGateway\Transformers;

use Corals\Foundation\Transformers\FractalPresenter;

class BranchPresenter extends FractalPresenter
{
    /**
     * @return BranchTransformer
     */
    public function getTransformer($extras = [])
    {
        return new BranchTransformer($extras);
    }
}
