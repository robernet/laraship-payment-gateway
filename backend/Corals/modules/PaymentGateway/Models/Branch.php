<?php

namespace Corals\Modules\PaymentGateway\Models;

use Corals\Foundation\Models\BaseModel;
use Corals\Foundation\Transformers\PresentableTrait;
use Corals\Modules\PaymentGateway\Traits\ApiHashTrait;
use Corals\User\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;

class Branch extends BaseModel
{
    use ApiHashTrait;
    use PresentableTrait;
    use LogsActivity;

    /**
     *  Model configuration.
     * @var string
     */
    public $config = 'paymentgateway.models.branch';

    protected $casts = [
        'properties' => 'json',
    ];

    protected $table = 'paymentgateway_branches';

    protected $guarded = ['id'];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function terminals()
    {
        return $this->hasMany(Pos::class, 'branch_id');
    }

    /**
     * Users allowed to log in at this branch via POST /pos/login, through the
     * paymentgateway_operator_branches pivot.
     */
    public function operators()
    {
        return $this->belongsToMany(User::class, 'paymentgateway_operator_branches', 'branch_id', 'user_id')
            ->withTimestamps();
    }
}
