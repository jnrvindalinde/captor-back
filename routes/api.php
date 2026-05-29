<?php

use App\Http\Controllers\Api\Admin\ClientController;
use App\Http\Controllers\Api\Admin\CollectionController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\LeadController;
use App\Http\Controllers\Api\Admin\MediaController;
use App\Http\Controllers\Api\Admin\MeetingController;
use App\Http\Controllers\Api\Admin\NavigationController;
use App\Http\Controllers\Api\Admin\PageController;
use App\Http\Controllers\Api\Admin\PostController;
use App\Http\Controllers\Api\Admin\ResourceController;
use App\Http\Controllers\Api\Admin\SiteGlobalController;
use App\Http\Controllers\Api\Admin\StoryController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PublicCollectionController;
use App\Http\Controllers\Api\PublicFormController;
use App\Http\Controllers\Api\PublicNavigationController;
use App\Http\Controllers\Api\PublicPageController;
use App\Http\Controllers\Api\PublicSiteGlobalController;
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

    Route::get('collections/{slug}', [PublicCollectionController::class, 'show']);
    Route::get('pages/{slug}',                 [PublicPageController::class, 'show']);
    Route::get('pages/{slug}/preview/{token}', [PublicPageController::class, 'preview']);
    Route::get('menus/{slug}',       [PublicNavigationController::class, 'show']);
    Route::get('globals',            [PublicSiteGlobalController::class, 'show']);
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
    Route::patch('leads/{lead}/notes/{note}', [LeadController::class, 'updateNote']);
    Route::post('leads/{lead}/meetings', [LeadController::class, 'addMeeting']);
    Route::post('leads/{lead}/decision', [LeadController::class, 'decideApplication']);
    Route::post('leads/{lead}/convert',  [LeadController::class, 'convertToClient']);

    Route::get('meetings', [MeetingController::class, 'index']);

    Route::apiResource('posts',     PostController::class);
    Route::apiResource('resources', ResourceController::class);
    Route::apiResource('stories',   StoryController::class);

    Route::get('clients',           [ClientController::class, 'index']);
    Route::get('clients/{client}',  [ClientController::class, 'show']);
    Route::patch('clients/{client}', [ClientController::class, 'update']);

    // CMS — Media library (Phase 1).
    Route::get('media',           [MediaController::class, 'index']);
    Route::post('media',          [MediaController::class, 'store']);
    Route::get('media/{medium}',  [MediaController::class, 'show']);
    Route::patch('media/{medium}', [MediaController::class, 'update']);
    Route::delete('media/{medium}', [MediaController::class, 'destroy']);

    // CMS — Collections (Phase 2).
    Route::get('collections',                                      [CollectionController::class, 'index']);
    Route::get('collections/{collection}',                         [CollectionController::class, 'show']);
    Route::post('collections/{collection}/items',                  [CollectionController::class, 'storeItem']);
    Route::patch('collections/{collection}/items/{item}',          [CollectionController::class, 'updateItem']);
    Route::delete('collections/{collection}/items/{item}',         [CollectionController::class, 'destroyItem']);
    Route::post('collections/{collection}/items/reorder',          [CollectionController::class, 'reorder']);

    // CMS — Pages (Phase 3).
    Route::get('cms/section-registry',                             [PageController::class, 'registry']);
    Route::get('pages',                                            [PageController::class, 'index']);
    Route::post('pages',                                           [PageController::class, 'store']);
    Route::get('pages/{page}',                                     [PageController::class, 'show']);
    Route::patch('pages/{page}',                                   [PageController::class, 'update']);
    Route::delete('pages/{page}',                                  [PageController::class, 'destroy']);
    Route::post('pages/{page}/publish',                            [PageController::class, 'publish']);
    Route::post('pages/{page}/unpublish',                          [PageController::class, 'unpublish']);
    Route::get('pages/{page}/audits',                              [PageController::class, 'audits']);
    Route::post('pages/{page}/sections',                           [PageController::class, 'storeSection']);
    Route::patch('pages/{page}/sections/{section}',                [PageController::class, 'updateSection']);
    Route::delete('pages/{page}/sections/{section}',               [PageController::class, 'destroySection']);
    Route::post('pages/{page}/sections/reorder',                   [PageController::class, 'reorderSections']);

    // CMS — Navigation menus (Phase 6).
    Route::get('menus',                                            [NavigationController::class, 'index']);
    Route::get('menus/{menu}',                                     [NavigationController::class, 'show']);
    Route::post('menus/{menu}/items',                              [NavigationController::class, 'storeItem']);
    Route::patch('menus/{menu}/items/{item}',                      [NavigationController::class, 'updateItem']);
    Route::delete('menus/{menu}/items/{item}',                     [NavigationController::class, 'destroyItem']);
    Route::post('menus/{menu}/items/reorder',                      [NavigationController::class, 'reorder']);

    // CMS — Site globals (Phase 7).
    Route::get('globals',                                          [SiteGlobalController::class, 'show']);
    Route::patch('globals',                                        [SiteGlobalController::class, 'update']);
});
