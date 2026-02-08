<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AIController;
use App\Http\Controllers\DatasetController;

Route::get('/', function () {
    return Inertia::render('Auth/Login');
})->name('login');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

Route::get('/operating-units', function () {
    return Inertia::render('OperatingUnits');
})->name('operating-units');

Route::get('/add-csv', function () {
    return Inertia::render('AddCsv');
})->name('add-csv');

// 1. This route DISPLAYS the Tester Page
Route::get('/test-ai', function () {
    return Inertia::render('TestAI');
})->name('test-ai');

// 2. This route HANDLES the AI Logic (The missing piece!)
Route::post('/ai/analyze', [AIController::class, 'analyze'])->name('ai.analyze');
Route::post('/dataset/upload', [DatasetController::class, 'upload'])->name('dataset.upload');