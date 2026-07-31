<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRejectLatePaymentToPaymentgatewayIssuersTable extends Migration
{
    public function up()
    {
        Schema::table('paymentgateway_issuers', function (Blueprint $table) {
            $table->boolean('reject_late_payment')->default(false)->after('reference_layout');
        });
    }

    public function down()
    {
        Schema::table('paymentgateway_issuers', function (Blueprint $table) {
            $table->dropColumn('reject_late_payment');
        });
    }
}
