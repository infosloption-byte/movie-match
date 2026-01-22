# Frontend Testing Guide

This guide shows you how to test both scenarios using the web interface.

## Quick Start

### 1. Start Backend Services

**Terminal 1 - Laravel API:**
```bash
cd /home/user/movie-match/storyline-matcher-api
php artisan serve
# Should start on http://localhost:8000
```

**Terminal 2 - Embedding Service:**
```bash
cd /home/user/movie-match/embedding-service
pip3 install flask flask-cors sentence-transformers --break-system-packages  # First time only
python3 app.py
# Should start on http://localhost:5001
```

### 2. Setup Database (First Time Only)

**Terminal 3:**
```bash
cd /home/user/movie-match/storyline-matcher-api

# Run migrations
php artisan migrate

# Import movies
curl -X POST http://localhost:8000/api/movies/import \
  -H "Content-Type: application/json" \
  -d '{"source": "popular", "page": 1}'

# Generate embeddings
curl -X POST http://localhost:8000/api/movies/batch-generate-embeddings \
  -H "Content-Type: application/json" \
  -d '{"limit": 50}'
```

### 3. Start Frontend

**Terminal 4:**
```bash
cd /home/user/movie-match/storyline-matcher-web

# Install dependencies (first time only)
npm install

# Start dev server
npm run dev
```

The frontend will open at: **http://localhost:5173**

---

## Testing the Interface

### Scenario 1: Search by Title

1. **Click "Scenario 1: Search by Title" tab**

2. **Search for a movie:**
   - Type: "Inception" in the search bar
   - Press Enter or click Search

3. **Browse results:**
   - You'll see movie cards with posters
   - Click on any movie card

4. **View similar movies:**
   - See the selected movie's details
   - Scroll down to see similar movies
   - Each similar movie shows a similarity score percentage

5. **Click on a similar movie:**
   - This will load that movie's similar movies
   - You can navigate through the movie graph

6. **Go back:**
   - Click "← Back to search results" to return

**Try these titles:**
- Inception
- The Matrix
- Interstellar
- The Avengers
- Titanic

---

### Scenario 2: Search by Plot

1. **Click "Scenario 2: Search by Plot" tab**

2. **Use an example plot (easiest):**
   - Click one of the 5 example plot buttons
   - Example: "A team of heroes with superpowers must work together to save the world from an alien invasion"
   - Click "Find Similar Movies"

3. **Or write your own:**
   - Type your own plot description (minimum 10 characters)
   - Be descriptive! More detail = better matches

4. **View results:**
   - See your plot description displayed
   - Browse movies matching your storyline
   - Each shows similarity score

5. **Click a movie:**
   - View full details
   - See that movie's similar movies

6. **Try another plot:**
   - Click "← Back to plot search"
   - Enter a different plot

**Example custom plots to try:**

**Action/Thriller:**
```
A secret agent must stop a terrorist organization from launching a global cyber attack that could destroy the world's financial systems
```

**Romance:**
```
Two strangers meet on a train journey across Europe and spend one magical night together in Vienna, knowing they may never see each other again
```

**Sci-Fi:**
```
Humanity discovers an ancient alien artifact on Mars that holds the key to faster-than-light travel and threatens to change civilization forever
```

**Horror:**
```
A family moves into an old mansion and discovers it's haunted by spirits of people who mysteriously disappeared decades ago
```

**Comedy:**
```
A strict businessman and a free-spirited artist are forced to work together on a project and slowly fall in love despite their differences
```

**Fantasy:**
```
A young orphan discovers they are the chosen one destined to defeat an ancient evil using magical powers they never knew they had
```

---

## UI Features

### Mode Switcher
- **Blue highlighted tab** = Active mode
- Descriptions explain what each scenario does
- Switching modes clears current search

### Search Input (Scenario 1)
- Simple text input
- Searches movie titles in database
- Shows pagination if many results

### Plot Input (Scenario 2)
- Large textarea for descriptions
- Character counter (minimum 10 chars)
- 5 pre-written examples
- Click example to auto-fill

### Movie Cards
- Show poster image
- Display title and year
- Similarity score percentage (when applicable)
- Click to view details

