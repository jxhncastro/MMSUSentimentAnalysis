<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperatingUnit extends Model
{
    protected $guarded = [];

    public function services()
    {
        return $this->hasMany(UnitService::class);
    }
}