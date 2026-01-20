<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'tmdb_id',
        'title',
        'plot_summary',
        'release_year',
        'poster_url',
        'popularity',
    ];

    protected $casts = [
        'tmdb_id' => 'integer',
        'release_year' => 'integer',
        'popularity' => 'decimal:2',
    ];

    /**
     * Get the embedding for the movie
     */
    public function embedding(): HasOne
    {
        return $this->hasOne(MovieEmbedding::class);
    }

    /**
     * Get movies similar to this one
     */
    public function similarities(): HasMany
    {
        return $this->hasMany(MovieSimilarity::class, 'movie_id');
    }

    /**
     * Check if movie has an embedding
     */
    public function hasEmbedding(): bool
    {
        return $this->embedding()->exists();
    }
}
