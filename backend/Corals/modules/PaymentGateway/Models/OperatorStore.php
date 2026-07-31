<?php

namespace Corals\Modules\PaymentGateway\Models;

use Corals\User\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Pivot: which stores a POS operator is allowed to log in against.
 * Internal auth-scoping table, not an API resource - plain Eloquent, not BaseModel.
 */
class OperatorStore extends Model
{
    protected $table = 'paymentgateway_operator_stores';

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
