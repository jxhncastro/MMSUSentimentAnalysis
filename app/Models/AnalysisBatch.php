<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalysisBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename', 
        'total_rows', 
        'processed_rows', 
        'status',
        // NEW FIELDS
        'blank_count',
        'na_count',
        'special_char_count',
        'valid_count'
    ];
}