<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentgatewayOperatorStoresTable extends Migration
{
    public function up()
    {
        Schema::create('paymentgateway_operator_stores', function (Blueprint $table) {
            $table->id();
            // users.id is a plain INT (unsigned), not BIGINT - see the shifts migration.
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreignId('store_id')->constrained('paymentgateway_stores');
            $table->timestamps();

            $table->unique(['user_id', 'store_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('paymentgateway_operator_stores');
    }
}
