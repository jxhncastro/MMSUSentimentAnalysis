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
        if (!Schema::hasColumn('analysis_batches', 'blank_count')) {
            $table->integer('blank_count')->default(0)->after('processed_rows');
        }
        if (!Schema::hasColumn('analysis_batches', 'na_count')) {
            $table->integer('na_count')->default(0)->after('blank_count');
        }
        if (!Schema::hasColumn('analysis_batches', 'special_char_count')) {
            $table->integer('special_char_count')->default(0)->after('na_count');
        }
        if (!Schema::hasColumn('analysis_batches', 'valid_count')) {
            $table->integer('valid_count')->default(0)->after('special_char_count');
        }
    });
}

    /**
     * Reverse the migrations.
     */
public function down()
{
    Schema::table('analysis_batches', function (Blueprint $table) {
        $table->dropColumn(['blank_count', 'na_count', 'special_char_count', 'valid_count']);
    });
}
};
