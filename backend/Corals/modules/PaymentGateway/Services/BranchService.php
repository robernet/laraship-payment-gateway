<?php

namespace Corals\Modules\PaymentGateway\Services;

use Corals\Foundation\Services\BaseServiceClass;

class BranchService extends BaseServiceClass
{
    /**
     * A branch's store is set once at creation (via $additionalData in
     * BranchesController@store) and is immutable afterward - excluded here so
     * a raw store_id in an update request body can't re-parent the branch and
     * desync the store_id already denormalized onto its POS/shifts.
     */
    protected $excludedRequestParams = ['store_id'];
}
