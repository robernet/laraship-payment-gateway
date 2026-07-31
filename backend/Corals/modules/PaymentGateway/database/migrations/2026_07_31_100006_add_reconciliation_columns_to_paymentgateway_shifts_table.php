<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReconciliationColumnsToPaymentgatewayShiftsTable extends Migration
{
    public function up()
    {
        Schema::table('paymentgateway_shifts', function (Blueprint $table) {
            $table->bigInteger('counted_amount_minor')->nullable()->after('closed_at');
            $table->bigInteger('discrepancy_minor')->nullable()->after('counted_amount_minor');
        });
    }

    public function down()
    {
        Schema::table('paymentgateway_shifts', function (Blueprint $table) {
            $table->dropColumn(['counted_amount_minor', 'discrepancy_minor']);
        });
    }
}
