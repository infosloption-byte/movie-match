<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\MovieSimilarity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Similarity Service
 *
 * Calculates and manages movie similarities based on plot embeddings.
 * Uses cosine similarity to measure how similar two movies are based on their plots.
 *
 * Cosine Similarity Explained:
 * - Measures the cosine of the angle between two vectors
 * - Range: -1 to 1 (we use 0 to 1 for normalized vectors)
 * - 1 = identical, 0 = orthogonal/unrelated, -1 = opposite
 * - Formula: cos(θ) = (A · B) / (||A|| × ||B||)
 *   where A · B is dot product and ||A|| is magnitude
 */
class SimilarityService
{
    private float $similarityThreshold;
    private int $similarMoviesLimit;

    public function __construct()
    {
        $this->similarityThreshold = config('services.similarity.threshold', 0.7);
        $this->similarMoviesLimit = config('services.similarity.limit', 10);
    }

    /**
     * Find similar movies using vector similarity search
     *
     * PostgreSQL: Uses pgvector's cosine distance operator (<=>)  with HNSW index
     * MySQL: Calculates similarity in PHP (slower, loads all embeddings)
     *
     * Note: For large datasets (>1000 movies), PostgreSQL is strongly recommended
     * for better performance.
     *
     * @param int $movieId Movie ID to find similar movies for
     * @param int|null $limit Maximum number of similar movies to return
     * @param float|null $threshold Minimum similarity score threshold
     * @return array Array of similar movies with scores
     */
    public function findSimilarMovies(
        int $movieId,
        ?int $limit = null,
        ?float $threshold = null
    ): array {
        $limit = $limit ?? $this->similarMoviesLimit;
        $threshold = $threshold ?? $this->similarityThreshold;

        $movie = Movie::with('embedding')->findOrFail($movieId);

        if (!$movie->embedding || !$movie->embedding->embedding) {
            throw new \Exception("Movie ID {$movieId} does not have an embedding");
        }

        // Check database driver and use appropriate method
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            return $this->findSimilarMoviesPostgreSQL($movie, $limit, $threshold);
        } else {
            return $this->findSimilarMoviesMySQL($movie, $limit, $threshold);
        }
    }

    /**
     * Find similar movies using PostgreSQL pgvector
     *
     * Uses pgvector's cosine distance operator (<=>)  to find nearest neighbors.
     * The HNSW index makes this search very fast even with thousands of movies.
     *
     * Note: pgvector uses cosine DISTANCE (1 - cosine_similarity), so we convert
     * back to similarity score for better UX (higher = more similar).
     */
    private function findSimilarMoviesPostgreSQL(Movie $movie, int $limit, float $threshold): array
    {
        // Use raw SQL for vector similarity search
        // The <=> operator calculates cosine distance
        // We exclude the movie itself and filter by threshold
        $results = DB::select("
            SELECT
                m.id,
                m.tmdb_id,
                m.title,
                m.plot_summary,
                m.release_year,
                m.poster_url,
                m.popularity,
                1 - (me.embedding <=> ?) as similarity_score
            FROM movies m
            INNER JOIN movie_embeddings me ON me.movie_id = m.id
            WHERE m.id != ?
                AND me.embedding IS NOT NULL
                AND 1 - (me.embedding <=> ?) >= ?
            ORDER BY me.embedding <=> ?
            LIMIT ?
        ", [
            $movie->embedding->embedding,  // Query vector
            $movie->id,                     // Exclude self
            $movie->embedding->embedding,  // Query vector for threshold check
            $threshold,                     // Similarity threshold
            $movie->embedding->embedding,  // Query vector for ordering
            $limit,                         // Result limit
        ]);

        return array_map(function ($row) {
            return [
                'id' => $row->id,
                'tmdb_id' => $row->tmdb_id,
                'title' => $row->title,
                'plot_summary' => $row->plot_summary,
                'release_year' => $row->release_year,
                'poster_url' => $row->poster_url,
                'popularity' => (float) $row->popularity,
                'similarity_score' => round((float) $row->similarity_score, 4),
            ];
        }, $results);
    }

    /**
     * Find similar movies using MySQL (PHP-based calculation)
     *
     * Loads all movie embeddings and calculates cosine similarity in PHP.
     * This is slower than PostgreSQL pgvector but works without extensions.
     *
     * Performance note: With 1000+ movies, this can take several seconds.
     * Consider caching results or using PostgreSQL for production.
     */
    private function findSimilarMoviesMySQL(Movie $movie, int $limit, float $threshold): array
    {
        // Get the query embedding as an array
        $queryEmbedding = json_decode($movie->embedding->embedding, true);

        if (!$queryEmbedding || !is_array($queryEmbedding)) {
            throw new \Exception("Invalid embedding format for movie ID {$movie->id}");
        }

        // Load all other movies with embeddings
        $candidates = Movie::where('id', '!=', $movie->id)
            ->whereHas('embedding')
            ->with('embedding')
            ->get();

        $similarities = [];

        foreach ($candidates as $candidate) {
            if (!$candidate->embedding || !$candidate->embedding->embedding) {
                continue;
            }

            // Decode JSON embedding
            $candidateEmbedding = json_decode($candidate->embedding->embedding, true);

            if (!$candidateEmbedding || !is_array($candidateEmbedding)) {
                continue;
            }

            // Calculate cosine similarity
            $similarity = $this->calculateCosineSimilarity($queryEmbedding, $candidateEmbedding);

            // Only include if above threshold
            if ($similarity >= $threshold) {
                $similarities[] = [
                    'id' => $candidate->id,
                    'tmdb_id' => $candidate->tmdb_id,
                    'title' => $candidate->title,
                    'plot_summary' => $candidate->plot_summary,
                    'release_year' => $candidate->release_year,
                    'poster_url' => $candidate->poster_url,
                    'popularity' => (float) $candidate->popularity,
                    'similarity_score' => round($similarity, 4),
                ];
            }
        }

        // Sort by similarity score (descending) and limit results
        usort($similarities, function ($a, $b) {
            return $b['similarity_score'] <=> $a['similarity_score'];
        });

        return array_slice($similarities, 0, $limit);
    }

    /**
     * Calculate cosine similarity between two embeddings (PHP implementation)
     *
     * This is a pure PHP implementation for cases where we need to calculate
     * similarity outside of the database context.
     *
     * Formula breakdown:
     * 1. Dot product: sum of element-wise multiplication
     * 2. Magnitude: square root of sum of squared elements
     * 3. Cosine similarity: dot product / (magnitude_A × magnitude_B)
     *
     * @param array $embedding1 First embedding vector
     * @param array $embedding2 Second embedding vector
     * @return float Similarity score (0-1)
     */
    public function calculateCosineSimilarity(array $embedding1, array $embedding2): float
    {
        if (count($embedding1) !== count($embedding2)) {
            throw new \Exception('Embeddings must have the same dimension');
        }

        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        // Calculate dot product and magnitudes in a single loop for efficiency
        for ($i = 0; $i < count($embedding1); $i++) {
            $dotProduct += $embedding1[$i] * $embedding2[$i];
            $magnitude1 += $embedding1[$i] * $embedding1[$i];
            $magnitude2 += $embedding2[$i] * $embedding2[$i];
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        // Avoid division by zero
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    /**
     * Cache similarity scores for a movie pair
     *
     * Stores pre-calculated similarity scores in the database for faster retrieval.
     * This is useful for frequently accessed movie pairs.
     *
     * @param int $movieId First movie ID
     * @param int $similarMovieId Second movie ID
     * @param float $score Similarity score
     * @return void
     */
    public function cacheSimilarity(int $movieId, int $similarMovieId, float $score): void
    {
        try {
            MovieSimilarity::updateOrCreate(
                [
                    'movie_id' => $movieId,
                    'similar_movie_id' => $similarMovieId,
                ],
                [
                    'similarity_score' => $score,
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to cache similarity', [
                'movie_id' => $movieId,
                'similar_movie_id' => $similarMovieId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get cached similar movies
     *
     * Retrieves pre-calculated similarities from the cache table.
     *
     * @param int $movieId Movie ID
     * @param int|null $limit Maximum results
     * @param float|null $threshold Minimum similarity
     * @return array Cached similar movies
     */
    public function getCachedSimilarMovies(
        int $movieId,
        ?int $limit = null,
        ?float $threshold = null
    ): array {
        $limit = $limit ?? $this->similarMoviesLimit;
        $threshold = $threshold ?? $this->similarityThreshold;

        $similarities = MovieSimilarity::where('movie_id', $movieId)
            ->where('similarity_score', '>=', $threshold)
            ->orderByDesc('similarity_score')
            ->limit($limit)
            ->with('similarMovie')
            ->get();

        return $similarities->map(function ($similarity) {
            return [
                'id' => $similarity->similarMovie->id,
                'tmdb_id' => $similarity->similarMovie->tmdb_id,
                'title' => $similarity->similarMovie->title,
                'plot_summary' => $similarity->similarMovie->plot_summary,
                'release_year' => $similarity->similarMovie->release_year,
                'poster_url' => $similarity->similarMovie->poster_url,
                'popularity' => $similarity->similarMovie->popularity,
                'similarity_score' => round($similarity->similarity_score, 4),
            ];
        })->toArray();
    }

    /**
     * Batch calculate and cache similarities for a movie
     *
     * Useful for pre-computing similarities for popular movies.
     *
     * @param int $movieId Movie ID to calculate similarities for
     * @return int Number of similarities cached
     */
    public function batchCacheSimilarities(int $movieId): int
    {
        $similarMovies = $this->findSimilarMovies($movieId);

        foreach ($similarMovies as $similarMovie) {
            $this->cacheSimilarity(
                $movieId,
                $similarMovie['id'],
                $similarMovie['similarity_score']
            );
        }

        return count($similarMovies);
    }
}
