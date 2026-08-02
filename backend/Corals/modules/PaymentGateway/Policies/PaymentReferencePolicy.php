<?php

namespace Corals\Modules\PaymentGateway\Policies;

use Corals\Foundation\Policies\BasePolicy;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Corals\User\Models\User;

class PaymentReferencePolicy extends BasePolicy
{
    protected $administrationPermission = 'Administrations::admin.paymentgateway';

    /**
     * @param User $user
     * @return bool
     */
    public function view(User $user)
    {
        return $user->can('PaymentGateway::payment_reference.view')
            || $user->tokenCan('payment:lookup')
            || Issuer::accessibleBy($user)->exists();
    }

    /**
     * @param User $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->can('PaymentGateway::payment_reference.create')
            || Issuer::accessibleBy($user)->exists();
    }

    /**
     * @param User $user
     * @param PaymentReference $paymentReference
     * @return bool
     */
    public function update(User $user, PaymentReference $paymentReference)
    {
        return $user->can('PaymentGateway::payment_reference.update');
    }

    /**
     * @param User $user
     * @param PaymentReference $paymentReference
     * @return bool
     */
    public function destroy(User $user, PaymentReference $paymentReference)
    {
        return $user->can('PaymentGateway::payment_reference.delete');
    }
}
