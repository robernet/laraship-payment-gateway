<?php

namespace Corals\Modules\PaymentGateway\Models;

use Corals\Foundation\Models\BaseModel;
use Corals\Foundation\Transformers\PresentableTrait;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentReference extends BaseModel
{
    use PresentableTrait;
    use LogsActivity;

    /**
     *  Model configuration.
     * @var string
     */
    public $config = 'paymentgateway.models.payment_reference';

    protected $casts = [
        'properties' => 'json',
        'amount_minor' => 'integer',
        'due_date' => 'date',
    ];

    protected $table = 'paymentgateway_payment_references';

    protected $guarded = ['id'];

    public function issuer()
    {
        return $this->belongsTo(Issuer::class, 'issuer_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'payment_reference_id');
    }
}
