<?php

namespace Corals\Modules\PaymentGateway\Policies;

use Corals\Foundation\Policies\BasePolicy;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\User\Models\User;

class IssuerPolicy extends BasePolicy
{
    protected $administrationPermission = 'Administrations::admin.paymentgateway';

    /**
     * @param User $user
     * @return bool
     */
    public function view(User $user)
    {
        return $user->can('PaymentGateway::issuer.view');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->can('PaymentGateway::issuer.create');
    }

    /**
     * @param User $user
     * @param Issuer $issuer
     * @return bool
     */
    public function update(User $user, Issuer $issuer)
    {
        return $user->can('PaymentGateway::issuer.update');
    }

    /**
     * @param User $user
     * @param Issuer $issuer
     * @return bool
     */
    public function destroy(User $user, Issuer $issuer)
    {
        return $user->can('PaymentGateway::issuer.delete');
    }
}
