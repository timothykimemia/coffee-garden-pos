<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCheckinDateToGuestCheckinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('guest_checkins', function (Blueprint $table) {
            if (!Schema::hasColumn('guest_checkins', 'checkin_date')) {
                $table->timestamp('checkin_date')->nullable()->after('guest_acknowledged');
            }
            if (!Schema::hasColumn('guest_checkins', 'room_number')) {
                $table->string('room_number')->nullable()->after('checkin_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('guest_checkins', function (Blueprint $table) {
            $table->dropColumn(['checkin_date', 'room_number']);
        });
    }
}

// Run this migration with: php artisan make:migration add_checkin_date_to_guest_checkins_table
// Then: php artisan migrate