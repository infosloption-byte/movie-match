<?php

use App\Http\Controllers\MovieController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Movie recommendation API routes
|
*/

Route::prefix('movies')->group(function () {
    // Import movies from TMDB
    Route::post('/import', [MovieController::class, 'import']);

    // Search movies by title
    Route::get('/search', [MovieController::class, 'search']);

    // Batch generate embeddings
    Route::post('/batch-generate-embeddings', [MovieController::class, 'batchGenerateEmbeddings']);

    // Single movie operations
    Route::get('/{id}', [MovieController::class, 'show']);
    Route::post('/{id}/generate-embedding', [MovieController::class, 'generateEmbedding']);
    Route::get('/{id}/similar', [MovieController::class, 'similar']);
});
