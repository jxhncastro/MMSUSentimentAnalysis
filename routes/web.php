<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DatasetController;
use App\Http\Controllers\SentimentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperatingUnitController;
use App\Http\Controllers\Auth\LoginController; 

// --- 1. PUBLIC ROUTES (No login required) ---
Route::get('/', function () {
    return Inertia::render('Auth/Login');
})->name('login');

Route::post('/login', [LoginController::class, 'store'])->name('login.store');


// --- 2. PROTECTED ROUTES (Login required) ---
// Everything inside this group requires the user to have the 'authenticated' session
Route::middleware(['custom.auth'])->group(function () {

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    
    // Analytics Dashboards
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/operating-units', [OperatingUnitController::class, 'index'])->name('operating-units');

    // Data Management
    Route::get('/add-csv', function () {
        return Inertia::render('AddCsv');
    })->name('add-csv');
    Route::post('/dataset/upload', [DatasetController::class, 'upload'])->name('dataset.upload');
    Route::get('/api/analysis-status', [DatasetController::class, 'getStatus'])->name('api.status');
    Route::post('/clear-data', [SentimentController::class, 'clearData'])->name('data.clear');

    // AI Testing
    Route::get('/test-ai', function () {
        return Inertia::render('TestAI');
    })->name('test-ai');
    Route::post('/ai/analyze', [SentimentController::class, 'analyze'])->name('ai.analyze');

    // Utility
    Route::get('/all-feedback', [DashboardController::class, 'allFeedback'])->name('feedback.index');
});