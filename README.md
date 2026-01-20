# 🎬 Storyline Matcher

A movie recommendation system that finds movies with similar **storylines** using AI embeddings, not just genres. Built with Laravel 11, React, MySQL/PostgreSQL, and OpenAI's embeddings API.

## 🌟 Features

- **Semantic Search**: Find movies with similar plots using AI-powered embeddings
- **Vector Similarity**: Uses cosine similarity on 1536-dimensional embeddings
- **Dual Database Support**: Works with both MySQL and PostgreSQL
- **Fast Lookups**: PostgreSQL + pgvector provides sub-millisecond similarity search with HNSW index
- **TMDB Integration**: Import movie data directly from The Movie Database API
- **Modern UI**: Responsive React frontend with Tailwind CSS
- **RESTful API**: Clean Laravel API with comprehensive endpoints

## ⚡ Database Performance

| Database | Speed (1000 movies) | Recommended For |
|----------|---------------------|-----------------|
| **PostgreSQL + pgvector** | < 50ms | Production, large datasets (1000+ movies) |
| **MySQL** | 2-5 seconds | Development, small datasets (< 500 movies) |

**Note**: MySQL stores embeddings as JSON and calculates similarity in PHP. For production use with large datasets, **PostgreSQL with pgvector is strongly recommended**. See [MYSQL_SETUP.md](MYSQL_SETUP.md) for details.

## 🏗️ Architecture

### Tech Stack

- **Backend**: Laravel 11 (API only)
- **Frontend**: React 18 + Vite + TypeScript
- **Database**: MySQL 8.0+ or PostgreSQL 14+ with pgvector extension
- **AI**: OpenAI text-embedding-3-small (1536 dimensions)
- **Data Source**: TMDB API

### How It Works

1. **Movie Import**: Movies are fetched from TMDB API with plot summaries
2. **Embedding Generation**: Plot summaries are converted to 1536-dimensional vectors using OpenAI's API
3. **Vector Storage**: Embeddings are stored in database (pgvector for PostgreSQL, JSON for MySQL)
4. **Similarity Search**: Cosine similarity calculated between embeddings (in database for PostgreSQL, in PHP for MySQL)
5. **Results**: Movies are ranked by similarity score (0-1, where 1 is most similar)

### Cosine Similarity Explained

Cosine similarity measures the angle between two vectors:
- **1.0** = Identical storylines
- **0.7-0.9** = Very similar plots
- **0.5-0.7** = Somewhat similar
- **< 0.5** = Not very similar

Formula: `cosine_similarity = (A · B) / (||A|| × ||B||)`

## 📁 Project Structure

```
movie-match/
├── storyline-matcher-api/          # Laravel Backend
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   └── MovieController.php
│   │   ├── Models/
│   │   │   ├── Movie.php
│   │   │   ├── MovieEmbedding.php
│   │   │   └── MovieSimilarity.php
│   │   └── Services/
│   │       ├── TMDBService.php     # TMDB API integration
│   │       ├── EmbeddingService.php # OpenAI embeddings
│   │       └── SimilarityService.php # Cosine similarity
│   ├── database/
│   │   └── migrations/             # Database schema
│   ├── config/
│   └── routes/
│       └── api.php
│
└── storyline-matcher-web/          # React Frontend
    ├── src/
    │   ├── components/
    │   │   ├── SearchBar.tsx
    │   │   ├── MovieCard.tsx
    │   │   ├── MovieGrid.tsx
    │   │   └── SimilarMovies.tsx
    │   ├── services/
    │   │   └── api.ts              # API client
    │   └── types/
    │       └── index.ts            # TypeScript types
    └── package.json
```

## 🚀 Setup Instructions

**🪟 Windows Users with WAMP Server**: See [WAMP_SETUP.md](WAMP_SETUP.md) for a complete step-by-step Windows setup guide.

### Prerequisites

- PHP 8.2+
- Composer
- **Database**: MySQL 8.0+ OR PostgreSQL 14+ (PostgreSQL recommended for production)
- Node.js 18+
- npm or yarn

### 1. Database Setup

**Choose ONE of the following options:**

#### Option A: MySQL (Simpler Setup, Slower Performance)

```bash
# Install MySQL
sudo apt update && sudo apt install mysql-server  # Ubuntu/Debian
# OR
brew install mysql  # macOS

# Create database
mysql -u root -p
```

