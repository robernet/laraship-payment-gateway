<?php

namespace Corals\Modules\PaymentGateway\database\seeds;

use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PaymentGatewaySettingsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('settings')->insert([
            [
                'code' => 'paymentgateway_id',
                'type' => 'NUMBER',
                'category' => 'PaymentGateway',
                'label' => 'Payment Gateway ID',
                'value' => '000',
                'editable' => 1,
                'hidden' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
