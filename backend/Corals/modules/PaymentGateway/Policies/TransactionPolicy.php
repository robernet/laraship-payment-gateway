<?php

namespace Corals\Modules\PaymentGateway\Policies;

use Corals\Foundation\Policies\BasePolicy;
use Corals\Modules\PaymentGateway\Models\Transaction;
use Corals\User\Models\User;

class TransactionPolicy extends BasePolicy
{
    protected $administrationPermission = 'Administrations::admin.paymentgateway';

    /**
     * @param User $user
     * @param Transaction|null $transaction
     * @return bool
     */
    public function view(User $user, Transaction $transaction = null)
    {
        if ($transaction && $transaction->shift?->operator_id === $user->id && $user->tokenCan('transaction:read-own')) {
            return true;
        }

        return $user->can('PaymentGateway::transaction.view');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->tokenCan('payment:collect') || $user->can('PaymentGateway::transaction.create');
    }
}
