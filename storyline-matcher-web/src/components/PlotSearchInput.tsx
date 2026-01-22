import { useState, FormEvent } from 'react';

interface PlotSearchInputProps {
  onSearch: (plot: string) => void;
  isLoading: boolean;
}

export default function PlotSearchInput({ onSearch, isLoading }: PlotSearchInputProps) {
  const [plot, setPlot] = useState('');

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (plot.trim().length >= 10) {
      onSearch(plot.trim());
    }
  };

  const examplePlots = [
    "A team of heroes with superpowers must work together to save the world from an alien invasion",
    "A detective investigates a series of mysterious murders while uncovering dark secrets from the past",
    "A group of astronauts embark on a dangerous mission to explore a distant planet",
    "Two people from different worlds fall in love against all odds",
    "A young person discovers they have magical powers and must learn to control them",
  ];

  return (
    <div className="space-y-4">
      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label
            htmlFor="plot-input"
            className="block text-sm font-medium text-gray-700 mb-2"
          >
            Describe the movie plot/storyline you're looking for
          </label>
          <textarea
            id="plot-input"
            value={plot}
            onChange={(e) => setPlot(e.target.value)}
            placeholder="e.g., A team of heroes saves the world from an alien invasion..."
            className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
            rows={4}
            disabled={isLoading}
            minLength={10}
          />
          <p className="mt-1 text-sm text-gray-500">
            {plot.length < 10
              ? `Minimum 10 characters (${10 - plot.length} more needed)`
              : `${plot.length} characters`}
          </p>
        </div>

        <button
          type="submit"
          disabled={isLoading || plot.trim().length < 10}
          className="w-full bg-primary-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-primary-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
        >
          {isLoading ? (
            <>
              <svg
                className="animate-spin h-5 w-5"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  className="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  strokeWidth="4"
                />
                <path
                  className="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                />
              </svg>
              Searching...
            </>
          ) : (
            <>
              <svg
                xmlns="http://www.w3.org/2000/svg"
                className="h-5 w-5"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fillRule="evenodd"
                  d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                  clipRule="evenodd"
                />
              </svg>
              Find Similar Movies
            </>
          )}
        </button>
      </form>

      {/* Example Plots */}
      <div className="mt-6">
        <p className="text-sm font-medium text-gray-700 mb-2">Try these examples:</p>
        <div className="space-y-2">
          {examplePlots.map((examplePlot, index) => (
            <button
              key={index}
              onClick={() => setPlot(examplePlot)}
              className="w-full text-left px-3 py-2 text-sm text-gray-600 bg-gray-50 hover:bg-gray-100 rounded border border-gray-200 transition"
              disabled={isLoading}
            >
              {examplePlot}
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}
