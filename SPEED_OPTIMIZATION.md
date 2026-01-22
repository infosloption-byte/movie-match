# Speed Optimization Guide

This guide shows you how to dramatically speed up the movie import and embedding generation process.

## 🚀 Quick Comparison

| Method | Time for 100 Movies | Speed |
|--------|-------------------|-------|
| **Normal Mode** | ~5-6 minutes | 1x |
| **Fast Mode** | ~2-3 minutes | 2-3x faster |
| **Split Import** | ~1-2 minutes | 3-5x faster |

---

## ⚡ Option 1: Fast Mode (Easiest)

Use the `--fast` flag to reduce delays between requests:

```bash
cd /home/user/movie-match/storyline-matcher-api

# Fast import with embeddings
php artisan movies:seed --pages=5 --fast

# What it does:
# - TMDB delay: 1s → 0.25s (4x faster)
# - Embedding delay: 2s → 0.5s (4x faster)
```

**When to use:**
- ✅ You have a stable internet connection
- ✅ Using local embedding service (not OpenAI/HuggingFace)
- ✅ Want quick results

**When NOT to use:**
- ❌ Slow/unreliable internet (may hit rate limits)
- ❌ Using external API services

---

## 🔀 Option 2: Split Import (Fastest)

Import movies first, then generate embeddings separately:

```bash
# Step 1: Import movies ONLY (very fast - 10-20 seconds for 100 movies)
php artisan movies:seed --pages=5 --skip-embeddings

# Step 2: Generate embeddings later (2-3 minutes for 100 movies)
php artisan embeddings:generate --fast

# Or without fast mode:
php artisan embeddings:generate
```

**Why this is faster:**
- Import is super fast (no embedding delays)
- Can import many pages quickly
- Generate embeddings when you have time
- Can run embedding generation in background

**Example:**
```bash
# Import 10 pages (200 movies) in ~30 seconds
php artisan movies:seed --pages=10 --skip-embeddings

# Then generate embeddings for all in ~5 minutes
php artisan embeddings:generate --fast --limit=200
```

---

## 🎯 Option 3: Combine Both (Recommended)

Best of both worlds:

```bash
# Import many movies quickly
php artisan movies:seed --pages=10 --skip-embeddings --fast

# Generate embeddings in fast mode
php artisan embeddings:generate --fast
```

---

## 📊 Detailed Speed Breakdown

### Normal Mode (Default)
```bash
php artisan movies:seed --pages=5
```
- Import: 1s delay × 100 movies = ~100s
- Embeddings: 2s delay × 100 movies = ~200s
- **Total: ~5 minutes**

### Fast Mode
```bash
php artisan movies:seed --pages=5 --fast
```
- Import: 0.25s delay × 100 movies = ~25s
- Embeddings: 0.5s delay × 100 movies = ~50s
- **Total: ~1.5 minutes**

### Split Mode
```bash
php artisan movies:seed --pages=5 --skip-embeddings --fast
php artisan embeddings:generate --fast
```
- Import: 0.25s delay × 100 movies = ~25s
- Embeddings: 0.5s delay × 100 movies = ~50s
- **Total: ~1.5 minutes (but can do import immediately)**

---

## 🛠️ Advanced Options

### Generate Embeddings in Batches

Process embeddings in smaller batches:

```bash
# Generate 50 at a time
php artisan embeddings:generate --limit=50 --fast

# Check progress
php artisan tinker
>>> \App\Models\MovieEmbedding::count()
>>> \App\Models\Movie::whereDoesntHave('embedding')->count()
```

### Import Multiple Times

Import different pages simultaneously in different terminals:

**Terminal 1:**
```bash
php artisan movies:seed --pages=5 --skip-embeddings --fast
```

**Terminal 2 (after Terminal 1 finishes):**
```bash
php artisan movies:seed --pages=10 --skip-embeddings --fast
```

**Terminal 3 (generate embeddings for all):**
```bash
php artisan embeddings:generate --fast
```

---

## 💡 Pro Tips

### 1. Import in Stages

