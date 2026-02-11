<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = [
        'analysis_batch_id',
        'operating_unit',
        'feedback_text',
        'sentiment',
        'confidence',
        'method'
    ];
}