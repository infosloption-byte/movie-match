import { useState } from 'react';
import SearchBar from './components/SearchBar';
import PlotSearchInput from './components/PlotSearchInput';
import MovieGrid from './components/MovieGrid';
import SimilarMovies from './components/SimilarMovies';
import Loading from './components/Loading';
import ErrorMessage from './components/ErrorMessage';
import { api } from './services/api';
import type { Movie, SimilarMovie } from './types';

type SearchMode = 'title' | 'plot';

function App() {
  const [searchMode, setSearchMode] = useState<SearchMode>('title');
  const [searchResults, setSearchResults] = useState<Movie[]>([]);
  const [selectedMovie, setSelectedMovie] = useState<Movie | null>(null);
  const [similarMovies, setSimilarMovies] = useState<SimilarMovie[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [isLoadingSimilar, setIsLoadingSimilar] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [plotSearchQuery, setPlotSearchQuery] = useState<string>('');

  // Scenario 1: Search by title
  const handleTitleSearch = async (query: string) => {
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

  // Scenario 2: Search by custom plot
  const handlePlotSearch = async (plot: string) => {
    setIsSearching(true);
    setError(null);
    setSelectedMovie(null);
    setSearchResults([]);
    setPlotSearchQuery(plot);

    try {
      const response = await api.searchByPlot(plot, 10, 0.6);
      setSimilarMovies(response.similar_movies);

      if (response.similar_movies.length === 0) {
        setError('No similar movies found for this plot. Try a different description.');
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to search by plot');
      setSimilarMovies([]);
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

  const resetSearch = () => {
    setSelectedMovie(null);
    setSimilarMovies([]);
    setSearchResults([]);
    setError(null);
    setPlotSearchQuery('');
  };

  const handleModeChange = (mode: SearchMode) => {
    setSearchMode(mode);
    resetSearch();
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
      <main className="container mx-auto px-4 py-8 max-w-6xl">
        {/* Mode Selector Tabs */}
        <div className="mb-8">
          <div className="bg-white rounded-lg shadow-sm p-2 inline-flex gap-2">
            <button
              onClick={() => handleModeChange('title')}
              className={`px-6 py-3 rounded-md font-semibold transition ${
                searchMode === 'title'
                  ? 'bg-primary-600 text-white'
                  : 'text-gray-600 hover:bg-gray-100'
              }`}
            >
              📽️ Scenario 1: Search by Title
            </button>
            <button
              onClick={() => handleModeChange('plot')}
              className={`px-6 py-3 rounded-md font-semibold transition ${
                searchMode === 'plot'
                  ? 'bg-primary-600 text-white'
                  : 'text-gray-600 hover:bg-gray-100'
              }`}
            >
              ✨ Scenario 2: Search by Plot
            </button>
          </div>

          {/* Mode Description */}
          <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p className="text-sm text-blue-800">
              {searchMode === 'title' ? (
                <>
                  <strong>Scenario 1:</strong> Search for a movie by title, view its details, then
                  discover similar movies based on plot similarity.
                </>
              ) : (
                <>
                  <strong>Scenario 2:</strong> Describe a custom plot or storyline, and find movies
                  that match your description using AI-powered semantic search.
                </>
              )}
            </p>
          </div>
        </div>

        {/* Search Section */}
        <section className="mb-8">
          <div className="bg-white rounded-lg shadow-md p-6">
            {searchMode === 'title' ? (
              <SearchBar onSearch={handleTitleSearch} isLoading={isSearching} />
            ) : (
              <PlotSearchInput onSearch={handlePlotSearch} isLoading={isSearching} />
            )}
          </div>
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
        {isSearching && (
          <Loading
            message={
              searchMode === 'title' ? 'Searching for movies...' : 'Finding similar movies...'
            }
          />
        )}

        {/* Scenario 1: Search Results (Title-based) */}
        {searchMode === 'title' &&
          !isSearching &&
          searchResults.length > 0 &&
          !selectedMovie && (
            <MovieGrid
              movies={searchResults}
              onMovieClick={handleMovieClick}
              title="Search Results - Click a movie to see similar ones"
            />
          )}

        {/* Selected Movie & Similar Movies (From Scenario 1) */}
        {selectedMovie && (
          <div>
            <button
              onClick={resetSearch}
              className="mb-6 text-primary-600 hover:text-primary-700 font-semibold flex items-center gap-2"
            >
              ← Back to {searchMode === 'title' ? 'search results' : 'plot search'}
            </button>

            <div className="mb-8 bg-white rounded-lg shadow-md p-6">
              <h2 className="text-2xl font-bold mb-4">Selected Movie</h2>
              <div className="flex flex-col md:flex-row gap-6">
                <img
                  src={selectedMovie.poster_url || 'https://via.placeholder.com/200x300'}
                  alt={selectedMovie.title}
                  className="w-48 h-72 object-cover rounded-lg shadow mx-auto md:mx-0"
                  onError={(e) => {
                    (e.target as HTMLImageElement).src =
                      'https://via.placeholder.com/200x300?text=No+Poster';
                  }}
                />
                <div className="flex-1">
                  <h3 className="text-xl font-bold mb-2">{selectedMovie.title}</h3>
                  {selectedMovie.release_year && (
                    <p className="text-gray-600 mb-4">📅 Year: {selectedMovie.release_year}</p>
                  )}
                  {selectedMovie.plot_summary && (
                    <div>
                      <h4 className="font-semibold text-gray-800 mb-2">Plot Summary:</h4>
                      <p className="text-gray-700 leading-relaxed">{selectedMovie.plot_summary}</p>
                    </div>
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

        {/* Scenario 2: Plot-based Search Results */}
        {searchMode === 'plot' &&
          !isSearching &&
          !selectedMovie &&
          similarMovies.length > 0 && (
            <div>
              {plotSearchQuery && (
                <div className="mb-6 bg-white rounded-lg shadow-md p-6">
                  <h3 className="text-lg font-semibold mb-2">Your Plot Description:</h3>
                  <p className="text-gray-700 italic">"{plotSearchQuery}"</p>
                </div>
              )}
              <SimilarMovies
                movies={similarMovies}
                onMovieClick={handleMovieClick}
                title="Movies Matching Your Plot"
              />
            </div>
          )}

        {/* Empty State */}
        {!isSearching &&
          searchResults.length === 0 &&
          similarMovies.length === 0 &&
          !selectedMovie &&
          !error && (
            <div className="text-center py-20">
              <div className="text-6xl mb-4">
                {searchMode === 'title' ? '🎥' : '✨'}
              </div>
              <h2 className="text-2xl font-semibold text-gray-700 mb-2">
                {searchMode === 'title' ? 'Ready to Discover' : 'Describe Your Perfect Movie'}
              </h2>
              <p className="text-gray-600 max-w-md mx-auto">
                {searchMode === 'title'
                  ? 'Search for a movie by title to find similar movies based on AI-powered plot analysis.'
                  : 'Enter a plot description and let AI find movies that match your storyline.'}
              </p>
            </div>
          )}
      </main>

      {/* Footer */}
      <footer className="bg-white border-t mt-16">
        <div className="container mx-auto px-4 py-6 text-center text-gray-600">
          <p className="text-sm">
            Powered by Local Embeddings, TMDB API, and Sentence Transformers
          </p>
        </div>
      </footer>
    </div>
  );
}

export default App;
