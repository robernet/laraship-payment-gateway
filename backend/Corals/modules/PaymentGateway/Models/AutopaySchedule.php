<?php

namespace Corals\Modules\PaymentGateway\Models;

use Corals\Foundation\Models\BaseModel;
use Corals\Foundation\Transformers\PresentableTrait;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * SCAFFOLD ONLY (Phase 4) - no live gateway wiring yet. See docs/roadmap.md
 * for the open processor decision (Stripe is installed but doesn't support
 * MSI; a MercadoPago module exists in another project, not yet brought in).
 */
class AutopaySchedule extends BaseModel
{
    use PresentableTrait;
    use LogsActivity;

    public $config = 'paymentgateway.models.autopay_schedule';

    protected $casts = [
        'properties' => 'json',
        'next_charge_date' => 'date',
    ];

    protected $table = 'paymentgateway_autopay_schedules';

    protected $guarded = ['id'];

    public function paymentReference()
    {
        return $this->belongsTo(PaymentReference::class, 'payment_reference_id');
    }
}
