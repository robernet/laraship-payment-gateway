<?php

namespace Corals\Modules\PaymentGateway\Policies;

use Corals\Foundation\Policies\BasePolicy;
use Corals\Modules\PaymentGateway\Models\Invoice;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\User\Models\User;

class InvoicePolicy extends BasePolicy
{
    protected $administrationPermission = 'Administrations::admin.paymentgateway';

    /**
     * @param User $user
     * @return bool
     */
    public function view(User $user)
    {
        return $user->can('PaymentGateway::invoice.view')
            || Issuer::accessibleBy($user)->exists();
    }

    /**
     * @param User $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->can('PaymentGateway::invoice.create')
            || Issuer::accessibleBy($user)->exists();
    }

    /**
     * The edit-lock (blocked once a Payment Reference exists) lives in
     * InvoicesController::edit()/update() instead of here - BasePolicy::before()
     * short-circuits this method entirely for admin-permission holders, so a
     * check placed here would never run for them.
     *
     * @param User $user
     * @param Invoice $invoice
     * @return bool
     */
    public function update(User $user, Invoice $invoice)
    {
        return $user->can('PaymentGateway::invoice.update')
            || $invoice->issuer->isAccessibleBy($user);
    }
}
