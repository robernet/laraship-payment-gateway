<?php

namespace Corals\Modules\PaymentGateway\Models;

use Corals\Foundation\Models\BaseModel;
use Corals\Foundation\Transformers\PresentableTrait;
use Spatie\Activitylog\Traits\LogsActivity;

class Issuer extends BaseModel
{
    use PresentableTrait;
    use LogsActivity;

    /**
     *  Model configuration.
     * @var string
     */
    public $config = 'paymentgateway.models.issuer';

    protected $casts = [
        'properties' => 'json',
        'reference_layout' => 'json',
        'reject_late_payment' => 'boolean',
    ];

    protected $table = 'paymentgateway_issuers';

    protected $guarded = ['id'];

    public function paymentReferences()
    {
        return $this->hasMany(PaymentReference::class, 'issuer_id');
    }
}
