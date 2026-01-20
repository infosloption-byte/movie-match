import type { Movie } from '../types';

interface MovieCardProps {
  movie: Movie;
  onClick?: () => void;
  similarityScore?: number;
}

const PLACEHOLDER_IMAGE = 'https://via.placeholder.com/500x750/1f2937/ffffff?text=No+Poster';

export default function MovieCard({ movie, onClick, similarityScore }: MovieCardProps) {
  return (
    <div
      onClick={onClick}
      className={`bg-white rounded-lg shadow-md overflow-hidden transition-transform hover:scale-105 ${
        onClick ? 'cursor-pointer' : ''
      }`}
    >
      <div className="relative aspect-[2/3] bg-gray-200">
        <img
          src={movie.poster_url || PLACEHOLDER_IMAGE}
          alt={movie.title}
          className="w-full h-full object-cover"
          loading="lazy"
          onError={(e) => {
            (e.target as HTMLImageElement).src = PLACEHOLDER_IMAGE;
          }}
        />
        {similarityScore !== undefined && (
          <div className="absolute top-2 right-2 bg-primary-600 text-white px-3 py-1 rounded-full text-sm font-bold">
            {(similarityScore * 100).toFixed(1)}% Match
          </div>
        )}
      </div>
      <div className="p-4">
        <h3 className="font-bold text-lg mb-1 line-clamp-2">{movie.title}</h3>
        {movie.release_year && (
          <p className="text-gray-600 text-sm mb-2">{movie.release_year}</p>
        )}
        {movie.plot_summary && (
          <p className="text-gray-700 text-sm line-clamp-3">{movie.plot_summary}</p>
        )}
      </div>
    </div>
  );
}
