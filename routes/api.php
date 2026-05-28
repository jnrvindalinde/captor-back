<?php

use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\LeadController;
use App\Http\Controllers\Api\Admin\PostController;
use App\Http\Controllers\Api\Admin\ResourceController;
use App\Http\Controllers\Api\Admin\StoryController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PublicFormController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public form submissions (no auth)
|--------------------------------------------------------------------------
*/
Route::prefix('public')->group(function () {
    Route::post('contact',      [PublicFormController::class, 'contact']);
    Route::post('org-inquiry',  [PublicFormController::class, 'orgInquiry']);
    Route::post('applications', [PublicFormController::class, 'application']);
});

/*
|--------------------------------------------------------------------------
| Auth (Sanctum personal access tokens)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me',      [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin portal API (requires authenticated admin user)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::get('leads',                  [LeadController::class, 'index']);
    Route::get('leads/{lead}',           [LeadController::class, 'show']);
    Route::patch('leads/{lead}',         [LeadController::class, 'update']);
    Route::post('leads/{lead}/notes',    [LeadController::class, 'addNote']);
    Route::post('leads/{lead}/meetings', [LeadController::class, 'addMeeting']);
    Route::post('leads/{lead}/decision', [LeadController::class, 'decideApplication']);

    Route::apiResource('posts',     PostController::class);
    Route::apiResource('resources', ResourceController::class);
    Route::apiResource('stories',   StoryController::class);
});
