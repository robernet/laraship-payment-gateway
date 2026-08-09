<?php

namespace Corals\Modules\PaymentGateway\Models;

use Corals\User\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Pivot: which branches a POS operator is allowed to log in against.
 * Internal auth-scoping table, not an API resource - plain Eloquent, not BaseModel.
 */
class OperatorBranch extends Model
{
    protected $table = 'paymentgateway_operator_branches';

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
