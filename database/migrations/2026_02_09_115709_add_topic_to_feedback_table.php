<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('feedback', function (Blueprint $table) {
            // Stores "Service Quality", "Facilities", "Speed", etc.
            $table->string('topic')->nullable()->default('General Service')->after('sentiment');
        });
    }

    public function down()
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn('topic');
        });
    }
};
