# Local Embedding Service

A simple Flask API that generates embeddings locally using sentence-transformers.

## Features
- ✅ **Completely Free** - No API costs
- ✅ **Unlimited** - No rate limits
- ✅ **Fast** - Runs locally on your machine
- ✅ **Private** - Your data never leaves your computer
- ✅ **384-dimensional embeddings** using `all-MiniLM-L6-v2` model

## Prerequisites

- Python 3.8 or higher
- pip (Python package installer)

## Installation

### Windows (PowerShell)

```powershell
# Navigate to the embedding service directory
cd C:\wamp\www\html\movie-match\embedding-service

# Create a virtual environment
python -m venv venv

# Activate the virtual environment
.\venv\Scripts\Activate

# Install dependencies
pip install -r requirements.txt
```

### Linux/Mac

```bash
# Navigate to the embedding service directory
cd /path/to/movie-match/embedding-service

# Create a virtual environment
python3 -m venv venv

# Activate the virtual environment
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt
```

## Running the Service

```powershell
# Make sure the virtual environment is activated
.\venv\Scripts\Activate  # Windows
# or
source venv/bin/activate  # Linux/Mac

# Run the service
python app.py
```

The service will start on http://localhost:5001

## API Endpoints

### Health Check
```
GET /health
```

### Generate Single Embedding
```
POST /embed
Content-Type: application/json

{
  "text": "Your text here"
}
```

### Generate Batch Embeddings
```
POST /embed/batch
Content-Type: application/json

{
  "texts": ["text 1", "text 2", "text 3"]
}
```

## Testing

Once running, test with:

```powershell
curl http://localhost:5001/health
```

You should see:
```json
{
  "status": "healthy",
  "model": "sentence-transformers/all-MiniLM-L6-v2",
  "embedding_dimension": 384
}
```

## Note

The first time you run this, it will download the model (~90MB). This only happens once!
