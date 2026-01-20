<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieEmbedding extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'embedding',
    ];

    protected $casts = [
        'movie_id' => 'integer',
    ];

    /**
     * The embedding attribute is stored as a pgvector type in PostgreSQL.
     * We don't cast it to an array automatically because pgvector handles it as a string.
     * When you need to use it as an array, parse it manually.
     */

    /**
     * Get the movie that owns the embedding
     */
    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * Parse the vector string to an array
     *
     * pgvector stores vectors as strings like '[0.1,0.2,0.3]'
     * This method converts it to a PHP array
     */
    public function getEmbeddingArray(): ?array
    {
        if (!$this->embedding) {
            return null;
        }

        // Remove brackets and split by comma
        $embedding = trim($this->embedding, '[]');
        return array_map('floatval', explode(',', $embedding));
    }
}
