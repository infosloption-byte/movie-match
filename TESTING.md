# Testing Guide - Movie Match System

This guide will walk you through testing both search scenarios.

## Prerequisites

### 1. Start Required Services

**Terminal 1 - Laravel API Server:**
```bash
cd /home/user/movie-match/storyline-matcher-api
php artisan serve
# Should start on http://localhost:8000
```

**Terminal 2 - Python Embedding Service:**
```bash
cd /home/user/movie-match/embedding-service
python3 app.py
# Should start on http://localhost:5001
```

### 2. Set Up Database

```bash
cd /home/user/movie-match/storyline-matcher-api

# Run migrations
php artisan migrate

# Or if you need to reset:
php artisan migrate:fresh
```

### 3. Import Movies and Generate Embeddings

```bash
# Import popular movies from TMDB
curl -X POST http://localhost:8000/api/movies/import \
  -H "Content-Type: application/json" \
  -d '{"source": "popular", "page": 1}'

# Generate embeddings for all movies
curl -X POST http://localhost:8000/api/movies/batch-generate-embeddings \
  -H "Content-Type: application/json" \
  -d '{"limit": 50}'
```

---

## Scenario 1: Search by Title → View Details → Find Similar Movies

This is the traditional movie recommendation flow.

### Step 1: Search for a Movie by Title

```bash
curl -X GET "http://localhost:8000/api/movies/search?query=Inception" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tmdb_id": 27205,
      "title": "Inception",
      "plot_summary": "Cobb, a skilled thief...",
      ...
    }
  ],
  "pagination": {...}
}
```

### Step 2: Get Movie Details

Copy the `id` from the search results and get full details:

```bash
curl -X GET http://localhost:8000/api/movies/1 \
  -H "Accept: application/json"
```

**Expected Response (Minimal Mode):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "tmdb_id": 27205,
    "title": "Inception",
    "plot_summary": "Cobb, a skilled thief...",
    "has_embedding": true,
    "release_year": 2010,
    "poster_url": "https://image.tmdb.org/t/p/w500/...",
    "vote_average": 8.4,
    "runtime": 148,
    "genres": [...],
    "data_source": "tmdb_api"  // Fetched on-demand
  }
}
```

### Step 3: Find Similar Movies

```bash
curl -X GET "http://localhost:8000/api/movies/1/similar?limit=5&threshold=0.7" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "movie": {
      "id": 1,
      "title": "Inception",
      ...
    },
    "similar_movies": [
      {
        "id": 5,
        "title": "Shutter Island",
        "similarity_score": 0.8542,
        "plot_summary": "...",
        ...
      },
      {
        "id": 12,
        "title": "The Matrix",
        "similarity_score": 0.8231,
        ...
      }
    ],
    "source": "calculated"
  }
}
```

---

## Scenario 2: Search by Custom Storyline/Plot

This is the new feature - search for movies matching a custom plot description.

### Test 1: Action/Superhero Plot

```bash
curl -X POST http://localhost:8000/api/movies/search-by-plot \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "plot": "A team of heroes with superpowers must work together to save the world from an alien invasion threatening to destroy humanity",
    "limit": 5,
    "threshold": 0.6
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "query": {
    "plot": "A team of heroes with superpowers must work together to save the world from an alien invasion...",
    "limit": 5,
    "threshold": 0.6
  },
  "similar_movies": [
    {
      "id": 24,
      "title": "The Avengers",
      "similarity_score": 0.8724,
      "plot_summary": "...",
      ...
    },
    {
      "id": 67,
      "title": "Guardians of the Galaxy",
      "similarity_score": 0.8156,
      ...
    }
  ],
  "count": 5
}
```

### Test 2: Mystery/Thriller Plot

```bash
curl -X POST http://localhost:8000/api/movies/search-by-plot \
  -H "Content-Type: application/json" \
  -d '{
    "plot": "A detective investigates a series of mysterious murders while uncovering dark secrets from the past",
    "limit": 5,
    "threshold": 0.65
  }'
