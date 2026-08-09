<?php

namespace Corals\Modules\PaymentGateway\Models;

use Corals\Foundation\Models\BaseModel;
use Corals\Foundation\Transformers\PresentableTrait;
use Corals\Modules\PaymentGateway\Traits\ApiHashTrait;
use Corals\User\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;

class Store extends BaseModel
{
    use ApiHashTrait;
    use PresentableTrait;
    use LogsActivity;

    /**
     *  Model configuration.
     * @var string
     */
    public $config = 'paymentgateway.models.store';

    protected $casts = [
        'properties' => 'json',
    ];

    protected $table = 'paymentgateway_stores';

    protected $guarded = ['id'];

    public function terminals()
    {
        return $this->hasMany(Pos::class, 'store_id');
    }

    /**
     * Users allowed to log in at this store via POST /pos/login, through the
     * paymentgateway_operator_stores pivot.
     */
    public function operators()
    {
        return $this->belongsToMany(User::class, 'paymentgateway_operator_stores', 'store_id', 'user_id')
            ->withTimestamps();
    }
}
