<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentgatewayShiftsTable extends Migration
{
    public function up()
    {
        Schema::create('paymentgateway_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('paymentgateway_stores');
            // users.id is a plain INT (unsigned), not BIGINT - this app predates
            // the BIGINT+Hashids convention stated in backend/CLAUDE.md for that table.
            $table->unsignedInteger('operator_id');
            $table->foreign('operator_id')->references('id')->on('users');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->text('properties')->nullable();
            $table->auditable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paymentgateway_shifts');
    }
}
