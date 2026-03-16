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
        Schema::table('analysis_batches', function (Blueprint $table) {
            $table->integer('blank_count')->default(0)->after('total_rows');
            $table->integer('na_count')->default(0)->after('blank_count');
            $table->integer('special_char_count')->default(0)->after('na_count');
            $table->integer('valid_count')->default(0)->after('special_char_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analysis_batches', function (Blueprint $table) {
            $table->dropColumn(['blank_count', 'na_count', 'special_char_count', 'valid_count']);
        });
    }
};