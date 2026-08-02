<?php

namespace Corals\Modules\PaymentGateway\Models;

use Corals\Foundation\Models\BaseModel;
use Corals\Foundation\Transformers\PresentableTrait;
use Corals\Modules\PaymentGateway\Traits\ApiHashTrait;
use Corals\User\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;

class Issuer extends BaseModel
{
    use ApiHashTrait;
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

    /**
     * Blanket admin access - either the module-wide administration permission
     * (the same one every other PaymentGateway policy recognizes via
     * BasePolicy::before()) or the granular payment_reference.create permission.
     */
    public static function isAdminUser(User $user): bool
    {
        return isSuperUser($user)
            || $user->hasPermissionTo('Administrations::admin.paymentgateway')
            || $user->hasPermissionTo('PaymentGateway::payment_reference.create');
    }

    public function isAccessibleBy(User $user): bool
    {
        return static::isAdminUser($user)
            || IssuerUser::query()
                ->where('user_id', $user->id)
                ->where('issuer_id', $this->id)
                ->exists();
    }

    public static function accessibleBy(User $user)
    {
        if (static::isAdminUser($user)) {
            return static::query();
        }

        return static::query()->whereIn('id', IssuerUser::query()
            ->where('user_id', $user->id)
            ->pluck('issuer_id'));
    }
}
