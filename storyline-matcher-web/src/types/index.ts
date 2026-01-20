export interface Movie {
  id: number;
  tmdb_id: number;
  title: string;
  plot_summary: string | null;
  release_year: number | null;
  poster_url: string | null;
  popularity: number;
  has_embedding?: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface SimilarMovie extends Movie {
  similarity_score: number;
}

export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  message?: string;
  errors?: Record<string, string[]>;
}

export interface SearchResponse {
  data: Movie[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface SimilarMoviesResponse {
  movie: Movie;
  similar_movies: SimilarMovie[];
  source: 'cache' | 'calculated';
}
