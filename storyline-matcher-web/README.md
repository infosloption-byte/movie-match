# Storyline Matcher Web (React Frontend)

Frontend application for the Storyline Matcher movie recommendation system.

## Installation

```bash
# Install dependencies
npm install

# Copy environment file
cp .env.example .env

# Start development server
npm run dev
```

The application will be available at `http://localhost:5173`

## Configuration

Edit `.env` to configure the API URL:

```env
VITE_API_URL=http://localhost:8000/api
```

## Features

### Components

- **SearchBar** - Search input with loading state
- **MovieCard** - Display individual movie with poster and details
- **MovieGrid** - Responsive grid layout for movie lists
- **SimilarMovies** - Display similar movies with similarity scores
- **Loading** - Loading spinner with customizable message
- **ErrorMessage** - Error display with retry option

### Services

- **api.ts** - Axios-based API client with TypeScript types

### Types

Full TypeScript type definitions for:
- Movie
- SimilarMovie
- ApiResponse
- SearchResponse
- SimilarMoviesResponse

## Build for Production

```bash
npm run build
```

Output will be in the `dist/` folder.

## Deployment

### Static Hosting

Deploy the `dist` folder to:
- Netlify
- Vercel
- AWS S3 + CloudFront
- GitHub Pages

### Environment Variables

Set `VITE_API_URL` to your production API URL.

### Example Netlify Deployment

```bash
npm run build
netlify deploy --prod --dir=dist
```

## Development

### Project Structure

```
src/
├── components/       # React components
│   ├── SearchBar.tsx
│   ├── MovieCard.tsx
│   ├── MovieGrid.tsx
│   ├── SimilarMovies.tsx
│   ├── Loading.tsx
│   └── ErrorMessage.tsx
├── services/        # API services
│   └── api.ts
├── types/          # TypeScript types
│   └── index.ts
├── App.tsx         # Main application
├── main.tsx        # Entry point
└── index.css       # Global styles
```

### Styling

This project uses Tailwind CSS for styling. Configuration in `tailwind.config.js`.

### API Integration

All API calls go through the `api` service:

```typescript
import { api } from './services/api';

// Search movies
const results = await api.searchMovies('inception');

// Get similar movies
const similar = await api.getSimilarMovies(movieId, 10, 0.7);
```

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers

## Performance

- Lazy loading for movie posters
- Responsive images
- Optimized bundle size with Vite
- Code splitting

## Troubleshooting

### CORS errors

Make sure the backend `.env` has correct `FRONTEND_URL`:

```env
FRONTEND_URL=http://localhost:5173
```

### API connection issues

Check that:
1. Backend server is running on port 8000
2. `.env` has correct `VITE_API_URL`
3. No firewall blocking requests
