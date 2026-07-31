<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentgatewayPaymentReferencesTable extends Migration
{
    public function up()
    {
        Schema::create('paymentgateway_payment_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issuer_id')->constrained('paymentgateway_issuers');
            $table->string('reference')->unique();
            $table->string('integration_mode')->default('online');
            $table->string('status')->default('pending');
            $table->bigInteger('amount_minor')->nullable();
            $table->string('currency', 3)->nullable();
            $table->date('due_date')->nullable();
            $table->text('properties')->nullable();
            $table->auditable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paymentgateway_payment_references');
    }
}
