<?php

namespace Corals\Modules\PaymentGateway\Policies;

use Corals\Modules\PaymentGateway\Models\Pos;
use Corals\Modules\PaymentGateway\Models\Shift;
use Corals\Foundation\Policies\BasePolicy;
use Corals\User\Models\User;

class ShiftPolicy extends BasePolicy
{
    protected $administrationPermission = 'Administrations::admin.paymentgateway';

    /**
     * A Pos device has no roles/permissions (it isn't a Spatie HasRoles
     * subject) - the parent's before() would call hasPermissionTo() on it and
     * fatal, so skip straight to the ability check below.
     *
     * @param User|Pos $user
     * @param string $ability
     * @return bool|null
     */
    public function before($user, $ability)
    {
        if ($user instanceof Pos) {
            return null;
        }

        return parent::before($user, $ability);
    }

    /**
     * @param User|Pos $user
     * @param Shift|null $shift
     * @return bool
     */
    public function view(User|Pos $user, Shift $shift = null)
    {
        if ($user instanceof Pos) {
            return $shift && $shift->pos_id === $user->id;
        }

        if ($shift && $shift->operator_id === $user->id) {
            return true;
        }

        return $user->can('PaymentGateway::shift.view');
    }

    /**
     * @param User|Pos $user
     * @return bool
     */
    public function create(User|Pos $user)
    {
        if ($user instanceof Pos) {
            return $user->tokenCan('shift:manage');
        }

        return $user->tokenCan('shift:manage') || $user->can('PaymentGateway::shift.create');
    }

    /**
     * A device or operator may only update (close) its own shift - enforced
     * again in the controller since this policy has no request context for
     * the abort_if check.
     *
     * @param User|Pos $user
     * @param Shift $shift
     * @return bool
     */
    public function update(User|Pos $user, Shift $shift)
    {
        if ($user instanceof Pos) {
            return $shift->pos_id === $user->id && $user->tokenCan('shift:manage');
        }

        if ($shift->operator_id === $user->id && $user->tokenCan('shift:manage')) {
            return true;
        }

        return $user->can('PaymentGateway::shift.update');
    }
}