```

### Test 3: Romance Plot

```bash
curl -X POST http://localhost:8000/api/movies/search-by-plot \
  -H "Content-Type: application/json" \
  -d '{
    "plot": "Two people from different worlds fall in love against all odds and must fight to stay together",
    "limit": 5,
    "threshold": 0.6
  }'
```

### Test 4: Sci-Fi Adventure

```bash
curl -X POST http://localhost:8000/api/movies/search-by-plot \
  -H "Content-Type: application/json" \
  -d '{
    "plot": "A group of astronauts embark on a dangerous mission to explore a distant planet and discover something extraordinary that could change humanity forever",
    "limit": 5,
    "threshold": 0.65
  }'
```

### Test 5: Coming of Age Story

```bash
curl -X POST http://localhost:8000/api/movies/search-by-plot \
  -H "Content-Type: application/json" \
  -d '{
    "plot": "A young person discovers they have magical powers and must learn to control them while attending a special school for gifted students",
    "limit": 5,
    "threshold": 0.6
  }'
```

---

## Testing TMDB Storage Optimization

### Minimal Mode (Default - Saves ~70% Disk Space)

In `.env`:
```
TMDB_STORAGE_MODE=minimal
```

When you view a movie:
- Only title and plot are stored locally
- Other data (poster, rating, runtime, etc.) fetched from TMDB on-demand
- Response includes `"data_source": "tmdb_api"`
- First request may be slower, subsequent requests use 24-hour cache

### Full Mode (More Storage, Faster)

In `.env`:
```
TMDB_STORAGE_MODE=full
```

When you import movies:
- All movie data stored locally
- No API calls needed when viewing movies
- Response includes `"data_source": "local_storage"`
- Faster but uses more disk space

### Test the Difference

```bash
# Check database size
du -sh /home/user/movie-match/storyline-matcher-api/storage/

# Get movie details (watch for data_source field)
curl -X GET http://localhost:8000/api/movies/1 | jq '.data.data_source'
```

---

## Troubleshooting

### Embedding Service Not Running

```bash
# Check if service is running
curl http://localhost:5001/health

# If not, start it:
cd /home/user/movie-match/embedding-service
python3 app.py
```

### Laravel Server Not Running

```bash
# Check if service is running
curl http://localhost:8000/api/movies/search?query=test

# If not, start it:
cd /home/user/movie-match/storyline-matcher-api
php artisan serve
```

### No Movies in Database

```bash
# Import movies
curl -X POST http://localhost:8000/api/movies/import \
  -H "Content-Type: application/json" \
  -d '{"source": "popular", "page": 1}'

# Generate embeddings
curl -X POST http://localhost:8000/api/movies/batch-generate-embeddings \
  -H "Content-Type: application/json" \
  -d '{"limit": 50}'
```

### Database Not Created

```bash
cd /home/user/movie-match/storyline-matcher-api
php artisan migrate
```

---

## API Endpoints Summary

| Endpoint | Method | Purpose | Scenario |
|----------|--------|---------|----------|
| `/api/movies/search?query={title}` | GET | Search by title | 1 |
| `/api/movies/{id}` | GET | Get movie details | 1 |
| `/api/movies/{id}/similar` | GET | Get similar movies | 1 |
| `/api/movies/search-by-plot` | POST | Search by custom plot | 2 |
| `/api/movies/import` | POST | Import from TMDB | Setup |
| `/api/movies/batch-generate-embeddings` | POST | Generate embeddings | Setup |

---

## Expected Results

**Scenario 1 (Title-based):**
- Search returns exact/partial title matches
- Details show full movie information
- Similar movies based on plot similarity

**Scenario 2 (Plot-based):**
- No database lookup needed
- Directly compares your plot against all movies
- Returns movies with thematically similar storylines
- Useful for "I want a movie about X" queries

Both scenarios use the same underlying embedding technology for finding similar movies!
