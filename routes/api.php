<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\UserPreferenceController;
use App\Models\Source;
use App\Models\Category;

Route::prefix('articles')->group(function () {
    Route::get('/', [ArticleController::class, 'index']);
    Route::get('/{id}', [ArticleController::class, 'show']);
});

Route::get('/sources', fn() => response()->json(Source::all()));
Route::get('/categories', fn() => response()->json(Category::all()));

// User preferences routes
// Works without authentication by providing user_id parameter
// For production, you may want to add authentication middleware (e.g., 'auth:sanctum')
Route::prefix('preferences')->group(function () {
    Route::get('/', [UserPreferenceController::class, 'index']);
    Route::post('/sources', [UserPreferenceController::class, 'updateSources']);
    Route::post('/categories', [UserPreferenceController::class, 'updateCategories']);
    Route::post('/authors', [UserPreferenceController::class, 'updateAuthors']);
    Route::delete('/', [UserPreferenceController::class, 'clear']);
});
