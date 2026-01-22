# 🎬 Movie Match - AI-Powered Movie Recommendations

Find movies with similar storylines using AI embeddings and semantic search.

## 🌟 Features

- **Scenario 1:** Search by movie title → View details → Find similar movies
- **Scenario 2:** Describe a custom plot → Find movies matching your storyline
- **AI-Powered:** Uses sentence transformers for semantic similarity (free!)
- **Fast:** Local embeddings, no API costs or rate limits
- **Smart Storage:** Minimal mode saves 70% disk space with on-demand TMDB data
- **Dual Database:** Works with MySQL or PostgreSQL

---

## 🚀 Quick Start (5 Minutes)

### 1. Start Backend Services

**Terminal 1 - Laravel API:**
```bash
cd storyline-matcher-api
php artisan serve
```

**Terminal 2 - Embedding Service:**
```bash
cd embedding-service
pip install flask flask-cors sentence-transformers
python app.py
```

### 2. Setup Database (First Time Only)

```bash
cd storyline-matcher-api

# Run migrations
php artisan migrate

# Import movies (fast mode - 2 minutes)
php artisan movies:seed --pages=3 --fast
```

### 3. Start Frontend

```bash
cd storyline-matcher-web
npm install
npm run dev
```

Open: **http://localhost:5173**

---

## 🎯 Usage

### Scenario 1: Search by Title
1. Click "Scenario 1: Search by Title" tab
2. Search for a movie (e.g., "Inception")
3. Click the movie to see similar ones based on plot

### Scenario 2: Search by Plot
1. Click "Scenario 2: Search by Plot" tab
2. Describe a plot or use an example
3. See movies matching your storyline instantly

---

## ⚡ Speed Tips

**Fast Mode** (2-3x faster):
```bash
php artisan movies:seed --pages=5 --fast
```

**Split Import** (fastest):
```bash
# Import 200 movies in 30 seconds
php artisan movies:seed --pages=10 --skip-embeddings --fast

# Generate embeddings later (3 minutes)
php artisan embeddings:generate --fast
```

---

## 🔧 Common Issues & Fixes

### CORS Errors

```bash
# Clear cache and restart server
cd storyline-matcher-api
php artisan config:clear
php artisan cache:clear

# Stop server (Ctrl+C) and restart
php artisan serve
```

### 500 Server Error

Check the Laravel terminal for error details, then:

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
composer dump-autoload

# Restart server
php artisan serve
```

### No Movies Found

```bash
php artisan movies:seed --pages=3 --fast
```

### Empty Plot Errors

```bash
php clean-empty-movies.php
```

### Database Not Connected

Check `.env` file in `storyline-matcher-api/`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=storyline_matcher
DB_USERNAME=root
DB_PASSWORD=
```

---

## 📖 Complete Setup Guide

See **SETUP.md** for detailed setup instructions including:
- Database configuration (MySQL/PostgreSQL)
- TMDB API key setup
- Windows/WAMP setup
- Troubleshooting

---

## 📊 Key Commands

```bash
# Import movies
php artisan movies:seed [--pages=N] [--fast] [--skip-embeddings]

# Generate embeddings separately
php artisan embeddings:generate [--fast] [--limit=N]

# Clean movies without plots
php clean-empty-movies.php

# Clear cache
php artisan config:clear
php artisan cache:clear
```

---

## 🛠️ Tech Stack

- **Backend:** Laravel 11, PHP 8.1+
- **Frontend:** React 18, TypeScript, Vite, Tailwind CSS
- **AI:** Sentence Transformers (sentence-transformers/all-MiniLM-L6-v2)
- **Database:** MySQL/MariaDB or PostgreSQL
- **APIs:** TMDB for movie data

---

## 📁 Project Structure

```
movie-match/
├── storyline-matcher-api/      # Laravel backend API
├── storyline-matcher-web/      # React frontend
├── embedding-service/          # Python AI service
├── README.md                   # This file
└── SETUP.md                    # Complete setup guide
```

---

## 🎬 API Endpoints

```bash
# Search by title
GET /api/movies/search?query=Inception

# Get movie details
GET /api/movies/{id}

# Get similar movies
GET /api/movies/{id}/similar

# Search by custom plot (Scenario 2)
POST /api/movies/search-by-plot
Body: {"plot": "your plot here", "limit": 10, "threshold": 0.6}
```

---

## 💡 Why This is Cool

- **100% Free:** No OpenAI costs, unlimited embeddings
- **Privacy:** Runs locally, no data sent to external services
- **Fast:** Sentence transformers are quick
- **Accurate:** 384-dimensional vectors for good similarity matching
- **Smart:** Caches TMDB data for 24 hours to reduce API calls

---

## 🤝 Contributing

Feel free to fork and improve! Ideas:
- Add more movie sources
- Improve UI/UX
- Add user accounts
- Implement movie ratings

---

## 📄 License

MIT License - Use freely for learning and projects!