```sql
CREATE DATABASE storyline_matcher;
CREATE USER 'storyline_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON storyline_matcher.* TO 'storyline_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Note**: See [MYSQL_SETUP.md](MYSQL_SETUP.md) for detailed MySQL configuration and performance considerations.

#### Option B: PostgreSQL + pgvector (Recommended for Production)

```bash
# Install PostgreSQL (Ubuntu/Debian)
sudo apt update
sudo apt install postgresql postgresql-contrib

# Install pgvector
cd /tmp
git clone --branch v0.5.1 https://github.com/pgvector/pgvector.git
cd pgvector
make
sudo make install

# Create database
sudo -u postgres psql
```

```sql
CREATE DATABASE storyline_matcher;
CREATE USER storyline_user WITH PASSWORD 'your_password';
GRANT ALL PRIVILEGES ON DATABASE storyline_matcher TO storyline_user;
\q
```

### 2. Backend Setup (Laravel)

```bash
cd storyline-matcher-api

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure .env file
# Edit .env and set:
# - Database connection (DB_CONNECTION=mysql or pgsql)
# - Database credentials (host, port, database name, username, password)
# - TMDB_API_KEY (get from https://www.themoviedb.org/settings/api)
# - OPENAI_API_KEY (get from https://platform.openai.com/api-keys)

# Run migrations
php artisan migrate

# Start development server
php artisan serve
# API will be available at http://localhost:8000
```

### 3. Frontend Setup (React)

```bash
cd storyline-matcher-web

# Install dependencies
npm install

# Copy environment file
cp .env.example .env

# Edit .env if needed (defaults to http://localhost:8000/api)

# Start development server
npm run dev
# Frontend will be available at http://localhost:5173
```

## 📝 API Keys Setup

### TMDB API Key

1. Go to https://www.themoviedb.org/signup
2. Create an account
3. Go to Settings → API
4. Request an API key (choose "Developer" option)
5. Copy the API key to your `.env` file

### OpenAI API Key

1. Go to https://platform.openai.com/signup
2. Create an account
3. Go to API Keys section
4. Create a new secret key
5. Copy the key to your `.env` file

**Note**: OpenAI API is paid. The `text-embedding-3-small` model costs approximately $0.02 per 1M tokens.

## 🔧 Usage

### 1. Import Movies

Import popular movies from TMDB:

```bash
curl -X POST http://localhost:8000/api/movies/import \
  -H "Content-Type: application/json" \
  -d '{"source": "popular", "page": 1}'
```

Or search for specific movies:

```bash
curl -X POST http://localhost:8000/api/movies/import \
  -H "Content-Type: application/json" \
  -d '{"source": "search", "query": "inception"}'
```

### 2. Generate Embeddings

Generate embeddings for imported movies:

```bash
# For a single movie
curl -X POST http://localhost:8000/api/movies/1/generate-embedding

# For all movies without embeddings (batch)
curl -X POST http://localhost:8000/api/movies/batch-generate-embeddings \
  -H "Content-Type: application/json" \
  -d '{"limit": 50}'
```

### 3. Search Movies

```bash
curl "http://localhost:8000/api/movies/search?query=inception"
```

### 4. Find Similar Movies

```bash
curl "http://localhost:8000/api/movies/1/similar?limit=10&threshold=0.7"
```

## 🎯 API Endpoints

### Movies

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/movies/import` | Import movies from TMDB |
| GET | `/api/movies/search?query={title}` | Search movies by title |
| GET | `/api/movies/{id}` | Get single movie details |
| POST | `/api/movies/{id}/generate-embedding` | Generate embedding for a movie |
| GET | `/api/movies/{id}/similar` | Get similar movies |
| POST | `/api/movies/batch-generate-embeddings` | Batch generate embeddings |

### Request/Response Examples

#### Import Movies

**Request:**
```json
POST /api/movies/import
{
  "source": "popular",
  "page": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Imported 20 movies, skipped 0",
  "data": {
    "imported": 20,
    "skipped": 0,
    "total_results": 10000,
    "page": 1,
    "total_pages": 500
  }
}
```

#### Get Similar Movies

**Request:**
```
GET /api/movies/1/similar?limit=5&threshold=0.7
```

