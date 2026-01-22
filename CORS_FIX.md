# CORS and 500 Error Fix Guide

## Issue Summary
You're getting:
1. **500 Internal Server Error** - Server-side error
2. **CORS Error** - Because the 500 error happens before CORS headers are sent

## Quick Fix Steps

### Step 1: Check if Laravel Server is Running

Open a terminal and check:

```bash
# Windows (PowerShell or CMD)
cd C:\wamp\www\html\movie-match\storyline-matcher-api
php artisan serve

# Should show:
# INFO  Server running on [http://127.0.0.1:8000]
```

If you see errors, they'll appear here. Common issues:
- Missing .env file
- Database connection errors
- Missing dependencies

### Step 2: Check Laravel Logs

If the server is running but you still get 500 errors:

```bash
# Check the error log
tail -50 storage/logs/laravel.log

# On Windows:
type storage\logs\laravel.log
```

Look for errors related to `search-by-plot` or `SimilarityService`.

### Step 3: Clear Laravel Cache

Sometimes cached routes/config cause issues:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan config:cache
```

### Step 4: Test the Endpoint Directly

With server running, test from another terminal:

```bash
curl -X POST http://localhost:8000/api/movies/search-by-plot ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"plot\": \"A team of heroes saves the world\", \"limit\": 5, \"threshold\": 0.6}"
```

**Expected Response:**
```json
{
  "success": true,
  "similar_movies": [...],
  "count": 5
}
```

**If you get error:**
It will show the actual error message (not just 500).

---

## Common Issues & Fixes

### Issue 1: "Call to undefined method"

**Error in logs:**
```
Call to undefined method App\Services\SimilarityService::findSimilarMoviesByEmbedding()
```

**Fix:**
```bash
# Clear compiled files
php artisan clear-compiled
composer dump-autoload
```

### Issue 2: "SQLSTATE[HY000] [2002] Connection refused"

**Error:** Database not connected

**Fix:**
1. Start MySQL/MariaDB:
   ```bash
   # WAMP - Start from WAMP control panel
   # Or check Windows services
   ```

2. Update `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=storyline_matcher
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

3. Test connection:
   ```bash
   php artisan migrate:status
   ```

### Issue 3: "Class 'App\Models\MovieEmbedding' not found"

**Fix:**
```bash
composer dump-autoload
```

### Issue 4: CORS Still Blocked After 500 Fixed

**Fix:** Update CORS to allow all origins during development:

Edit `config/cors.php`:
```php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'], // Allow all during development
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

Then clear config:
```bash
php artisan config:clear
```

### Issue 5: Missing Dependencies

**Error:** Various "Class not found" errors

**Fix:**
```bash
composer install
php artisan config:clear
```

---

## Debugging Steps

### 1. Enable Debug Mode

Edit `.env`:
```env
APP_DEBUG=true
APP_ENV=local
```

### 2. Check Route Exists

```bash
php artisan route:list | grep search-by-plot
```

**Expected output:**
```
POST | api/movies/search-by-plot | movies.searchByPlot
```

### 3. Test in Tinker

```bash
php artisan tinker
```

```php
// Test the service directly
$service = app(\App\Services\SimilarityService::class);
$embedding = array_fill(0, 384, 0.5); // Dummy embedding
$result = $service->findSimilarMoviesByEmbedding($embedding, 5, 0.6);
print_r($result);
```

### 4. Check Database Has Movies

```bash
php artisan tinker
```

```php
\App\Models\Movie::count(); // Should be > 0
\App\Models\MovieEmbedding::count(); // Should be > 0
```

If 0, import movies first:
```bash
php artisan movies:seed --pages=2 --fast
```

---

## Windows-Specific Issues

### Port Already in Use

If port 8000 is busy:
```bash
# Use different port
php artisan serve --port=8001

# Update frontend .env:
# VITE_API_URL=http://localhost:8001/api
```

### Firewall Blocking

Add exception for PHP:
1. Windows Defender Firewall
2. Allow an app through firewall
3. Add `php.exe` (usually in `C:\wamp\bin\php\php8.x\php.exe`)

### Path Issues

Make sure you're in the correct directory:
```powershell
cd C:\wamp\www\html\movie-match\storyline-matcher-api
pwd  # Should show the correct path
```

---

## Complete Restart Procedure

If nothing works, fresh start:

```bash
# 1. Stop all services
# Close PHP server (Ctrl+C)
# Close embedding service (Ctrl+C)

# 2. Clear all cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
composer dump-autoload

# 3. Verify environment
php artisan config:cache
php artisan route:cache

# 4. Start services
php artisan serve

# In another terminal:
cd ..\embedding-service
python app.py

# 5. Test
curl http://localhost:8000/api/movies/search?query=test
```

---

## Quick Diagnostic Command

Run this to check everything:

```bash
php artisan tinker
```

```php
// Check environment
echo "APP_ENV: " . config('app.env') . "\n";
echo "APP_DEBUG: " . (config('app.debug') ? 'true' : 'false') . "\n";
echo "DB_CONNECTION: " . config('database.default') . "\n";
echo "FRONTEND_URL: " . config('cors.allowed_origins')[0] . "\n";

// Check database
echo "Movies: " . \App\Models\Movie::count() . "\n";
echo "Embeddings: " . \App\Models\MovieEmbedding::count() . "\n";

// Check services
$tmdb = app(\App\Services\TMDBService::class);
$embedding = app(\App\Services\EmbeddingService::class);
$similarity = app(\App\Services\SimilarityService::class);
echo "Services loaded successfully\n";

exit
```

---

## Contact Points

After running diagnostics, provide:
1. Laravel server output (any errors shown)
2. Last 20 lines of `storage/logs/laravel.log`
3. Result of diagnostic command above
4. Browser console error (full message)

This will help identify the exact issue!
