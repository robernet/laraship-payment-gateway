<?php

namespace Corals\Modules\PaymentGateway\Providers;

use Corals\Foundation\Providers\BaseUninstallModuleServiceProvider;
use Corals\Modules\PaymentGateway\database\seeds\PaymentGatewayDatabaseSeeder;
use Illuminate\Support\Facades\Schema;

class UninstallModuleServiceProvider extends BaseUninstallModuleServiceProvider
{
    /**
     * This module ships its schema as standard timestamped Laravel migrations
     * (database/migrations/*.php), not the make:module scaffold's single
     * consolidated {Module}Tables class, so dropSchema()'s $migrations-based
     * rollback doesn't apply here - drop tables directly instead, children
     * before parents per their foreign keys.
     */
    protected function providerBooted()
    {
        Schema::dropIfExists('paymentgateway_autopay_schedules');
        Schema::dropIfExists('paymentgateway_transactions');
        Schema::dropIfExists('paymentgateway_issuer_users');
        Schema::dropIfExists('paymentgateway_operator_stores');
        Schema::dropIfExists('paymentgateway_payment_references');
        Schema::dropIfExists('paymentgateway_shifts');
        Schema::dropIfExists('paymentgateway_issuers');
        Schema::dropIfExists('paymentgateway_stores');

        $paymentgatewayDatabaseSeeder = new PaymentGatewayDatabaseSeeder();

        $paymentgatewayDatabaseSeeder->rollback();
    }
}
