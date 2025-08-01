<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnlineBookingsTable extends Migration
{
    public function up()
    {
        Schema::create('online_bookings', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('room_slug');
            $table->date('check_in');
            $table->date('check_out');
            $table->integer('adults');
            $table->integer('rooms');
            $table->string('name');
            $table->string('phone_number');
            $table->enum('gender', ['male', 'female']);
            $table->string('email')->nullable();
            $table->integer('number_of_days');
            $table->decimal('total_price', 10, 2);
            $table->decimal('price_per_room', 10, 2);
            $table->boolean('is_double_occupancy')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('online_bookings');
    }
}
