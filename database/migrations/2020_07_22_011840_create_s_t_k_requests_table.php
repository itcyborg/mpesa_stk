<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSTKRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stkrequests', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type');
            $table->text('reference');
            $table->text('description');
            $table->string('CheckoutRequestID');
            $table->text('CustomerMessage')->nullable();
            $table->string('MerchantRequestID');
            $table->string('ResponseCode');
            $table->text('ResponseDescription');
            $table->string('PhoneNumber');
            $table->integer('Amount');
            $table->enum('status',['success','pending','failed'])->default('pending');
            $table->enum('channel',['API','WEB'])->default('WEB');
            $table->unsignedBigInteger('user_id');
            $table->longText('callback_uri')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stkrequests');
    }
}
