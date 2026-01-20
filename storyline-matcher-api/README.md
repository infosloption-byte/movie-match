# Storyline Matcher API (Laravel Backend)

Backend API for the Storyline Matcher movie recommendation system.

## Installation

### 1. Install Dependencies

```bash
composer install
```

### 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=storyline_matcher
DB_USERNAME=your_username
DB_PASSWORD=your_password

TMDB_API_KEY=your_tmdb_api_key
OPENAI_API_KEY=your_openai_api_key
```

### 3. Database Setup

Make sure PostgreSQL is running and pgvector extension is installed.

```bash
# Create database
createdb storyline_matcher

# Run migrations
php artisan migrate
```

### 4. Start Server

```bash
php artisan serve
```

API will be available at `http://localhost:8000`

## Service Classes

### TMDBService

Handles TMDB API interactions with rate limiting.

**Methods:**
- `searchMovies(string $query, int $page)` - Search movies by title
- `getMovieDetails(int $tmdbId)` - Get detailed movie info
- `getPopularMovies(int $page)` - Get popular movies
- `discoverMovies(array $filters, int $page)` - Discover movies with filters
- `buildPosterUrl(string $posterPath, string $size)` - Build poster URL
- `transformMovieData(array $tmdbMovie)` - Transform TMDB data to our format

### EmbeddingService

Generates OpenAI embeddings from text.

**Methods:**
- `generateEmbedding(string $text)` - Generate single embedding
- `generateBatchEmbeddings(array $texts)` - Generate multiple embeddings
- `prepareText(string $text, int $maxLength)` - Clean and prepare text
- `formatForDatabase(array $embedding)` - Format for pgvector storage

### SimilarityService

Calculates and manages movie similarities.

**Methods:**
- `findSimilarMovies(int $movieId, ?int $limit, ?float $threshold)` - Find similar movies using vector search
- `calculateCosineSimilarity(array $embedding1, array $embedding2)` - Calculate cosine similarity in PHP
- `cacheSimilarity(int $movieId, int $similarMovieId, float $score)` - Cache similarity score
- `getCachedSimilarMovies(int $movieId, ?int $limit, ?float $threshold)` - Get cached similarities
- `batchCacheSimilarities(int $movieId)` - Pre-compute and cache similarities

## Vector Similarity in PostgreSQL

The `movie_embeddings` table uses pgvector's `<=>` operator for cosine distance:

```sql
SELECT
    m.*,
    1 - (me.embedding <=> query_embedding) as similarity_score
FROM movies m
INNER JOIN movie_embeddings me ON me.movie_id = m.id
WHERE 1 - (me.embedding <=> query_embedding) >= 0.7
ORDER BY me.embedding <=> query_embedding
LIMIT 10
```

**Note:** pgvector uses cosine *distance*, so we convert to *similarity* by subtracting from 1.

## HNSW Index

The HNSW (Hierarchical Navigable Small World) index provides fast approximate nearest neighbor search:

```sql
CREATE INDEX movie_embeddings_embedding_idx
ON movie_embeddings
USING hnsw (embedding vector_cosine_ops)
WITH (m = 16, ef_construction = 64);
```

**Parameters:**
- `m = 16` - Number of connections per layer (higher = better recall, more memory)
- `ef_construction = 64` - Dynamic candidate list size (higher = better index quality)

## API Endpoints

See main README.md for complete API documentation.

## Testing

To test the API manually:

```bash
# Import 20 popular movies
curl -X POST http://localhost:8000/api/movies/import \
  -H "Content-Type: application/json" \
  -d '{"source": "popular", "page": 1}'

# Generate embeddings for all movies
curl -X POST http://localhost:8000/api/movies/batch-generate-embeddings \
  -H "Content-Type: application/json" \
  -d '{"limit": 50}'

# Search for a movie
curl "http://localhost:8000/api/movies/search?query=inception"

# Get similar movies (replace 1 with actual movie ID)
curl "http://localhost:8000/api/movies/1/similar?limit=10&threshold=0.7"
```

## Deployment

### Production Configuration

1. Set environment variables:
```bash
APP_ENV=production
APP_DEBUG=false
```

2. Cache configuration:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. Optimize autoloader:
```bash
composer install --optimize-autoloader --no-dev
```

### Queue Workers

For background processing (future enhancement):

```bash
php artisan queue:work
```

## Monitoring

Monitor OpenAI API usage to control costs:
- text-embedding-3-small: ~$0.02 per 1M tokens
- Average movie plot: ~100-200 tokens
- 1000 movies ≈ $0.002 - $0.004

## Troubleshooting

### "Class not found" errors
```bash
composer dump-autoload
```

### Database connection issues
```bash
# Test PostgreSQL connection
psql -h 127.0.0.1 -U your_username -d storyline_matcher
```

### pgvector errors
```bash
# Verify extension is enabled
sudo -u postgres psql storyline_matcher -c "CREATE EXTENSION IF NOT EXISTS vector;"
```
