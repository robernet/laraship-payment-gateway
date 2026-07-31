<?php

namespace Corals\Modules\PaymentGateway\database\seeds;

use Illuminate\Database\Seeder;

class PaymentGatewayMenuDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $paymentgateway_menu_id = \DB::table('menus')->insertGetId([
            'parent_id' => 1,// admin
            'key' => 'paymentgateway',
            'url' => null,
            'active_menu_url' => 'stores*',
            'name' => 'PaymentGateway',
            'description' => 'PaymentGateway Menu Item',
            'icon' => 'fa fa-globe',
            'target' => null, 'roles' => '["1","2"]',
            'order' => 0,
        ]);

        // seed children menu
        \DB::table('menus')->insert(
            [
                [
                    'parent_id' => $paymentgateway_menu_id,
                    'key' => null,
                    'url' => config('paymentgateway.models.store.resource_url'),
                    'active_menu_url' => config('paymentgateway.models.store.resource_url') . '*',
                    'name' => 'Stores',
                    'description' => 'Stores List Menu Item',
                    'icon' => 'fa fa-cube',
                    'target' => null, 'roles' => '["1"]',
                    'order' => 0,
                ],
            ]
        );
    }
}
