<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'operating_unit',
        'feedback_text',
        'sentiment',
        'confidence',
        'service_availed'
    ];
}