<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the movie_similarities table for caching similarity calculations.
     * This improves performance by avoiding repeated similarity calculations
     * for frequently accessed movie pairs.
     */
    public function up(): void
    {
        Schema::create('movie_similarities', function (Blueprint $table) {
            $table->foreignId('movie_id')->constrained()->onDelete('cascade');
            $table->foreignId('similar_movie_id')->constrained('movies')->onDelete('cascade');
            $table->decimal('similarity_score', 5, 4)->comment('Cosine similarity score (0-1)');
            $table->timestamps();

            // Composite primary key to prevent duplicates
            $table->primary(['movie_id', 'similar_movie_id']);

            // Index for reverse lookups
            $table->index('similar_movie_id');
            $table->index('similarity_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movie_similarities');
    }
};
