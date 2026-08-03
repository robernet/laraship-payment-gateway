<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentgatewayInvoicesTable extends Migration
{
    public function up()
    {
        Schema::create('paymentgateway_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issuer_id')->constrained('paymentgateway_issuers');
            $table->string('customer_id');
            $table->bigInteger('amount_minor');
            $table->string('currency', 3);
            $table->date('due_date');
            $table->text('description')->nullable();
            $table->string('status')->default('unpaid');
            $table->auditable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paymentgateway_invoices');
    }
}
