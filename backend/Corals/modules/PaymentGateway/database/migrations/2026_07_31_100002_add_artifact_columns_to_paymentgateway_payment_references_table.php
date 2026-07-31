<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddArtifactColumnsToPaymentgatewayPaymentReferencesTable extends Migration
{
    public function up()
    {
        Schema::table('paymentgateway_payment_references', function (Blueprint $table) {
            $table->string('folio')->nullable()->after('due_date');
            $table->string('barcode_url')->nullable()->after('folio');
            $table->string('pay_format_url')->nullable()->after('barcode_url');
        });
    }

    public function down()
    {
        Schema::table('paymentgateway_payment_references', function (Blueprint $table) {
            $table->dropColumn(['folio', 'barcode_url', 'pay_format_url']);
        });
    }
}
