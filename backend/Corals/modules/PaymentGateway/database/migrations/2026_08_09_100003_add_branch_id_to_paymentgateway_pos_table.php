<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBranchIdToPaymentgatewayPosTable extends Migration
{
    public function up()
    {
        Schema::table('paymentgateway_pos', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('store_id')->constrained('paymentgateway_branches');
        });
    }

    public function down()
    {
        Schema::table('paymentgateway_pos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
}
