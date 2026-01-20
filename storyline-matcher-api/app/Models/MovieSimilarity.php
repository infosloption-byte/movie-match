<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieSimilarity extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'similar_movie_id',
        'similarity_score',
    ];

    protected $casts = [
        'movie_id' => 'integer',
        'similar_movie_id' => 'integer',
        'similarity_score' => 'decimal:4',
    ];

    /**
     * Get the source movie
     */
    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id');
    }

    /**
     * Get the similar movie
     */
    public function similarMovie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'similar_movie_id');
    }
}