**Response:**
```json
{
  "success": true,
  "data": {
    "movie": {
      "id": 1,
      "title": "Inception",
      "plot_summary": "A thief who steals corporate secrets...",
      "release_year": 2010
    },
    "similar_movies": [
      {
        "id": 15,
        "title": "Shutter Island",
        "similarity_score": 0.8532,
        "release_year": 2010
      }
    ],
    "source": "calculated"
  }
}
```

## 🔍 Database Schema

### Movies Table
- `id` - Primary key
- `tmdb_id` - TMDB movie ID (unique)
- `title` - Movie title
- `plot_summary` - Plot overview from TMDB
- `release_year` - Release year
- `poster_url` - Full URL to poster image
- `popularity` - TMDB popularity score

### Movie Embeddings Table
- `id` - Primary key
- `movie_id` - Foreign key to movies
- `embedding` - Vector(1536) - pgvector type
- HNSW index on embedding for fast similarity search

### Movie Similarities Table (Cache)
- `movie_id` - Foreign key to movies
- `similar_movie_id` - Foreign key to movies
- `similarity_score` - Decimal (0-1)

## 🎨 Frontend Features

- **Search Bar**: Search movies by title
- **Movie Cards**: Display movie posters, titles, years, and plots
- **Similarity Scores**: Show match percentage for similar movies
- **Responsive Design**: Works on mobile, tablet, and desktop
- **Loading States**: Visual feedback during API calls
- **Error Handling**: User-friendly error messages

## 🚀 Deployment

### Backend (Laravel)

1. Set `APP_ENV=production` in `.env`
2. Run `php artisan config:cache`
3. Run `php artisan route:cache`
4. Set up a production web server (Nginx/Apache)
5. Use a process manager like Supervisor for queue workers

### Frontend (React)

```bash
cd storyline-matcher-web
npm run build
# Deploy the 'dist' folder to your hosting provider
```

## 🧪 Testing the System

1. **Import movies**: Start with popular movies
```bash
curl -X POST http://localhost:8000/api/movies/import \
  -H "Content-Type: application/json" \
  -d '{"source": "popular", "page": 1}'
```

2. **Generate embeddings**: Process all imported movies
```bash
curl -X POST http://localhost:8000/api/movies/batch-generate-embeddings \
  -H "Content-Type: application/json" \
  -d '{"limit": 50}'
```

3. **Test search**: Search for a movie
```bash
curl "http://localhost:8000/api/movies/search?query=matrix"
```

4. **Test similarity**: Find similar movies
```bash
curl "http://localhost:8000/api/movies/1/similar"
```

## 📊 Performance Considerations

- **HNSW Index**: Provides fast approximate nearest neighbor search
- **Rate Limiting**: TMDB API limited to ~4 requests/second
- **Batch Processing**: Generate embeddings in batches to save API calls
- **Caching**: Movie similarities can be cached in the database
- **OpenAI Costs**: Monitor usage to control embedding generation costs

## 🐛 Troubleshooting

### pgvector not installed
```bash
# Check if extension is available
sudo -u postgres psql -c "SELECT * FROM pg_available_extensions WHERE name = 'vector';"

# If not found, reinstall pgvector
```

### OpenAI API errors
- Check API key is valid
- Ensure you have credits in your OpenAI account
- Verify the model name is correct (text-embedding-3-small)

### CORS errors
- Check FRONTEND_URL in backend `.env`
- Verify CORS middleware is enabled

## 📚 Resources

- [pgvector Documentation](https://github.com/pgvector/pgvector)
- [OpenAI Embeddings Guide](https://platform.openai.com/docs/guides/embeddings)
- [TMDB API Documentation](https://developers.themoviedb.org/3)
- [Laravel 11 Documentation](https://laravel.com/docs/11.x)
- [React Documentation](https://react.dev)

## 📄 License

MIT License - Feel free to use this project for learning and development.

## 🤝 Contributing

This is a demonstration project. Feel free to fork and modify for your own use!

## 💡 Future Enhancements

- [ ] User accounts and favorites
- [ ] Movie ratings and reviews integration
- [ ] Multi-language support
- [ ] Recommendation history
- [ ] Advanced filters (genre, year, rating)
- [ ] Batch similarity pre-computation for popular movies
- [ ] Real-time embedding generation queue
- [ ] Movie recommendation explanations (why are they similar?)
