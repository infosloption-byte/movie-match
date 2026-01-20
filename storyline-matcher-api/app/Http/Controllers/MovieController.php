<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\MovieEmbedding;
use App\Services\TMDBService;
use App\Services\EmbeddingService;
use App\Services\SimilarityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MovieController extends Controller
{
    public function __construct(
        private TMDBService $tmdbService,
        private EmbeddingService $embeddingService,
        private SimilarityService $similarityService
    ) {}

    /**
     * Import movies from TMDB
     *
     * POST /api/movies/import
     * Body: { "source": "popular", "page": 1 } or { "source": "search", "query": "inception" }
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'source' => 'required|in:popular,search,discover',
            'query' => 'required_if:source,search|string',
            'page' => 'integer|min:1|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $page = $request->input('page', 1);
            $source = $request->input('source');

            // Fetch movies from TMDB based on source
            $tmdbResponse = match ($source) {
                'popular' => $this->tmdbService->getPopularMovies($page),
                'search' => $this->tmdbService->searchMovies($request->input('query'), $page),
                'discover' => $this->tmdbService->discoverMovies([], $page),
            };

            $imported = 0;
            $skipped = 0;

            foreach ($tmdbResponse['results'] ?? [] as $tmdbMovie) {
                // Skip movies without plot summaries
                if (empty($tmdbMovie['overview'])) {
                    $skipped++;
                    continue;
                }

                $movieData = $this->tmdbService->transformMovieData($tmdbMovie);

                // Create or update movie
                Movie::updateOrCreate(
                    ['tmdb_id' => $movieData['tmdb_id']],
                    $movieData
                );

                $imported++;
            }

            return response()->json([
                'success' => true,
                'message' => "Imported {$imported} movies, skipped {$skipped}",
                'data' => [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'total_results' => $tmdbResponse['total_results'] ?? 0,
                    'page' => $page,
                    'total_pages' => $tmdbResponse['total_pages'] ?? 0,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Movie import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import movies: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate embedding for a movie's plot
     *
     * POST /api/movies/{id}/generate-embedding
     */
    public function generateEmbedding(int $id): JsonResponse
    {
        try {
            $movie = Movie::findOrFail($id);

            if (empty($movie->plot_summary)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Movie does not have a plot summary',
                ], 400);
            }

            // Prepare text for embedding
            $text = $this->embeddingService->prepareText($movie->plot_summary);

            // Generate embedding
            $embedding = $this->embeddingService->generateEmbedding($text);

            // Format for database storage
            $embeddingString = $this->embeddingService->formatForDatabase($embedding);

            // Store embedding
            MovieEmbedding::updateOrCreate(
                ['movie_id' => $movie->id],
                ['embedding' => $embeddingString]
            );

            return response()->json([
                'success' => true,
                'message' => 'Embedding generated successfully',
                'data' => [
                    'movie_id' => $movie->id,
                    'title' => $movie->title,
                    'embedding_dimensions' => count($embedding),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Embedding generation failed', [
                'movie_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate embedding: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search movies by title
     *
     * GET /api/movies/search?query=inception
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:1',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = $request->input('query');
            $perPage = $request->input('per_page', 20);

            // Use appropriate operator based on database driver
            // PostgreSQL: ILIKE (case-insensitive)
            // MySQL: LIKE (case-insensitive by default with utf8mb4_general_ci)
            $operator = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

            $movies = Movie::where('title', $operator, "%{$query}%")
                ->orderBy('popularity', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $movies->items(),
                'pagination' => [
                    'current_page' => $movies->currentPage(),
                    'per_page' => $movies->perPage(),
                    'total' => $movies->total(),
                    'last_page' => $movies->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Movie search failed', [
                'query' => $request->input('query'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get similar movies based on plot embedding
     *
     * GET /api/movies/{id}/similar
     */
    public function similar(int $id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'integer|min:1|max:50',
            'threshold' => 'numeric|min:0|max:1',
            'use_cache' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $movie = Movie::findOrFail($id);
            $limit = $request->input('limit', 10);
            $threshold = $request->input('threshold', 0.7);
            $useCache = $request->input('use_cache', false);

            // Try cache first if requested
            if ($useCache) {
                $similarMovies = $this->similarityService->getCachedSimilarMovies(
                    $id,
                    $limit,
                    $threshold
                );

                if (!empty($similarMovies)) {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'movie' => $movie,
                            'similar_movies' => $similarMovies,
                            'source' => 'cache',
                        ],
                    ]);
                }
            }

            // Calculate similarities using vector search
            $similarMovies = $this->similarityService->findSimilarMovies(
                $id,
                $limit,
                $threshold
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'movie' => $movie,
                    'similar_movies' => $similarMovies,
                    'source' => 'calculated',
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Similar movies search failed', [
                'movie_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to find similar movies: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single movie details
     *
     * GET /api/movies/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $movie = Movie::with('embedding')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $movie->id,
                    'tmdb_id' => $movie->tmdb_id,
                    'title' => $movie->title,
                    'plot_summary' => $movie->plot_summary,
                    'release_year' => $movie->release_year,
                    'poster_url' => $movie->poster_url,
                    'popularity' => $movie->popularity,
                    'has_embedding' => $movie->hasEmbedding(),
                    'created_at' => $movie->created_at,
                    'updated_at' => $movie->updated_at,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Movie not found',
            ], 404);
        }
    }

    /**
     * Batch generate embeddings for all movies without embeddings
     *
     * POST /api/movies/batch-generate-embeddings
     */
    public function batchGenerateEmbeddings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $limit = $request->input('limit', 50);

            // Get movies without embeddings
            $movies = Movie::whereDoesntHave('embedding')
                ->whereNotNull('plot_summary')
                ->limit($limit)
                ->get();

            if ($movies->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No movies need embeddings',
                    'data' => ['processed' => 0],
                ]);
            }

            $processed = 0;
            $failed = 0;

            foreach ($movies as $movie) {
                try {
                    $text = $this->embeddingService->prepareText($movie->plot_summary);
                    $embedding = $this->embeddingService->generateEmbedding($text);
                    $embeddingString = $this->embeddingService->formatForDatabase($embedding);

                    MovieEmbedding::create([
                        'movie_id' => $movie->id,
                        'embedding' => $embeddingString,
                    ]);

                    $processed++;
                } catch (\Exception $e) {
                    Log::error('Failed to generate embedding for movie', [
                        'movie_id' => $movie->id,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Processed {$processed} movies, {$failed} failed",
                'data' => [
                    'processed' => $processed,
                    'failed' => $failed,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Batch embedding generation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Batch processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