```bash
# Stage 1: Quick import for testing (30 seconds)
php artisan movies:seed --pages=2 --skip-embeddings --fast

# Stage 2: Generate embeddings for initial movies (1 minute)
php artisan embeddings:generate --limit=40 --fast

# Stage 3: Import more movies later (2 minutes)
php artisan movies:seed --pages=10 --skip-embeddings --fast

# Stage 4: Generate remaining embeddings (3 minutes)
php artisan embeddings:generate --fast
```

### 2. Check What Needs Processing

```bash
# Count movies without embeddings
php artisan tinker
>>> \App\Models\Movie::whereDoesntHave('embedding')
    ->whereNotNull('plot_summary')
    ->where('plot_summary', '!=', '')
    ->count()
```

### 3. Clean Database First

```bash
# Remove movies without plot summaries before importing more
php clean-empty-movies.php
```

---

## 🔥 Maximum Speed Setup

For absolute maximum speed:

```bash
# 1. Clean existing data
php clean-empty-movies.php

# 2. Import 20 pages in fast mode (400 movies in ~1 minute)
php artisan movies:seed --pages=20 --skip-embeddings --fast

# 3. Generate all embeddings in background (run this in separate terminal)
nohup php artisan embeddings:generate --fast > embeddings.log 2>&1 &

# 4. Check progress
tail -f embeddings.log

# 5. When done, check count
php artisan tinker
>>> \App\Models\MovieEmbedding::count()
```

---

## ⚠️ Rate Limit Warnings

### TMDB API
- Normal: 40 requests per 10 seconds (safe)
- Fast: May hit limits if importing 50+ pages
- **Solution:** Use `--skip-embeddings` and import in batches

### Local Embedding Service
- No rate limits
- Only limited by CPU/RAM
- **Safe to use `--fast` always**

### OpenAI/HuggingFace
- Have rate limits
- **DO NOT use `--fast` mode**
- Use normal mode or split import

---

## 📈 Recommended Workflows

### For Testing (2 minutes)
```bash
php artisan movies:seed --pages=2 --fast
```

### For Development (5 minutes)
```bash
php artisan movies:seed --pages=5 --skip-embeddings --fast
php artisan embeddings:generate --limit=100 --fast
```

### For Production (10 minutes)
```bash
# Import many movies
php artisan movies:seed --pages=20 --skip-embeddings --fast

# Generate embeddings in background
nohup php artisan embeddings:generate --fast > embeddings.log 2>&1 &
```

---

## 🎬 All Available Commands

```bash
# Import with options
php artisan movies:seed [--pages=N] [--skip-embeddings] [--fast]

# Generate embeddings separately
php artisan embeddings:generate [--limit=N] [--fast]

# Clean empty movies
php clean-empty-movies.php

# Check status
php artisan tinker
>>> \App\Models\Movie::count()
>>> \App\Models\MovieEmbedding::count()
```

---

## 📊 Example: Import 500 Movies Fast

```bash
# Step 1: Import (2-3 minutes)
php artisan movies:seed --pages=25 --skip-embeddings --fast

# Step 2: Generate embeddings (8-10 minutes)
php artisan embeddings:generate --fast

# Total: ~12 minutes for 500 movies
# vs Normal mode: ~40 minutes
```

---

## ✅ Verification

After importing, verify everything worked:

```bash
php artisan tinker
```

```php
// Check counts
$totalMovies = \App\Models\Movie::count();
$withEmbeddings = \App\Models\MovieEmbedding::count();
$withoutEmbeddings = \App\Models\Movie::whereDoesntHave('embedding')->count();
$emptyPlots = \App\Models\Movie::whereNull('plot_summary')
    ->orWhere('plot_summary', '')
    ->count();

echo "Total movies: {$totalMovies}\n";
echo "With embeddings: {$withEmbeddings}\n";
echo "Without embeddings: {$withoutEmbeddings}\n";
echo "Empty plots: {$emptyPlots}\n";

// Should show:
// - Total movies: 500
// - With embeddings: 500
// - Without embeddings: 0
// - Empty plots: 0
```

Happy importing! 🚀
