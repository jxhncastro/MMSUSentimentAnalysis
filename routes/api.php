<?php
use App\Http\Controllers\OperatingUnitController;

Route::get('/operating-units', [OperatingUnitController::class, 'index']);
Route::post('/operating-units/upload', [OperatingUnitController::class, 'upload']);
