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
            // Add the missing column
            $table->unsignedBigInteger('analysis_batch_id')->nullable()->after('id');
            
            // Optional: Add a foreign key if you have an analysis_batches table
            // $table->foreign('analysis_batch_id')->references('id')->on('analysis_batches')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn('analysis_batch_id');
        });
    }
};
