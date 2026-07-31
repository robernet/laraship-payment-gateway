<?php

namespace Corals\Modules\PaymentGateway\Models;

use Corals\Foundation\Models\BaseModel;
use Corals\Foundation\Transformers\PresentableTrait;
use Spatie\Activitylog\Traits\LogsActivity;

class Store extends BaseModel
{
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
}
