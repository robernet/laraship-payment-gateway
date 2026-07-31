<?php

namespace Corals\Modules\PaymentGateway;

use Corals\Foundation\Providers\BasePackageServiceProvider;
use Corals\Modules\PaymentGateway\Facades\PaymentGateway;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\Modules\PaymentGateway\Providers\PaymentGatewayAuthServiceProvider;
use Corals\Modules\PaymentGateway\Providers\PaymentGatewayObserverServiceProvider;
use Corals\Modules\PaymentGateway\Providers\PaymentGatewayRouteServiceProvider;
use Corals\Settings\Facades\Modules;
use Corals\Settings\Facades\Settings;
use Illuminate\Foundation\AliasLoader;

class PaymentGatewayServiceProvider extends BasePackageServiceProvider
{
    protected $defer = true;
    /**
     * @var
     */
    protected $packageCode = 'corals-paymentgateway';

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function bootPackage()
    {
        // Load view
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'PaymentGateway');

        // Load translation
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'PaymentGateway');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        $this->registerCustomFieldsModels();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function registerPackage()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/paymentgateway.php', 'paymentgateway');

        $this->app->register(PaymentGatewayRouteServiceProvider::class);
        $this->app->register(PaymentGatewayAuthServiceProvider::class);
        $this->app->register(PaymentGatewayObserverServiceProvider::class);

        $this->app->booted(function () {
            $loader = AliasLoader::getInstance();
            $loader->alias('PaymentGateway', PaymentGateway::class);
        });
    }

    protected function registerCustomFieldsModels()
    {
        Settings::addCustomFieldModel(Store::class);
    }

    public function registerModulesPackages()
    {
        Modules::addModulesPackages('corals/paymentgateway');
    }
}
