<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuestRegistrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('guest_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('booking_id')->nullable();
            $table->string('surname');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
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

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('guest_registrations');
    }
}