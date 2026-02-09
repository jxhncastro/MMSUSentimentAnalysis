<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalysisBatch extends Model
{
    use HasFactory;

    protected $fillable = ['filename', 'total_rows', 'processed_rows', 'status'];
}