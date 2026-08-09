<?php

namespace Corals\Modules\PaymentGateway\Policies;

use Corals\Foundation\Policies\BasePolicy;
use Corals\Modules\PaymentGateway\Models\Pos;
use Corals\Modules\PaymentGateway\Models\Transaction;
use Corals\User\Models\User;

class TransactionPolicy extends BasePolicy
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
     * @param Transaction|null $transaction
     * @return bool
     */
    public function view(User|Pos $user, Transaction $transaction = null)
    {
        if ($user instanceof Pos) {
            return $transaction && $transaction->shift?->pos_id === $user->id && $user->tokenCan('transaction:read-own');
        }

        if ($transaction && $transaction->shift?->operator_id === $user->id && $user->tokenCan('transaction:read-own')) {
            return true;
        }

        return $user->can('PaymentGateway::transaction.view');
    }

    /**
     * @param User|Pos $user
     * @return bool
     */
    public function create(User|Pos $user)
    {
        if ($user instanceof Pos) {
            return $user->tokenCan('payment:collect');
        }

        return $user->tokenCan('payment:collect') || $user->can('PaymentGateway::transaction.create');
    }
}
