<?php

namespace Corals\Modules\PaymentGateway\Providers;

use Corals\Foundation\Providers\BaseInstallModuleServiceProvider;
use Corals\Modules\PaymentGateway\database\migrations\PaymentGatewayTables;
use Corals\Modules\PaymentGateway\database\seeds\PaymentGatewayDatabaseSeeder;

class InstallModuleServiceProvider extends BaseInstallModuleServiceProvider
{
    protected $module_public_path = __DIR__ . '/../public';

    protected $migrations = [
        PaymentGatewayTables::class,
    ];

    protected function providerBooted()
    {
        $this->createSchema();

        $paymentgatewayDatabaseSeeder = new PaymentGatewayDatabaseSeeder();

        $paymentgatewayDatabaseSeeder->run();
    }
}
