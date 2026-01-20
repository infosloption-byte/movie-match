import type { Movie } from '../types';
import MovieCard from './MovieCard';

interface MovieGridProps {
  movies: Movie[];
  onMovieClick?: (movie: Movie) => void;
  title?: string;
}

export default function MovieGrid({ movies, onMovieClick, title }: MovieGridProps) {
  if (movies.length === 0) {
    return null;
  }

  return (
    <div className="mb-12">
      {title && (
        <h2 className="text-2xl font-bold mb-6 text-gray-800">{title}</h2>
      )}
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        {movies.map((movie) => (
          <MovieCard
            key={movie.id}
            movie={movie}
            onClick={onMovieClick ? () => onMovieClick(movie) : undefined}
          />
        ))}
      </div>
    </div>
  );
}
