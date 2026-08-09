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
        $paymentgateway_menu_id = \DB::table('menus')->where('key', 'paymentgateway')->value('id');

        if (!$paymentgateway_menu_id) {
            $paymentgateway_menu_id = \DB::table('menus')->insertGetId([
                'parent_id' => 1,// admin
                'key' => 'paymentgateway',
                'url' => null,
                'active_menu_url' => 'stores*',
                'name' => 'Payment Gateway',
                'description' => 'Payment Gateway Menu Item',
                'icon' => 'fa fa-globe',
                'target' => null, 'roles' => '["1","2"]',
                'order' => 0,
            ]);
        }

        $children = [
                [
                    'parent_id' => $paymentgateway_menu_id,
                    'key' => null,
                    'url' => config('paymentgateway.models.issuer.resource_url'),
                    'active_menu_url' => config('paymentgateway.models.issuer.resource_url') . '*',
                    'name' => 'Issuers',
                    'description' => 'Issuers List Menu Item',
                    'icon' => 'fa fa-stack-overflow',
                    'target' => null, 'roles' => '["1"]',
                    'order' => 0,
                ],
                [
                    'parent_id' => $paymentgateway_menu_id,
                    'key' => null,
                    'url' => config('paymentgateway.models.pos.resource_url'),
                    'active_menu_url' => config('paymentgateway.models.pos.resource_url') . '*',
                    'name' => 'POS',
                    'description' => 'POS List Menu Item',
                    'icon' => 'fa fa-desktop',
                    'target' => null, 'roles' => '["1"]',
                    'order' => 0,
                ],
                [
                    'parent_id' => $paymentgateway_menu_id,
                    'key' => null,
                    'url' => config('paymentgateway.models.invoice.resource_url'),
                    'active_menu_url' => config('paymentgateway.models.invoice.resource_url') . '*',
                    'name' => 'Invoices',
                    'description' => 'Invoices List Menu Item',
                    'icon' => 'fa fa-file-text-o',
                    'target' => null, 'roles' => '["1"]',
                    'order' => 0,
                ],
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
                [
                    'parent_id' => $paymentgateway_menu_id,
                    'key' => null,
                    'url' => config('paymentgateway.models.branch.resource_url'),
                    'active_menu_url' => config('paymentgateway.models.branch.resource_url') . '*',
                    'name' => 'Branches',
                    'description' => 'Branches List Menu Item',
                    'icon' => 'fa fa-sitemap',
                    'target' => null, 'roles' => '["1"]',
                    'order' => 0,
                ],
                [
                    'parent_id' => $paymentgateway_menu_id,
                    'key' => null,
                    'url' => config('paymentgateway.models.payment_reference.resource_url'),
                    'active_menu_url' => config('paymentgateway.models.payment_reference.resource_url') . '*',
                    'name' => 'Payment References',
                    'description' => 'Payment References List Menu Item',
                    'icon' => 'fa fa-barcode',
                    'target' => null, 'roles' => '["1"]',
                    'order' => 1,
                ],
                [
                    'parent_id' => $paymentgateway_menu_id,
                    'key' => null,
                    'url' => config('paymentgateway.models.transaction.resource_url'),
                    'active_menu_url' => config('paymentgateway.models.transaction.resource_url') . '*',
                    'name' => 'Transactions',
                    'description' => 'Transactions List Menu Item',
                    'icon' => 'fa fa-exchange',
                    'target' => null, 'roles' => '["1"]',
                    'order' => 2,
                ],
                [
                    'parent_id' => $paymentgateway_menu_id,
                    'key' => null,
                    'url' => config('paymentgateway.models.shift.resource_url'),
                    'active_menu_url' => config('paymentgateway.models.shift.resource_url') . '*',
                    'name' => 'Shifts',
                    'description' => 'Shifts List Menu Item',
                    'icon' => 'fa fa-clock-o',
                    'target' => null, 'roles' => '["1"]',
                    'order' => 3,
                ],
                [
                    'parent_id' => $paymentgateway_menu_id,
                    'key' => null,
                    'url' => 'reports',
                    'active_menu_url' => 'reports*',
                    'name' => 'Reports',
                    'description' => 'Issuer/Store Reports Menu Item',
                    'icon' => 'fa fa-bar-chart',
                    'target' => null, 'roles' => '["1"]',
                    'order' => 4,
                ],
        ];

        $existingUrls = \DB::table('menus')
            ->where('parent_id', $paymentgateway_menu_id)
            ->pluck('url')
            ->all();

        $newChildren = array_values(array_filter(
            $children,
            fn ($child) => !in_array($child['url'], $existingUrls, true)
        ));

        if (!empty($newChildren)) {
            \DB::table('menus')->insert($newChildren);
        }
    }
}
