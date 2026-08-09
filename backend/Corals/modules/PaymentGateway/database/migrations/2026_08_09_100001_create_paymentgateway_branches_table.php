<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentgatewayBranchesTable extends Migration
{
    public function up()
    {
        Schema::create('paymentgateway_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('paymentgateway_stores');
            $table->string('name');
            $table->text('properties')->nullable();
            $table->auditable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paymentgateway_branches');
    }
}
