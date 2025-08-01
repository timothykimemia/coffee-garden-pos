<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeBusinessIdNullableInContacts extends Migration
{
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['business_id']);
            // Make business_id nullable
            $table->unsignedBigInteger('business_id')->nullable()->change();
            // Re-add the foreign key constraint with nullable support
            $table->foreign('business_id')->references('id')->on('business')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Reverse the changes: drop foreign key and make business_id non-nullable
            $table->dropForeign(['business_id']);
            $table->unsignedBigInteger('business_id')->nullable(false)->change();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
    }
}