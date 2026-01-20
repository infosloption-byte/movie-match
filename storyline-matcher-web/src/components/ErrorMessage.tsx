interface ErrorMessageProps {
  message: string;
  onRetry?: () => void;
}

export default function ErrorMessage({ message, onRetry }: ErrorMessageProps) {
  return (
    <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <div className="flex items-start">
        <div className="flex-1">
          <h3 className="text-red-800 font-semibold mb-1">Error</h3>
          <p className="text-red-700">{message}</p>
        </div>
        {onRetry && (
          <button
            onClick={onRetry}
            className="ml-4 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
          >
            Retry
          </button>
        )}
      </div>
    </div>
  );
}
