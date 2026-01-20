import axios, { AxiosInstance } from 'axios';
import type { ApiResponse, Movie, SearchResponse, SimilarMoviesResponse } from '../types';

class ApiService {
  private client: AxiosInstance;

  constructor() {
    this.client = axios.create({
      baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });
  }

  /**
   * Search movies by title
   */
  async searchMovies(query: string, page = 1, perPage = 20): Promise<SearchResponse> {
    const response = await this.client.get<ApiResponse<any>>('/movies/search', {
      params: { query, page, per_page: perPage },
    });

    if (!response.data.success) {
      throw new Error(response.data.message || 'Search failed');
    }

    // The API returns { success, data: [], pagination: {} }
    // We need to restructure it to match SearchResponse type
    return {
      data: response.data.data || [],
      pagination: response.data.pagination || {
        current_page: 1,
        per_page: perPage,
        total: 0,
        last_page: 1,
      },
    };
  }

  /**
   * Get movie details by ID
   */
  async getMovie(id: number): Promise<Movie> {
    const response = await this.client.get<ApiResponse<Movie>>(`/movies/${id}`);

    if (!response.data.success || !response.data.data) {
      throw new Error(response.data.message || 'Failed to fetch movie');
    }

    return response.data.data;
  }

  /**
   * Get similar movies based on plot
   */
  async getSimilarMovies(
    id: number,
    limit = 10,
    threshold = 0.7,
    useCache = false
  ): Promise<SimilarMoviesResponse> {
    const response = await this.client.get<ApiResponse<SimilarMoviesResponse>>(
      `/movies/${id}/similar`,
      {
        params: { limit, threshold, use_cache: useCache },
      }
    );

    if (!response.data.success || !response.data.data) {
      throw new Error(response.data.message || 'Failed to fetch similar movies');
    }

    return response.data.data;
  }

  /**
   * Generate embedding for a movie
   */
  async generateEmbedding(id: number): Promise<void> {
    const response = await this.client.post<ApiResponse<any>>(
      `/movies/${id}/generate-embedding`
    );

    if (!response.data.success) {
      throw new Error(response.data.message || 'Failed to generate embedding');
    }
  }

  /**
   * Import movies from TMDB
   */
  async importMovies(source: 'popular' | 'search' | 'discover', query?: string, page = 1): Promise<any> {
    const response = await this.client.post<ApiResponse<any>>('/movies/import', {
      source,
      query,
      page,
    });

    if (!response.data.success) {
      throw new Error(response.data.message || 'Failed to import movies');
    }

    return response.data.data;
  }
}

export const api = new ApiService();
