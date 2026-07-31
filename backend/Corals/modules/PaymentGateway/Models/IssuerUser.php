<?php

namespace Corals\Modules\PaymentGateway\Models;

use Corals\User\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Pivot: which issuers a platform User is allowed to generate references for.
 * Internal auth-scoping table, not an API resource - plain Eloquent, not BaseModel.
 */
class IssuerUser extends Model
{
    protected $table = 'paymentgateway_issuer_users';

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function issuer()
    {
        return $this->belongsTo(Issuer::class, 'issuer_id');
    }
}
