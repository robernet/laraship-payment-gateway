<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentgatewayIssuerUsersTable extends Migration
{
    public function up()
    {
        Schema::create('paymentgateway_issuer_users', function (Blueprint $table) {
            $table->id();
            // users.id is a plain INT (unsigned), not BIGINT - see the shifts migration.
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreignId('issuer_id')->constrained('paymentgateway_issuers');
            $table->timestamps();

            $table->unique(['user_id', 'issuer_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('paymentgateway_issuer_users');
    }
}
