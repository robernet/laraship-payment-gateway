<?php

namespace Corals\Modules\PaymentGateway\Policies;

use Corals\Foundation\Policies\BasePolicy;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\User\Models\User;

class StorePolicy extends BasePolicy
{
    protected $administrationPermission = 'Administrations::admin.paymentgateway';

    /**
     * @param User $user
     * @return bool
     */
    public function view(User $user)
    {
        if ($user->can('PaymentGateway::store.view')) {
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
        return $user->can('PaymentGateway::store.create');
    }

    /**
     * @param User $user
     * @param Store $store
     * @return bool
     */
    public function update(User $user, Store $store)
    {
        if ($user->can('PaymentGateway::store.update')) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @param Store $store
     * @return bool
     */
    public function destroy(User $user, Store $store)
    {
        if ($user->can('PaymentGateway::store.delete')) {
            return true;
        }

        return false;
    }
}
