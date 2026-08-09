<?php

namespace Corals\Modules\PaymentGateway\Models;

use Corals\Foundation\Models\BaseModel;
use Corals\Foundation\Transformers\PresentableTrait;
use Corals\Modules\PaymentGateway\Traits\ApiHashTrait;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A POS terminal is a device-level API identity - it authenticates itself via
 * POST /pos/device-login with {code, device_secret} and gets its own Sanctum
 * token, distinct from a human operator's user/password token. See
 * docs/api-contract.md Auth (POS).
 */
class Pos extends BaseModel implements AuthenticatableContract, AuthorizableContract
{
    use ApiHashTrait;
    use PresentableTrait;
    use LogsActivity;
    use Authenticatable;
    use Authorizable;
    use HasApiTokens;

    /**
     *  Model configuration.
     * @var string
     */
    public $config = 'paymentgateway.models.pos';

    protected $casts = [
        'properties' => 'json',
    ];

    protected $table = 'paymentgateway_pos';

    protected $guarded = ['id'];

    protected $hidden = ['device_secret'];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Generate and persist a new device secret, returning the plaintext once -
     * only the hash is stored, mirroring how a password is never recoverable.
     */
    public function regenerateDeviceSecret(): string
    {
        $plainSecret = Str::random(40);

        $this->update(['device_secret' => Hash::make($plainSecret)]);

        return $plainSecret;
    }
}
