<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvoiceIdToPaymentgatewayPaymentReferencesTable extends Migration
{
    public function up()
    {
        Schema::table('paymentgateway_payment_references', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('issuer_id')->constrained('paymentgateway_invoices');
        });
    }

    public function down()
    {
        Schema::table('paymentgateway_payment_references', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
        });
    }
}
