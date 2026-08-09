<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentgatewayOperatorBranchesTable extends Migration
{
    public function up()
    {
        Schema::create('paymentgateway_operator_branches', function (Blueprint $table) {
            $table->id();
            // users.id is a plain INT (unsigned), not BIGINT - matches operator_stores.
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreignId('branch_id')->constrained('paymentgateway_branches');
            $table->timestamps();

            $table->unique(['user_id', 'branch_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('paymentgateway_operator_branches');
    }
}
