import type { SimilarMovie } from '../types';
import MovieCard from './MovieCard';

interface SimilarMoviesProps {
  movies: SimilarMovie[];
  onMovieClick?: (movie: SimilarMovie) => void;
}

export default function SimilarMovies({ movies, onMovieClick }: SimilarMoviesProps) {
  if (movies.length === 0) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-600 text-lg">
          No similar movies found. The selected movie may not have an embedding yet.
        </p>
      </div>
    );
  }

  return (
    <div>
      <h2 className="text-2xl font-bold mb-6 text-gray-800">
        Similar Movies by Storyline
      </h2>
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        {movies.map((movie) => (
          <MovieCard
            key={movie.id}
            movie={movie}
            similarityScore={movie.similarity_score}
            onClick={onMovieClick ? () => onMovieClick(movie) : undefined}
          />
        ))}
      </div>
    </div>
  );
}
