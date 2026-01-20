# Quick Setup Guide

This guide will help you get the Storyline Matcher up and running in under 10 minutes.

## Prerequisites Checklist

Before starting, make sure you have:

- [ ] PostgreSQL 14+ installed
- [ ] PHP 8.2+ installed
- [ ] Composer installed
- [ ] Node.js 18+ installed
- [ ] TMDB API key (https://www.themoviedb.org/settings/api)
- [ ] OpenAI API key (https://platform.openai.com/api-keys)

## Step-by-Step Setup

### 1. Install pgvector Extension

```bash
# Clone pgvector repository
cd /tmp
git clone --branch v0.5.1 https://github.com/pgvector/pgvector.git
cd pgvector

# Build and install
make
sudo make install

# Verify installation
sudo -u postgres psql -c "SELECT * FROM pg_available_extensions WHERE name = 'vector';"
```

### 2. Create Database

```bash
# Start PostgreSQL if not running
sudo systemctl start postgresql

# Create database and user
sudo -u postgres psql << EOF
CREATE DATABASE storyline_matcher;
CREATE USER storyline_user WITH PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE storyline_matcher TO storyline_user;
\q
EOF
```

### 3. Setup Backend

```bash
cd storyline-matcher-api

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Edit .env file with your credentials
nano .env  # or use your preferred editor
```

Required `.env` values:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=storyline_matcher
DB_USERNAME=storyline_user
DB_PASSWORD=your_secure_password

TMDB_API_KEY=your_tmdb_api_key_here
OPENAI_API_KEY=your_openai_api_key_here
```

Run migrations:
```bash
php artisan migrate
```

### 4. Setup Frontend

```bash
cd ../storyline-matcher-web

# Install dependencies
npm install

# Copy environment file (default values should work)
cp .env.example .env
```

### 5. Start Both Servers

Terminal 1 (Backend):
```bash
cd storyline-matcher-api
php artisan serve
```

Terminal 2 (Frontend):
```bash
cd storyline-matcher-web
npm run dev
```

### 6. Import and Process Movies

In a new terminal:

```bash
# Import 20 popular movies
curl -X POST http://localhost:8000/api/movies/import \
  -H "Content-Type: application/json" \
  -d '{"source": "popular", "page": 1}'

# Generate embeddings for imported movies
curl -X POST http://localhost:8000/api/movies/batch-generate-embeddings \
  -H "Content-Type: application/json" \
  -d '{"limit": 50}'
```

### 7. Open the App

Visit http://localhost:5173 in your browser and start searching for movies!

## Testing the System

1. **Search for a movie**: Type "matrix" in the search bar
2. **Click on a movie**: View the movie details
3. **See similar movies**: Movies with similar storylines will appear below

## Common Issues

### Issue: "Extension vector not found"

**Solution**: Make sure pgvector is properly installed:
```bash
sudo -u postgres psql storyline_matcher -c "CREATE EXTENSION vector;"
```

### Issue: "TMDB API error"

**Solution**: Verify your TMDB API key is correct in `.env`

### Issue: "OpenAI API error"

**Solutions**:
- Check your API key is valid
- Ensure you have credits in your OpenAI account
- Verify billing is set up at https://platform.openai.com/account/billing

### Issue: "CORS error in browser"

**Solution**: Check that `FRONTEND_URL` in backend `.env` matches your frontend URL (default: http://localhost:5173)

### Issue: "Connection refused"

**Solution**: Make sure both servers are running:
- Backend: http://localhost:8000
- Frontend: http://localhost:5173

## Next Steps

1. Import more movies:
```bash
# Import from multiple pages
for i in {1..5}; do
  curl -X POST http://localhost:8000/api/movies/import \
    -H "Content-Type: application/json" \
    -d "{\"source\": \"popular\", \"page\": $i}"
  sleep 1
done
```

2. Generate embeddings for all movies:
```bash
curl -X POST http://localhost:8000/api/movies/batch-generate-embeddings \
  -H "Content-Type: application/json" \
  -d '{"limit": 100}'
```

3. Try different similarity thresholds and limits in the UI

## Cost Estimation

OpenAI API costs for embeddings (text-embedding-3-small):
- 100 movies: ~$0.0002 - $0.0004
- 1,000 movies: ~$0.002 - $0.004
- 10,000 movies: ~$0.02 - $0.04

TMDB API is free for non-commercial use!

## Support

If you encounter issues:
1. Check the troubleshooting sections in README.md
2. Review the logs: `storyline-matcher-api/storage/logs/laravel.log`
3. Check browser console for frontend errors

## Production Deployment

See README.md for production deployment instructions.
