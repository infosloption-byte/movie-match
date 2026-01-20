<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the movie_embeddings table to store OpenAI embeddings vectors.
     *
     * PostgreSQL: Uses pgvector's vector type with HNSW index for fast similarity search
     * MySQL: Uses JSON column to store embeddings (similarity calculated in PHP)
     *
     * Note: MySQL implementation is slower as it must load all embeddings and calculate
     * similarity in application code. For production with large datasets, PostgreSQL
     * with pgvector is strongly recommended.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        Schema::create('movie_embeddings', function (Blueprint $table) use ($driver) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->onDelete('cascade');

            // For MySQL, add JSON column directly in the schema
            if ($driver === 'mysql') {
                $table->json('embedding')->nullable();
            }

            $table->timestamps();
        });

        // PostgreSQL: Add vector column and HNSW index
        if ($driver === 'pgsql') {
            // Add vector column using raw SQL (Laravel Schema doesn't support vector type)
            // The vector(1536) means we're storing 1536-dimensional embedding vectors
            DB::statement('ALTER TABLE movie_embeddings ADD COLUMN embedding vector(1536)');

            // Create HNSW index for fast similarity search
            // HNSW (Hierarchical Navigable Small World) is an algorithm for approximate
            // nearest neighbor search that provides excellent performance for high-dimensional vectors
            //
            // vector_cosine_ops: Uses cosine distance for similarity (1 - cosine_similarity)
            // m = 16: Number of connections per layer (higher = better recall, more memory)
            // ef_construction = 64: Size of dynamic candidate list (higher = better index quality, slower build)
            DB::statement('CREATE INDEX movie_embeddings_embedding_idx ON movie_embeddings USING hnsw (embedding vector_cosine_ops) WITH (m = 16, ef_construction = 64)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movie_embeddings');
    }
};
