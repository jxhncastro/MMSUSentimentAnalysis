<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DatasetController;
use App\Http\Controllers\SentimentController;
use App\Http\Controllers\DashboardController; // <--- IMPORT THIS

// 1. Authentication / Home
Route::get('/', function () {
    return Inertia::render('Auth/Login');
})->name('login');

// 2. DASHBOARD (Fixed: Now connects to Controller to get Real Data)
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// 3. Static Pages
Route::get('/operating-units', function () {
    return Inertia::render('OperatingUnits');
})->name('operating-units');

Route::get('/add-csv', function () {
    return Inertia::render('AddCsv');
})->name('add-csv');

// 4. AI Tester
Route::get('/test-ai', function () {
    return Inertia::render('TestAI');
})->name('test-ai');

// 5. Backend Logic (AI & Uploads)
Route::post('/ai/analyze', [SentimentController::class, 'analyze']);
Route::post('/analyze-sentiment', [SentimentController::class, 'analyzeFeedback']);

// 6. Dataset & Batch Processing
// TEMPORARY DEBUG ROUTE
Route::post('/dataset/upload', [DatasetController::class, 'upload'])
    ->name('dataset.upload')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// 7. Polling API (For the Progress Bar)
Route::get('/api/analysis-status', [DatasetController::class, 'getStatus']);

Route::get('/check-limit', function () {
    return [
        'post_max_size' => ini_get('post_max_size'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'real_php_ini_path' => php_ini_loaded_file()
    ];
});

Route::get('/debug-upload', function () {
    return view('debug_upload');
});

Route::get('/all-feedback', [\App\Http\Controllers\DashboardController::class, 'allFeedback'])
    ->name('feedback.index');