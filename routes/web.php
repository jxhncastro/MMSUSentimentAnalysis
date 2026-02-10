<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DatasetController;
use App\Http\Controllers\SentimentController;
use App\Http\Controllers\DashboardController;

// --- 1. AUTHENTICATION ---
Route::get('/', function () {
    return Inertia::render('Auth/Login');
})->name('login');

// --- 2. DASHBOARD (Main View) ---
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// --- 3. DATA MANAGEMENT (Upload & Clear) ---
// This handles the CSV Upload
Route::post('/dataset/upload', [DatasetController::class, 'upload'])->name('dataset.upload');

// This handles the "Clear All Data" button (DELETE method)
Route::post('/clear-data', [SentimentController::class, 'clearData'])->name('data.clear');

// --- 4. AI TESTING (The "Test AI" Page) ---
Route::get('/test-ai', function () {
    return Inertia::render('TestAI');
})->name('test-ai');

// This connects to the 'analyze' function we just fixed in SentimentController
Route::post('/ai/analyze', [SentimentController::class, 'analyze'])->name('ai.analyze');

// --- 5. UTILITY ROUTES ---
// Progress Bar Polling
Route::get('/api/analysis-status', [DatasetController::class, 'getStatus'])->name('api.status');

// View All Feedback Table
Route::get('/all-feedback', [DashboardController::class, 'allFeedback'])->name('feedback.index');

// Static Pages (Keep if used in sidebar)
Route::get('/operating-units', function () {
    return Inertia::render('OperatingUnits');
})->name('operating-units');

Route::get('/add-csv', function () {
    return Inertia::render('AddCsv');
})->name('add-csv');