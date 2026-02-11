<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            // Maps to CSV: "Office/Unit Visited"
            $table->string('office'); 
            
            // Maps to CSV: "Suggestions on how we can further improve our services"
            $table->text('comment'); 
            
            $table->string('services_availed')->nullable();
            $table->string('sentiment');
            $table->string('topic')->nullable();
            $table->string('confidence')->nullable(); 
            $table->string('method')->default('BERT/XLMR');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};