<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DatasetController;
use App\Http\Controllers\SentimentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperatingUnitController;

// --- 1. AUTHENTICATION ---
Route::get('/', function () {
    return Inertia::render('Auth/Login');
})->name('login');

// --- 2. ANALYTICS DASHBOARDS ---
// Main Overview Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// ✅ FIXED: Points to OperatingUnitController to load dynamic charts and thematic summaries
Route::get('/operating-units', [OperatingUnitController::class, 'index'])->name('operating-units');

// --- 3. DATA MANAGEMENT ---
// View for the Upload Page
Route::get('/add-csv', function () {
    return Inertia::render('AddCsv');
})->name('add-csv');

// Handles the actual File Upload
Route::post('/dataset/upload', [DatasetController::class, 'upload'])->name('dataset.upload');

// Handles the progress bar polling for background processing
Route::get('/api/analysis-status', [DatasetController::class, 'getStatus'])->name('api.status');

// Handles clearing all feedback from the database
Route::post('/clear-data', [SentimentController::class, 'clearData'])->name('data.clear');

// --- 4. AI TESTING ---
Route::get('/test-ai', function () {
    return Inertia::render('TestAI');
})->name('test-ai');

// Handles the single-text analysis for the AI Tester
Route::post('/ai/analyze', [SentimentController::class, 'analyze'])->name('ai.analyze');

// --- 5. UTILITY ---
// Optional: If you still need a flat list of all feedback
Route::get('/all-feedback', [DashboardController::class, 'allFeedback'])->name('feedback.index');