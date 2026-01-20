<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Embedding Service
 *
 * Generates vector embeddings using OpenAI's API.
 * These embeddings convert text (movie plot summaries) into numerical vectors
 * that capture semantic meaning, allowing us to find similar movies by comparing vectors.
 */
class EmbeddingService
{
    private string $apiKey;
    private string $model;
    private string $apiUrl = 'https://api.openai.com/v1/embeddings';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'text-embedding-3-small');
    }

    /**
     * Generate embedding vector from text
     *
     * Uses OpenAI's embedding API to convert text into a vector representation.
     * The text-embedding-3-small model produces 1536-dimensional vectors.
     *
     * @param string $text Text to embed (movie plot summary)
     * @return array Embedding vector (array of 1536 floats)
     * @throws \Exception If API call fails
     */
    public function generateEmbedding(string $text): array
    {
        if (empty($text)) {
            throw new \Exception('Cannot generate embedding for empty text');
        }

        try {
            $response = Http::withOptions(['verify' => config('app.env') !== 'local'])
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ])->post($this->apiUrl, [
                    'input' => $text,
                    'model' => $this->model,
                ]);

            if ($response->failed()) {
                $errorBody = $response->body();
                Log::error('OpenAI API embedding generation failed', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                ]);

                // Provide more specific error message
                $errorMessage = 'Failed to generate embedding from OpenAI API';
                if ($response->status() === 401) {
                    $errorMessage = 'Invalid OpenAI API key. Please check your OPENAI_API_KEY in .env file';
                } elseif ($response->status() === 429) {
                    $errorMessage = 'OpenAI API rate limit exceeded. Please wait and try again';
                } elseif (!empty($errorBody)) {
                    $decoded = json_decode($errorBody, true);
                    if (isset($decoded['error']['message'])) {
                        $errorMessage = 'OpenAI API Error: ' . $decoded['error']['message'];
                    }
                }

                throw new \Exception($errorMessage);
            }

            $data = $response->json();

            if (!isset($data['data'][0]['embedding'])) {
                Log::error('OpenAI API response missing embedding data', [
                    'response' => $data,
                ]);
                throw new \Exception('Invalid response from OpenAI API');
            }

            return $data['data'][0]['embedding'];

        } catch (\Exception $e) {
            Log::error('Embedding generation exception', [
                'message' => $e->getMessage(),
                'text_length' => strlen($text),
            ]);
            throw $e;
        }
    }

    /**
     * Generate embeddings for multiple texts in a batch
     *
     * More efficient than generating embeddings one at a time.
     * OpenAI API supports batching up to 2048 inputs per request.
     *
     * @param array $texts Array of texts to embed
     * @return array Array of embedding vectors
     * @throws \Exception If API call fails
     */
    public function generateBatchEmbeddings(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        // Filter out empty texts
        $texts = array_filter($texts, fn($text) => !empty($text));

        if (empty($texts)) {
            return [];
        }

        try {
            $response = Http::withOptions(['verify' => config('app.env') !== 'local'])
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ])->timeout(60)->post($this->apiUrl, [
                    'input' => array_values($texts),
                    'model' => $this->model,
                ]);

            if ($response->failed()) {
                $errorBody = $response->body();
                Log::error('OpenAI API batch embedding generation failed', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'batch_size' => count($texts),
                ]);

                // Provide more specific error message
                $errorMessage = 'Failed to generate batch embeddings from OpenAI API';
                if ($response->status() === 401) {
                    $errorMessage = 'Invalid OpenAI API key. Please check your OPENAI_API_KEY in .env file';
                } elseif ($response->status() === 429) {
                    $errorMessage = 'OpenAI API rate limit exceeded. Please wait and try again';
                } elseif (!empty($errorBody)) {
                    $decoded = json_decode($errorBody, true);
                    if (isset($decoded['error']['message'])) {
                        $errorMessage = 'OpenAI API Error: ' . $decoded['error']['message'];
                    }
                }

                throw new \Exception($errorMessage);
            }

            $data = $response->json();

            if (!isset($data['data']) || !is_array($data['data'])) {
                Log::error('OpenAI API batch response missing data', [
                    'response' => $data,
                ]);
                throw new \Exception('Invalid batch response from OpenAI API');
            }

            // Extract embeddings in the correct order
            $embeddings = [];
            foreach ($data['data'] as $item) {
                if (isset($item['embedding'])) {
                    $embeddings[] = $item['embedding'];
                }
            }

            return $embeddings;

        } catch (\Exception $e) {
            Log::error('Batch embedding generation exception', [
                'message' => $e->getMessage(),
                'batch_size' => count($texts),
            ]);
            throw $e;
        }
    }

    /**
     * Prepare text for embedding
     *
     * Cleans and formats text before generating embeddings.
     * Removes excessive whitespace and truncates if needed.
     *
     * @param string $text Raw text
     * @param int $maxLength Maximum character length (default: 8000)
     * @return string Cleaned text
     */
    public function prepareText(string $text, int $maxLength = 8000): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Truncate if too long (OpenAI has a token limit)
        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength);
        }

        return $text;
    }

    /**
     * Convert embedding array to database format
     *
     * PostgreSQL: Formats as pgvector string '[0.1,0.2,0.3]'
     * MySQL: Formats as JSON string '{"0":0.1,"1":0.2,"2":0.3}' (handled by json_encode)
     *
     * @param array $embedding Embedding vector
     * @return string Formatted embedding for database storage
     */
    public function formatForDatabase(array $embedding): string
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL pgvector format: [0.1,0.2,0.3]
            return '[' . implode(',', $embedding) . ']';
        } else {
            // MySQL JSON format
            return json_encode($embedding);
        }
    }
}
