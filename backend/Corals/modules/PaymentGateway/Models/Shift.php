<?php

namespace Corals\Modules\PaymentGateway\Models;

use Corals\Foundation\Models\BaseModel;
use Corals\Foundation\Transformers\PresentableTrait;
use Corals\Modules\PaymentGateway\Traits\ApiHashTrait;
use Corals\User\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;

class Shift extends BaseModel
{
    use ApiHashTrait;
    use PresentableTrait;
    use LogsActivity;

    /**
     *  Model configuration.
     * @var string
     */
    public $config = 'paymentgateway.models.shift';

    protected $casts = [
        'properties' => 'json',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'counted_amount_minor' => 'integer',
        'discrepancy_minor' => 'integer',
    ];

    protected $table = 'paymentgateway_shifts';

    protected $guarded = ['id'];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function pos()
    {
        return $this->belongsTo(Pos::class, 'pos_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'shift_id');
    }

    public function isOpen(): bool
    {
        return is_null($this->closed_at);
    }
}
