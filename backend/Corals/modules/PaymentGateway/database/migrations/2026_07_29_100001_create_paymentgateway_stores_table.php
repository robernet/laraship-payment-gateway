<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentgatewayStoresTable extends Migration
{
    public function up()
    {
        Schema::create('paymentgateway_stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('properties')->nullable();
            $table->auditable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paymentgateway_stores');
    }
}