### Loading States
- "Searching for movies..." (title search)
- "Finding similar movies..." (plot search or similarity)
- Spinner animation

### Error Handling
- Clear error messages
- Retry button when applicable
- Helpful suggestions

---

## What to Look For

### ✅ Working Features

**Scenario 1:**
- Search finds movies by title
- Clicking movie shows details
- Similar movies display with scores
- Can navigate between movies

**Scenario 2:**
- Plot search returns relevant movies
- Similarity scores make sense
- Example plots work instantly
- Custom plots return good matches

**Both:**
- Smooth tab switching
- No data mixing between modes
- Error messages are clear
- Images load correctly
- Responsive on mobile

### ⚠️ Potential Issues

**If no results appear:**
- Check backend services are running
- Verify database has movies (run import)
- Ensure embeddings were generated
- Check browser console for errors

**If images don't load:**
- TMDB API may be slow
- Some movies don't have posters
- Placeholder will show instead

**If similarity scores are low:**
- Normal! Not all movies are similar
- Try lowering threshold in backend
- Or try different plots

---

## Advanced Testing

### Test Different Plot Styles

**Very specific:**
```
A hacker discovers their entire life is a computer simulation and must find a way to escape the virtual prison while evading AI agents hunting them
```

**Very general:**
```
Someone goes on an adventure and learns important life lessons
```

**Genre mixing:**
```
A romantic comedy set in space where two astronauts fall in love while trying to repair a broken spaceship before running out of oxygen
```

### Test Edge Cases

1. **Empty search** - Should show validation
2. **Very short plot (< 10 chars)** - Button disabled
3. **Movie without embedding** - Shows error message
4. **Special characters in search** - Should handle gracefully
5. **Very long plot description** - Should work fine

### Performance Testing

1. **Import more movies:**
   ```bash
   # Import page 2, 3, 4, etc.
   curl -X POST http://localhost:8000/api/movies/import \
     -H "Content-Type: application/json" \
     -d '{"source": "popular", "page": 2}'
   ```

2. **Generate all embeddings:**
   ```bash
   curl -X POST http://localhost:8000/api/movies/batch-generate-embeddings \
     -H "Content-Type: application/json" \
     -d '{"limit": 100}'
   ```

3. **Test with 100+ movies** - Should still be fast

---

## Screenshots / Expected Behavior

### Scenario 1 Flow:
```
[Home Page with Scenario 1 Active]
         ↓ (search "Inception")
[Search Results Grid: 1 movie found]
         ↓ (click Inception)
[Movie Details + Similar Movies]
   Inception (selected)
   - Shutter Island (88%)
   - The Matrix (82%)
   - Interstellar (79%)
         ↓ (click Shutter Island)
[Movie Details + Similar Movies for Shutter Island]
```

### Scenario 2 Flow:
```
[Home Page with Scenario 2 Active]
         ↓ (enter plot or click example)
[Your Plot + Matching Movies]
   Your plot: "A team of heroes..."
   - The Avengers (87%)
   - Guardians of the Galaxy (82%)
   - Justice League (78%)
         ↓ (click The Avengers)
[Movie Details + Similar Movies for The Avengers]
```

---

## Troubleshooting

### Frontend won't start
```bash
cd storyline-matcher-web
rm -rf node_modules package-lock.json
npm install
npm run dev
```

### Backend not responding
```bash
# Check if services are running
curl http://localhost:8000/api/movies/search?query=test
curl http://localhost:5001/health
```

### CORS errors
- Make sure backend .env has: `FRONTEND_URL=http://localhost:5173`
- Restart Laravel: `php artisan serve`

### TypeScript errors
```bash
cd storyline-matcher-web
npm run type-check
```

### Build for production
```bash
cd storyline-matcher-web
npm run build
# Creates dist/ folder with optimized files
```

---

## Next Steps

1. **Import more movies** - Get diverse content
2. **Test different genres** - Action, romance, horror, etc.
3. **Try creative plots** - Mix genres, be specific
4. **Check similarity scores** - See what works best
5. **Test on mobile** - Should be responsive

Enjoy testing! 🎬✨
