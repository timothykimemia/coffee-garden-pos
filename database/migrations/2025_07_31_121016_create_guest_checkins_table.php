<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGuestCheckinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('guest_checkins', function (Blueprint $table) {
$table->increments('id');

    $table->string('surname');
    $table->string('name');
    $table->string('email')->nullable();
    $table->string('phone');
    $table->enum('gender', ['Male', 'Female']);
    $table->string('nationality');
    $table->enum('id_type', ['ID', 'Visa']);
    $table->string('id_number')->nullable();
    $table->string('passport_no')->nullable();
    $table->string('stay_purpose');
    $table->string('payment_method');
    $table->string('company')->nullable();
    $table->text('remarks')->nullable();
    $table->boolean('staff_acknowledged')->default(false);
    $table->boolean('guest_acknowledged')->default(false);
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
        Schema::dropIfExists('guest_checkins');
    }
}
