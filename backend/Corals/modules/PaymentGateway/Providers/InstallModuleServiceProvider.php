<?php

namespace Corals\Modules\PaymentGateway\Providers;

use Corals\Foundation\Providers\BaseInstallModuleServiceProvider;
use Corals\Modules\PaymentGateway\database\seeds\PaymentGatewayDatabaseSeeder;

class InstallModuleServiceProvider extends BaseInstallModuleServiceProvider
{
    protected $module_public_path = __DIR__ . '/../public';

    /**
     * This module ships its schema as standard timestamped Laravel migrations
     * (database/migrations/*.php), already applied via the normal migrator -
     * not the make:module scaffold's single consolidated {Module}Tables class,
     * so there's no createSchema() call here (see UninstallModuleServiceProvider
     * for the matching note on the teardown side).
     */
    protected function providerBooted()
    {
        $paymentgatewayDatabaseSeeder = new PaymentGatewayDatabaseSeeder();

        $paymentgatewayDatabaseSeeder->run();
    }
}
