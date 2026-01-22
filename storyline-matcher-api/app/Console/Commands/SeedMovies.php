<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\MovieEmbedding;
use App\Services\TMDBService;
use App\Services\EmbeddingService;
use Illuminate\Console\Command;

class SeedMovies extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'movies:seed
                            {--pages=3 : Number of pages to import}
                            {--skip-embeddings : Skip embedding generation (import only)}
                            {--fast : Use faster speeds (reduce delays)}';

    /**
     * The console command description.
     */
    protected $description = 'Import popular movies from TMDB and generate embeddings';

    public function __construct(
        private TMDBService $tmdbService,
        private EmbeddingService $embeddingService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pages = (int) $this->option('pages');
        $skipEmbeddings = $this->option('skip-embeddings');
        $fastMode = $this->option('fast');

        // Set delays based on mode
        $importDelay = $fastMode ? 0.25 : 1; // 0.25s vs 1s between TMDB requests
        $embeddingDelay = $fastMode ? 0.5 : 2; // 0.5s vs 2s between embeddings

        $this->info("🎬 Starting movie import...");
        $this->info("This will import {$pages} pages of popular movies from TMDB");
        if ($fastMode) {
            $this->warn("⚡ Fast mode enabled - using reduced delays");
        }
        if ($skipEmbeddings) {
            $this->warn("⏭️  Skipping embedding generation - import only");
        }
        $this->newLine();

        $totalImported = 0;

        // Import movies page by page
        for ($page = 1; $page <= $pages; $page++) {
            $this->info("📥 Importing page {$page}/{$pages}...");

            try {
                $response = $this->tmdbService->getPopularMovies($page);
                $movies = $response['results'] ?? [];

                foreach ($movies as $movieData) {
                    // Check if movie already exists
                    $existingMovie = Movie::where('tmdb_id', $movieData['id'])->first();

                    if ($existingMovie) {
                        $this->line("  ⏭️  Skipping: {$movieData['title']} (already exists)");
                        continue;
                    }

                    // Create movie
                    $movie = Movie::create([
                        'tmdb_id' => $movieData['id'],
                        'title' => $movieData['title'],
                        'plot_summary' => $movieData['overview'] ?? null,
                        'release_year' => isset($movieData['release_date'])
                            ? (int) substr($movieData['release_date'], 0, 4)
                            : null,
                        'poster_url' => isset($movieData['poster_path'])
                            ? "https://image.tmdb.org/t/p/w500{$movieData['poster_path']}"
                            : null,
                        'popularity' => $movieData['popularity'] ?? 0,
                    ]);

                    $this->line("  ✅ Imported: {$movie->title}");
                    $totalImported++;
                }

                // Small delay to avoid rate limiting
                usleep((int)($importDelay * 1000000)); // Convert to microseconds

            } catch (\Exception $e) {
                $this->error("  ❌ Error importing page {$page}: {$e->getMessage()}");
                continue;
            }
        }

        $this->newLine();
        $this->info("✅ Imported {$totalImported} new movies!");
        $this->newLine();

        // Skip embeddings if requested
        if ($skipEmbeddings) {
            $this->info("⏭️  Skipping embedding generation as requested.");
            $this->info("💡 Run 'php artisan embeddings:generate' later to generate embeddings.");
            return 0;
        }

        // Generate embeddings
        $this->info("🤖 Generating embeddings...");
        $moviesWithoutEmbeddings = Movie::whereDoesntHave('embedding')
            ->whereNotNull('plot_summary')
            ->where('plot_summary', '!=', '')
            ->get();

        if ($moviesWithoutEmbeddings->isEmpty()) {
            $this->info("All movies already have embeddings!");
            return 0;
        }

        $this->info("Found {$moviesWithoutEmbeddings->count()} movies without embeddings");
        $this->newLine();

        $bar = $this->output->createProgressBar($moviesWithoutEmbeddings->count());
        $bar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($moviesWithoutEmbeddings as $movie) {
            $maxRetries = 3;
            $retryDelay = 20; // Start with 20 seconds for rate limit

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    // Prepare text
                    $text = $this->embeddingService->prepareText($movie->plot_summary);

                    // Generate embedding
                    $embedding = $this->embeddingService->generateEmbedding($text);

                    // Format for database
                    $embeddingString = $this->embeddingService->formatForDatabase($embedding);

                    // Save to database
                    MovieEmbedding::create([
                        'movie_id' => $movie->id,
                        'embedding' => $embeddingString,
                    ]);

                    $successCount++;
                    $bar->advance();

                    // Delay to avoid rate limits
                    usleep((int)($embeddingDelay * 1000000)); // Convert to microseconds
                    break; // Success, exit retry loop

                } catch (\Exception $e) {
                    $isRateLimit = str_contains($e->getMessage(), 'rate limit');

                    if ($isRateLimit && $attempt < $maxRetries) {
                        // Rate limit error - wait and retry
                        $this->newLine();
                        $this->warn("Rate limited for: {$movie->title}. Waiting {$retryDelay}s before retry {$attempt}/{$maxRetries}...");
                        sleep($retryDelay);
                        $retryDelay *= 2; // Exponential backoff
                        continue;
                    }

                    // Final failure or non-rate-limit error
                    $errorCount++;
                    $bar->advance();
                    $this->newLine();
                    $this->error("Failed to generate embedding for: {$movie->title}");
                    $this->error("Error: {$e->getMessage()}");

                    // Show full error details for debugging
                    if ($this->output->isVerbose()) {
                        $this->error("Stack trace: " . $e->getTraceAsString());
                    }

                    break; // Exit retry loop
                }
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Generated {$successCount} embeddings");
        if ($errorCount > 0) {
            $this->warn("⚠️  {$errorCount} embeddings failed");
        }

        $this->newLine();
        $this->info("🎉 All done! Your database is ready.");

        return 0;
    }
}
