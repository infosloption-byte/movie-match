<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\MovieEmbedding;
use App\Services\EmbeddingService;
use Illuminate\Console\Command;

class GenerateEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'embeddings:generate
                            {--limit= : Maximum number of embeddings to generate}
                            {--fast : Use faster speed (reduce delays)}';

    /**
     * The console command description.
     */
    protected $description = 'Generate embeddings for movies that don\'t have them yet';

    public function __construct(
        private EmbeddingService $embeddingService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $fastMode = $this->option('fast');

        // Set delay based on mode
        $embeddingDelay = $fastMode ? 0.5 : 2; // 0.5s vs 2s between embeddings

        $this->info("🤖 Generating embeddings for movies...");
        if ($fastMode) {
            $this->warn("⚡ Fast mode enabled - using reduced delays");
        }
        $this->newLine();

        // Get movies without embeddings
        $query = Movie::whereDoesntHave('embedding')
            ->whereNotNull('plot_summary')
            ->where('plot_summary', '!=', '');

        if ($limit) {
            $query->limit((int)$limit);
        }

        $movies = $query->get();

        if ($movies->isEmpty()) {
            $this->info("✅ All movies already have embeddings!");
            return 0;
        }

        $this->info("Found {$movies->count()} movies without embeddings");
        $this->newLine();

        $bar = $this->output->createProgressBar($movies->count());
        $bar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($movies as $movie) {
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

                    // Store embedding
                    MovieEmbedding::updateOrCreate(
                        ['movie_id' => $movie->id],
                        ['embedding' => $embeddingString]
                    );

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
        $this->info("🎉 Embedding generation complete!");

        return 0;
    }
}
