<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentgatewayAutopaySchedulesTable extends Migration
{
    public function up()
    {
        Schema::create('paymentgateway_autopay_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_reference_id')->constrained('paymentgateway_payment_references');
            $table->string('status')->default('scheduled');
            $table->date('next_charge_date');
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->text('properties')->nullable();
            $table->auditable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paymentgateway_autopay_schedules');
    }
}
