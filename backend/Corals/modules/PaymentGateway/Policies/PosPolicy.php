<?php

namespace Corals\Modules\PaymentGateway\Policies;

use Corals\Foundation\Policies\BasePolicy;
use Corals\Modules\PaymentGateway\Models\Pos;
use Corals\User\Models\User;

class PosPolicy extends BasePolicy
{
    protected $administrationPermission = 'Administrations::admin.paymentgateway';

    /**
     * @param User $user
     * @return bool
     */
    public function view(User $user)
    {
        if ($user->can('PaymentGateway::pos.view')) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->can('PaymentGateway::pos.create');
    }

    /**
     * @param User $user
     * @param Pos $pos
     * @return bool
     */
    public function update(User $user, Pos $pos)
    {
        if ($user->can('PaymentGateway::pos.update')) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @param Pos $pos
     * @return bool
     */
    public function destroy(User $user, Pos $pos)
    {
        if ($user->can('PaymentGateway::pos.delete')) {
            return true;
        }

        return false;
    }
}
