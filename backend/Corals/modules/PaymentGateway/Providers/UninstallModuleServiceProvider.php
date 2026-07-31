<?php

namespace Corals\Modules\PaymentGateway\Providers;

use Corals\Foundation\Providers\BaseUninstallModuleServiceProvider;
use Corals\Modules\PaymentGateway\database\migrations\PaymentGatewayTables;
use Corals\Modules\PaymentGateway\database\seeds\PaymentGatewayDatabaseSeeder;

class UninstallModuleServiceProvider extends BaseUninstallModuleServiceProvider
{
    protected $migrations = [
        PaymentGatewayTables::class,
    ];

    protected function providerBooted()
    {
        $this->dropSchema();

        $paymentgatewayDatabaseSeeder = new PaymentGatewayDatabaseSeeder();

        $paymentgatewayDatabaseSeeder->rollback();
    }
}
