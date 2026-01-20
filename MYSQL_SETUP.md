# MySQL Setup Guide

This guide explains how to set up the Storyline Matcher with MySQL instead of PostgreSQL.

## Important Performance Note

**MySQL Performance Warning**: MySQL does NOT have native vector similarity search support like PostgreSQL's pgvector extension. This means:

- **Similarity calculations are done in PHP** (not in the database)
- **Much slower** for large datasets (1000+ movies)
- All embeddings must be loaded into memory for each similarity search
- No indexing optimization for vector search

### Performance Comparison

| Database | 100 Movies | 1,000 Movies | 10,000 Movies |
|----------|------------|--------------|---------------|
| PostgreSQL (pgvector) | < 10ms | < 50ms | < 100ms |
| MySQL (PHP calculation) | ~100ms | ~2-5s | ~30-60s |

**Recommendation**: Use PostgreSQL with pgvector for production. MySQL is acceptable for:
- Development/testing
- Small datasets (< 500 movies)
- Proof of concept

## MySQL Setup Steps

### 1. Install MySQL

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install mysql-server

# macOS
brew install mysql

# Start MySQL service
sudo systemctl start mysql  # Linux
brew services start mysql   # macOS
```

### 2. Create Database

```bash
# Login to MySQL
mysql -u root -p

# Create database and user
CREATE DATABASE storyline_matcher;
CREATE USER 'storyline_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON storyline_matcher.* TO 'storyline_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Configure Laravel

Edit `storyline-matcher-api/.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=storyline_matcher
DB_USERNAME=storyline_user
DB_PASSWORD=your_password
```

### 4. Run Migrations

```bash
cd storyline-matcher-api
php artisan migrate
```

The migrations will automatically detect MySQL and:
- Store embeddings as JSON instead of pgvector
- Skip HNSW index creation
- Use PHP-based similarity calculations

### 5. Test the Setup

```bash
# Import some movies
curl -X POST http://localhost:8000/api/movies/import \
  -H "Content-Type: application/json" \
  -d '{"source": "popular", "page": 1}'

# Generate embeddings
curl -X POST http://localhost:8000/api/movies/batch-generate-embeddings \
  -H "Content-Type: application/json" \
  -d '{"limit": 20}'

# Test similarity search
curl "http://localhost:8000/api/movies/1/similar"
```

## How It Works with MySQL

### Embedding Storage

MySQL stores embeddings as JSON:

```sql
CREATE TABLE movie_embeddings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    movie_id BIGINT,
    embedding JSON,  -- Stores [0.1, 0.2, 0.3, ...]
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Similarity Calculation

When finding similar movies, the system:

1. Loads the query movie's embedding from database
2. Fetches ALL other movies with embeddings
3. Decodes each JSON embedding
4. Calculates cosine similarity in PHP for each movie
5. Sorts results by similarity score
6. Returns top N matches

**Code path**: `SimilarityService::findSimilarMoviesMySQL()`

### Optimization Tips for MySQL

#### 1. Use Similarity Caching

Pre-calculate similarities for popular movies:

```php
// Cache similarities for a movie
curl -X POST http://localhost:8000/api/movies/1/cache-similarities
```

Then fetch from cache:

```php
curl "http://localhost:8000/api/movies/1/similar?use_cache=true"
```

#### 2. Limit Dataset Size

Keep your dataset small (< 500 movies) for acceptable performance.

#### 3. Lower Similarity Threshold

Use a higher threshold to reduce candidates:

```php
curl "http://localhost:8000/api/movies/1/similar?threshold=0.8&limit=5"
```

#### 4. Add Indexes

Add indexes on frequently queried columns:

```sql
CREATE INDEX idx_movie_id ON movie_embeddings(movie_id);
CREATE INDEX idx_similarity_score ON movie_similarities(similarity_score);
```

#### 5. Consider Background Processing

For large datasets, calculate similarities in background jobs:

```bash
php artisan queue:work
```

## Switching from MySQL to PostgreSQL

If you outgrow MySQL and want to migrate to PostgreSQL:

### 1. Export Data

```bash
# Export movies and embeddings
php artisan db:seed --class=ExportMoviesSeeder
```

### 2. Setup PostgreSQL

Follow the main [SETUP.md](SETUP.md) guide for PostgreSQL setup.

### 3. Change Database Config

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=storyline_matcher
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 4. Run Migrations

```bash
php artisan migrate:fresh
```

### 5. Import Data

```bash
php artisan db:seed --class=ImportMoviesSeeder
```

The same code will automatically use pgvector for much faster similarity search!

## Troubleshooting

### "Column embedding cannot be null"

Make sure you're generating embeddings:

```bash
curl -X POST http://localhost:8000/api/movies/batch-generate-embeddings
```

### Slow similarity searches

This is expected with MySQL. Options:
1. Reduce dataset size
2. Use similarity caching
3. Increase threshold (0.8 instead of 0.7)
4. Switch to PostgreSQL

### JSON parsing errors

Check that embeddings are valid JSON:

```sql
SELECT id, JSON_VALID(embedding) FROM movie_embeddings WHERE JSON_VALID(embedding) = 0;
```

### Out of memory errors

MySQL loads all embeddings into memory. Solutions:
1. Increase PHP memory limit in `php.ini`:
   ```ini
   memory_limit = 512M
   ```
2. Reduce dataset size
3. Use PostgreSQL instead

## MySQL vs PostgreSQL Feature Comparison

| Feature | MySQL | PostgreSQL + pgvector |
|---------|-------|----------------------|
| Embedding storage | JSON | Native vector type |
| Similarity search | PHP calculation | Database operation |
| Indexing | None | HNSW index |
| Speed (1000 movies) | 2-5 seconds | < 50ms |
| Memory usage | High (loads all) | Low (indexed) |
| Max recommended dataset | 500 movies | 100,000+ movies |
| Setup complexity | Simple | Requires extension |

## Conclusion

MySQL support is provided for convenience and development, but **PostgreSQL with pgvector is strongly recommended for production use**.

For small projects or proof-of-concept, MySQL works fine. For anything serious, the performance difference is substantial enough to justify using PostgreSQL.
