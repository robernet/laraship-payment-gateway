<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCardLinkColumnsToPaymentgatewayPaymentReferencesTable extends Migration
{
    public function up()
    {
        Schema::table('paymentgateway_payment_references', function (Blueprint $table) {
            $table->string('pay_td_url')->nullable()->after('pay_format_url');
            $table->boolean('autopay_enabled')->default(false)->after('pay_td_url');
            $table->unsignedInteger('autopay_payment_number')->nullable()->after('autopay_enabled');
            $table->unsignedInteger('autopay_frequency_days')->nullable()->after('autopay_payment_number');
        });
    }

    public function down()
    {
        Schema::table('paymentgateway_payment_references', function (Blueprint $table) {
            $table->dropColumn(['pay_td_url', 'autopay_enabled', 'autopay_payment_number', 'autopay_frequency_days']);
        });
    }
}
