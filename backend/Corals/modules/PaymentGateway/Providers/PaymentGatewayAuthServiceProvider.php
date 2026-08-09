<?php

namespace Corals\Modules\PaymentGateway\Providers;

use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\Modules\PaymentGateway\Models\Invoice;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Corals\Modules\PaymentGateway\Models\Pos;
use Corals\Modules\PaymentGateway\Models\Shift;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\Modules\PaymentGateway\Models\Transaction;
use Corals\Modules\PaymentGateway\Policies\BranchPolicy;
use Corals\Modules\PaymentGateway\Policies\InvoicePolicy;
use Corals\Modules\PaymentGateway\Policies\IssuerPolicy;
use Corals\Modules\PaymentGateway\Policies\PaymentReferencePolicy;
use Corals\Modules\PaymentGateway\Policies\PosPolicy;
use Corals\Modules\PaymentGateway\Policies\ShiftPolicy;
use Corals\Modules\PaymentGateway\Policies\StorePolicy;
use Corals\Modules\PaymentGateway\Policies\TransactionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class PaymentGatewayAuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Store::class => StorePolicy::class,
        Branch::class => BranchPolicy::class,
        Pos::class => PosPolicy::class,
        Issuer::class => IssuerPolicy::class,
        Invoice::class => InvoicePolicy::class,
        PaymentReference::class => PaymentReferencePolicy::class,
        Transaction::class => TransactionPolicy::class,
        Shift::class => ShiftPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
