<?php

namespace Corals\Modules\PaymentGateway\Models;

use Corals\Foundation\Models\BaseModel;
use Corals\Foundation\Transformers\PresentableTrait;
use Corals\Modules\PaymentGateway\Traits\ApiHashTrait;
use Spatie\Activitylog\Traits\LogsActivity;

class Transaction extends BaseModel
{
    use ApiHashTrait;
    use PresentableTrait;
    use LogsActivity;

    /**
     *  Model configuration.
     * @var string
     */
    public $config = 'paymentgateway.models.transaction';

    protected $casts = [
        'properties' => 'json',
        'amount_minor' => 'integer',
        'collected_at' => 'datetime',
    ];

    protected $table = 'paymentgateway_transactions';

    protected $guarded = ['id'];

    public function paymentReference()
    {
        return $this->belongsTo(PaymentReference::class, 'payment_reference_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}
