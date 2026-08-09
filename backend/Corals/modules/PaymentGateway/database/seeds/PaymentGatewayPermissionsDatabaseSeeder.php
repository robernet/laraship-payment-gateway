<?php

namespace Corals\Modules\PaymentGateway\database\seeds;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class PaymentGatewayPermissionsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [];

        $permissions[] = [
            'name' => 'Administrations::admin.paymentgateway',
        ];

        $models = ['store', 'branch', 'pos', 'issuer', 'invoice', 'payment_reference', 'transaction', 'shift'];

        $levels = ['view', 'create', 'update', 'delete', 'restore', 'hardDelete'];

        foreach ($models as $model) {
            foreach ($levels as $level) {
                $permissions[] = [
                    'name' => 'PaymentGateway::' . $model . '.' . $level,
                ];
            }
        }

        $permissions = array_map(function ($item) {
            return array_merge($item, [
                'guard_name' => config('auth.defaults.guard'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, $permissions);

        $existingNames = DB::table('permissions')
            ->whereIn('name', array_column($permissions, 'name'))
            ->pluck('name')
            ->all();

        $newPermissions = array_values(array_filter(
            $permissions,
            fn ($permission) => !in_array($permission['name'], $existingNames, true)
        ));

        if (!empty($newPermissions)) {
            DB::table('permissions')->insert($newPermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
