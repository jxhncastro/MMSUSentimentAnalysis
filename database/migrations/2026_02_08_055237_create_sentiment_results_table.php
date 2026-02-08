<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sentiment_results', function (Blueprint $table) {
            $table->id();
            $table->string('office_name')->nullable();       // Office/Unit Visited
            $table->text('services_availed')->nullable();    // We will merge all 44 columns here
            $table->text('comment')->nullable();             // cleaned_text
            $table->string('sentiment');                     // predicted_sentiment (Positive/Negative)
            $table->string('aspect');                        // predicted_category (Cleanliness, etc.)
            $table->float('confidence_score');               // confidence_score
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sentiment_results');
    }
};