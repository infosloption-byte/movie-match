import { useState } from 'react';
import SearchBar from './components/SearchBar';
import MovieGrid from './components/MovieGrid';
import SimilarMovies from './components/SimilarMovies';
import Loading from './components/Loading';
import ErrorMessage from './components/ErrorMessage';
import { api } from './services/api';
import type { Movie, SimilarMovie } from './types';

function App() {
  const [searchResults, setSearchResults] = useState<Movie[]>([]);
  const [selectedMovie, setSelectedMovie] = useState<Movie | null>(null);
  const [similarMovies, setSimilarMovies] = useState<SimilarMovie[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [isLoadingSimilar, setIsLoadingSimilar] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSearch = async (query: string) => {
    setIsSearching(true);
    setError(null);
    setSelectedMovie(null);
    setSimilarMovies([]);

    try {
      const response = await api.searchMovies(query);
      setSearchResults(response.data);

      if (response.data.length === 0) {
        setError('No movies found. Try a different search term.');
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to search movies');
      setSearchResults([]);
    } finally {
      setIsSearching(false);
    }
  };

  const handleMovieClick = async (movie: Movie) => {
    setSelectedMovie(movie);
    setIsLoadingSimilar(true);
    setError(null);

    try {
      const response = await api.getSimilarMovies(movie.id);
      setSimilarMovies(response.similar_movies);

      if (response.similar_movies.length === 0) {
        setError(
          'No similar movies found. This movie may not have an embedding generated yet.'
        );
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load similar movies');
      setSimilarMovies([]);
    } finally {
      setIsLoadingSimilar(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
      {/* Header */}
      <header className="bg-white shadow-sm border-b">
        <div className="container mx-auto px-4 py-6">
          <h1 className="text-3xl font-bold text-gray-900 text-center">
            🎬 Storyline Matcher
          </h1>
          <p className="text-gray-600 text-center mt-2">
            Find movies with similar storylines using AI embeddings
          </p>
        </div>
      </header>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-8">
        {/* Search Section */}
        <section className="mb-8">
          <SearchBar onSearch={handleSearch} isLoading={isSearching} />
        </section>

        {/* Error Message */}
        {error && (
          <ErrorMessage
            message={error}
            onRetry={() => {
              if (selectedMovie) {
                handleMovieClick(selectedMovie);
              }
            }}
          />
        )}

        {/* Loading State */}
        {isSearching && <Loading message="Searching for movies..." />}

        {/* Search Results */}
        {!isSearching && searchResults.length > 0 && !selectedMovie && (
          <MovieGrid
            movies={searchResults}
            onMovieClick={handleMovieClick}
            title="Search Results"
          />
        )}

        {/* Selected Movie & Similar Movies */}
        {selectedMovie && (
          <div>
            <button
              onClick={() => {
                setSelectedMovie(null);
                setSimilarMovies([]);
                setError(null);
              }}
              className="mb-6 text-primary-600 hover:text-primary-700 font-semibold flex items-center gap-2"
            >
              ← Back to search results
            </button>

            <div className="mb-8 bg-white rounded-lg shadow-md p-6">
              <h2 className="text-2xl font-bold mb-4">Selected Movie</h2>
              <div className="flex gap-6">
                <img
                  src={selectedMovie.poster_url || 'https://via.placeholder.com/200x300'}
                  alt={selectedMovie.title}
                  className="w-48 h-72 object-cover rounded-lg shadow"
                  onError={(e) => {
                    (e.target as HTMLImageElement).src =
                      'https://via.placeholder.com/200x300';
                  }}
                />
                <div className="flex-1">
                  <h3 className="text-xl font-bold mb-2">{selectedMovie.title}</h3>
                  {selectedMovie.release_year && (
                    <p className="text-gray-600 mb-4">Year: {selectedMovie.release_year}</p>
                  )}
                  {selectedMovie.plot_summary && (
                    <p className="text-gray-700">{selectedMovie.plot_summary}</p>
                  )}
                </div>
              </div>
            </div>

            {isLoadingSimilar ? (
              <Loading message="Finding similar movies..." />
            ) : (
              <SimilarMovies movies={similarMovies} onMovieClick={handleMovieClick} />
            )}
          </div>
        )}

        {/* Empty State */}
        {!isSearching &&
          searchResults.length === 0 &&
          !selectedMovie &&
          !error && (
            <div className="text-center py-20">
              <div className="text-6xl mb-4">🎥</div>
              <h2 className="text-2xl font-semibold text-gray-700 mb-2">
                Discover Similar Movies
              </h2>
              <p className="text-gray-600 max-w-md mx-auto">
                Search for a movie by title to find other movies with similar storylines
                based on AI-powered semantic analysis.
              </p>
            </div>
          )}
      </main>

      {/* Footer */}
      <footer className="bg-white border-t mt-16">
        <div className="container mx-auto px-4 py-6 text-center text-gray-600">
          <p>
            Powered by OpenAI Embeddings, TMDB API, and pgvector
          </p>
        </div>
      </footer>
    </div>
  );
}

export default App;
