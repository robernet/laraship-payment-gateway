<?php

namespace Corals\Modules\PaymentGateway\Policies;

use Corals\Foundation\Policies\BasePolicy;
use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\User\Models\User;

class BranchPolicy extends BasePolicy
{
    protected $administrationPermission = 'Administrations::admin.paymentgateway';

    public function view(User $user)
    {
        return $user->can('PaymentGateway::branch.view');
    }

    public function create(User $user)
    {
        return $user->can('PaymentGateway::branch.create');
    }

    public function update(User $user, Branch $branch)
    {
        return $user->can('PaymentGateway::branch.update');
    }

    public function destroy(User $user, Branch $branch)
    {
        return $user->can('PaymentGateway::branch.delete');
    }
}
