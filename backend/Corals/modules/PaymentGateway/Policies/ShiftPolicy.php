<?php

namespace Corals\Modules\PaymentGateway\Policies;

use Corals\Modules\PaymentGateway\Models\Shift;
use Corals\Foundation\Policies\BasePolicy;
use Corals\User\Models\User;

class ShiftPolicy extends BasePolicy
{
    protected $administrationPermission = 'Administrations::admin.paymentgateway';

    /**
     * @param User $user
     * @param Shift|null $shift
     * @return bool
     */
    public function view(User $user, Shift $shift = null)
    {
        if ($shift && $shift->operator_id === $user->id) {
            return true;
        }

        return $user->can('PaymentGateway::shift.view');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->tokenCan('shift:manage') || $user->can('PaymentGateway::shift.create');
    }

    /**
     * An operator may only update (close) their own shift - enforced again in
     * the controller since this policy has no request context for the abort_if check.
     *
     * @param User $user
     * @param Shift $shift
     * @return bool
     */
    public function update(User $user, Shift $shift)
    {
        if ($shift->operator_id === $user->id && $user->tokenCan('shift:manage')) {
            return true;
        }

        return $user->can('PaymentGateway::shift.update');
    }
}
