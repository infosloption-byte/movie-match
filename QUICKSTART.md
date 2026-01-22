# Quick Start Guide

## Testing Both Scenarios in 3 Steps

### Step 1: Start Services (2 terminals needed)

**Terminal 1 - Laravel API:**
```bash
cd /home/user/movie-match/storyline-matcher-api
php artisan serve
```

**Terminal 2 - Embedding Service:**
```bash
cd /home/user/movie-match/embedding-service
python3 app.py
```

### Step 2: Setup Database & Import Movies (New terminal)

```bash
cd /home/user/movie-match

# Run database migrations
cd storyline-matcher-api
php artisan migrate

# Return to project root
cd ..

# Run automated setup
./test-scenarios.sh setup
```

This will:
- Import 20 popular movies from TMDB
- Generate embeddings for all movies
- Takes about 2-3 minutes

### Step 3: Test Both Scenarios

**Option A - Automated Testing (Recommended):**
```bash
cd /home/user/movie-match

# Interactive menu
./test-scenarios.sh

# Or run specific tests directly:
./test-scenarios.sh 1      # Test Scenario 1 only
./test-scenarios.sh 2      # Test Scenario 2 only
./test-scenarios.sh both   # Test both scenarios
```

**Option B - Manual Testing:**

See [TESTING.md](TESTING.md) for detailed curl commands.

---

## What Each Scenario Does

### Scenario 1: Title-based Search (Traditional)
```
User searches "Inception"
  → System finds movie in database
  → User views movie details
  → System shows similar movies based on plot
```

**Use Case:** "I liked this movie, show me similar ones"

### Scenario 2: Plot-based Search (NEW)
```
User describes a plot: "A team of heroes saves the world from aliens"
  → System generates embedding for description
  → System finds movies with similar storylines
```

**Use Case:** "I want to watch a movie about X"

---

## Example Outputs

### Scenario 1 Output:
```
Step 1: Searching for 'Inception'...
✓ Found: Inception (2010)

Step 2: Getting details...
✓ Title: Inception
✓ Plot: Cobb, a skilled thief...
✓ Rating: 8.4/10
✓ Runtime: 148 minutes

Step 3: Finding similar movies...
✓ Similar Movies Found:
  1. Shutter Island (88% similar)
  2. The Matrix (82% similar)
  3. Interstellar (79% similar)
```

### Scenario 2 Output:
```
Your Plot: "A team of heroes with superpowers saves the world"

Similar Movies Found:
  1. The Avengers (87% similar)
  2. Guardians of the Galaxy (82% similar)
  3. Justice League (78% similar)
  4. X-Men (75% similar)
  5. Fantastic Four (71% similar)
```

---

## Troubleshooting

### "Services not running"
Make sure both terminals from Step 1 are still running.

### "No movies found"
Run the setup script: `./test-scenarios.sh setup`

### "Connection refused"
Check that Laravel is on port 8000 and Embedding service on port 5001.

### "Module not found" (Python)
Install dependencies:
```bash
cd embedding-service
pip3 install -r requirements.txt --break-system-packages
```

### "Vendor autoload not found" (PHP)
Install dependencies:
```bash
cd storyline-matcher-api
composer install
```

---

## Next Steps

1. **Try custom plots** - Test Scenario 2 with your own plot descriptions
2. **Import more movies** - Use `{"source": "popular", "page": 2}` to get more movies
3. **Adjust similarity threshold** - Lower threshold (0.5) = more results, higher (0.8) = more precise
4. **Switch storage modes** - Edit `.env` and change `TMDB_STORAGE_MODE` to `full` or `minimal`

---

## API Endpoints Quick Reference

```bash
# Search by title
GET /api/movies/search?query=Inception

# Get movie details
GET /api/movies/1

# Get similar movies
GET /api/movies/1/similar?limit=5&threshold=0.7

# Search by custom plot
POST /api/movies/search-by-plot
Body: {"plot": "your plot here", "limit": 5, "threshold": 0.6}
```

Full API documentation in [TESTING.md](TESTING.md)
