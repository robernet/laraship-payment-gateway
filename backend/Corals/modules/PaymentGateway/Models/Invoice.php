<?php

namespace Corals\Modules\PaymentGateway\Models;

use Corals\Foundation\Models\BaseModel;
use Corals\Foundation\Transformers\PresentableTrait;
use Corals\Modules\PaymentGateway\Traits\ApiHashTrait;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends BaseModel
{
    use ApiHashTrait;
    use PresentableTrait;
    use LogsActivity;

    /**
     *  Model configuration.
     * @var string
     */
    public $config = 'paymentgateway.models.invoice';

    protected $casts = [
        'properties' => 'json',
        'amount_minor' => 'integer',
        'due_date' => 'date',
    ];

    protected $table = 'paymentgateway_invoices';

    protected $guarded = ['id'];

    public function issuer()
    {
        return $this->belongsTo(Issuer::class, 'issuer_id');
    }

    public function paymentReference()
    {
        return $this->hasOne(PaymentReference::class, 'invoice_id');
    }
}
