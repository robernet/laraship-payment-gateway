<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPosIdToPaymentgatewayShiftsTable extends Migration
{
    public function up()
    {
        Schema::table('paymentgateway_shifts', function (Blueprint $table) {
            // Device-authenticated (POS) shifts carry pos_id and leave operator_id
            // null; user-authenticated (web/App) shifts do the opposite - see
            // docs/api-contract.md Auth (POS) / Shift.
            $table->unsignedInteger('operator_id')->nullable()->change();
            $table->foreignId('pos_id')->nullable()->after('operator_id')->constrained('paymentgateway_pos');
        });
    }

    public function down()
    {
        Schema::table('paymentgateway_shifts', function (Blueprint $table) {
            $table->dropForeign(['pos_id']);
            $table->dropColumn('pos_id');
            $table->unsignedInteger('operator_id')->nullable(false)->change();
        });
    }
}
