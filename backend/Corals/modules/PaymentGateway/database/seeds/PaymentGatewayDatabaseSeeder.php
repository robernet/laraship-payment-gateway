<?php

namespace Corals\Modules\PaymentGateway\database\seeds;

use Corals\Menu\Models\Menu;
use Corals\Settings\Models\Setting;
use Corals\User\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PaymentGatewayDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(PaymentGatewayPermissionsDatabaseSeeder::class);
        $this->call(PaymentGatewayMenuDatabaseSeeder::class);
        $this->call(PaymentGatewaySettingsDatabaseSeeder::class);
    }

    public function rollback()
    {
        Permission::where('name', 'like', 'PaymentGateway::%')->delete();

        Menu::where('key', 'paymentgateway')
            ->orWhere('active_menu_url', 'like', 'paymentgateways%')
            ->orWhere('url', 'like', 'paymentgateways%')
            ->delete();

        Setting::where('category', 'PaymentGateway')->delete();

        Media::whereIn('collection_name', ['paymentgateway-media-collection'])->delete();
    }
}
