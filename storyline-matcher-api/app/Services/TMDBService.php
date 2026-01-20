<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * TMDB Service
 *
 * Handles all interactions with The Movie Database (TMDB) API.
 * Includes rate limiting to respect TMDB's API limits (~40 requests per 10 seconds).
 */
class TMDBService
{
    private string $apiKey;
    private string $baseUrl;
    private string $imageBaseUrl;
    private int $rateLimitPerSecond;
    private string $rateLimitKey = 'tmdb_api_rate_limit';

    public function __construct()
    {
        $this->apiKey = config('services.tmdb.api_key');
        $this->baseUrl = config('services.tmdb.base_url');
        $this->imageBaseUrl = config('services.tmdb.image_base_url');
        $this->rateLimitPerSecond = config('services.tmdb.rate_limit_per_second', 4);
    }

    /**
     * Search for movies by title
     *
     * @param string $query Search query
     * @param int $page Page number (default: 1)
     * @return array TMDB API response
     */
    public function searchMovies(string $query, int $page = 1): array
    {
        $this->waitForRateLimit();

        $request = Http::withOptions(['verify' => config('app.env') !== 'local'])
            ->get("{$this->baseUrl}/search/movie", [
                'api_key' => $this->apiKey,
                'query' => $query,
                'page' => $page,
                'language' => 'en-US',
            ]);

        if ($request->failed()) {
            Log::error('TMDB API search failed', [
                'query' => $query,
                'status' => $request->status(),
                'body' => $request->body(),
            ]);
            throw new \Exception('Failed to search movies from TMDB');
        }

        return $request->json();
    }

    /**
     * Get detailed movie information by TMDB ID
     *
     * @param int $tmdbId TMDB movie ID
     * @return array Movie details
     */
    public function getMovieDetails(int $tmdbId): array
    {
        $this->waitForRateLimit();

        $response = Http::withOptions(['verify' => config('app.env') !== 'local'])
            ->get("{$this->baseUrl}/movie/{$tmdbId}", [
                'api_key' => $this->apiKey,
                'language' => 'en-US',
            ]);

        if ($response->failed()) {
            Log::error('TMDB API get movie details failed', [
                'tmdb_id' => $tmdbId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception("Failed to get movie details from TMDB for ID: {$tmdbId}");
        }

        return $response->json();
    }

    /**
     * Get popular movies
     *
     * @param int $page Page number (default: 1)
     * @return array TMDB API response with popular movies
     */
    public function getPopularMovies(int $page = 1): array
    {
        $this->waitForRateLimit();

        $response = Http::withOptions(['verify' => config('app.env') !== 'local'])
            ->get("{$this->baseUrl}/movie/popular", [
                'api_key' => $this->apiKey,
                'page' => $page,
                'language' => 'en-US',
            ]);

        if ($response->failed()) {
            Log::error('TMDB API get popular movies failed', [
                'page' => $page,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to get popular movies from TMDB');
        }

        return $response->json();
    }

    /**
     * Get discover movies with filters
     *
     * @param array $filters Filters to apply (year, genre, etc.)
     * @param int $page Page number
     * @return array TMDB API response
     */
    public function discoverMovies(array $filters = [], int $page = 1): array
    {
        $this->waitForRateLimit();

        $params = array_merge([
            'api_key' => $this->apiKey,
            'page' => $page,
            'language' => 'en-US',
            'sort_by' => 'popularity.desc',
        ], $filters);

        $response = Http::withOptions(['verify' => config('app.env') !== 'local'])
            ->get("{$this->baseUrl}/discover/movie", $params);

        if ($response->failed()) {
            Log::error('TMDB API discover movies failed', [
                'filters' => $filters,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to discover movies from TMDB');
        }

        return $response->json();
    }

    /**
     * Build full poster URL from TMDB poster path
     *
     * @param string|null $posterPath Poster path from TMDB
     * @param string $size Image size (w92, w154, w185, w342, w500, w780, original)
     * @return string|null Full poster URL or null
     */
    public function buildPosterUrl(?string $posterPath, string $size = 'w500'): ?string
    {
        if (!$posterPath) {
            return null;
        }

        return "{$this->imageBaseUrl}/{$size}{$posterPath}";
    }

    /**
     * Rate limiting mechanism
     *
     * Uses a sliding window approach to limit API calls per second.
     * Prevents exceeding TMDB's rate limits.
     */
    private function waitForRateLimit(): void
    {
        $now = microtime(true);
        $timestamps = Cache::get($this->rateLimitKey, []);

        // Remove timestamps older than 1 second
        $timestamps = array_filter($timestamps, function ($timestamp) use ($now) {
            return ($now - $timestamp) < 1;
        });

        // If we've hit the rate limit, wait
        if (count($timestamps) >= $this->rateLimitPerSecond) {
            $oldestTimestamp = min($timestamps);
            $waitTime = 1 - ($now - $oldestTimestamp);

            if ($waitTime > 0) {
                usleep((int) ($waitTime * 1000000)); // Convert to microseconds
            }

            // Refresh timestamps after waiting
            $now = microtime(true);
            $timestamps = array_filter($timestamps, function ($timestamp) use ($now) {
                return ($now - $timestamp) < 1;
            });
        }

        // Add current timestamp
        $timestamps[] = $now;
        Cache::put($this->rateLimitKey, $timestamps, 2); // Cache for 2 seconds
    }

    /**
     * Transform TMDB movie data to our database format
     *
     * @param array $tmdbMovie Raw movie data from TMDB API
     * @return array Formatted movie data for database insertion
     */
    public function transformMovieData(array $tmdbMovie): array
    {
        return [
            'tmdb_id' => $tmdbMovie['id'],
            'title' => $tmdbMovie['title'] ?? $tmdbMovie['original_title'] ?? 'Unknown',
            'plot_summary' => $tmdbMovie['overview'] ?? null,
            'release_year' => isset($tmdbMovie['release_date'])
                ? (int) substr($tmdbMovie['release_date'], 0, 4)
                : null,
            'poster_url' => $this->buildPosterUrl($tmdbMovie['poster_path'] ?? null),
            'popularity' => $tmdbMovie['popularity'] ?? 0,
        ];
    }
}
