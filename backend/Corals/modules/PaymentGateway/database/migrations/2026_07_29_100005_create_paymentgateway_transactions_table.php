<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentgatewayTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('paymentgateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_reference_id')->constrained('paymentgateway_payment_references');
            $table->foreignId('shift_id')->constrained('paymentgateway_shifts');
            $table->bigInteger('amount_minor');
            $table->string('currency', 3);
            $table->timestamp('collected_at');
            $table->string('status')->default('settled');
            $table->text('properties')->nullable();
            $table->auditable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paymentgateway_transactions');
    }
}
