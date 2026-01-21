"""
Simple Local Embedding Service
Generates embeddings using sentence-transformers locally
No API calls, no rate limits, completely free!
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
from sentence_transformers import SentenceTransformer
import logging

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)
CORS(app)

# Load the embedding model (first time will download ~90MB)
logger.info("Loading sentence-transformers model...")
model = SentenceTransformer('sentence-transformers/all-MiniLM-L6-v2')
logger.info("Model loaded successfully!")


@app.route('/health', methods=['GET'])
def health():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'model': 'sentence-transformers/all-MiniLM-L6-v2',
        'embedding_dimension': 384
    })


@app.route('/embed', methods=['POST'])
def generate_embedding():
    """Generate embedding for a single text"""
    try:
        data = request.get_json()

        if not data or 'text' not in data:
            return jsonify({
                'error': 'Missing "text" field in request body'
            }), 400

        text = data['text']

        if not text or not text.strip():
            return jsonify({
                'error': 'Text cannot be empty'
            }), 400

        # Generate embedding
        logger.info(f"Generating embedding for text of length {len(text)}")
        embedding = model.encode(text, convert_to_tensor=False)

        # Convert to list for JSON serialization
        embedding_list = embedding.tolist()

        return jsonify({
            'embedding': embedding_list,
            'dimension': len(embedding_list)
        })

    except Exception as e:
        logger.error(f"Error generating embedding: {str(e)}")
        return jsonify({
            'error': f'Failed to generate embedding: {str(e)}'
        }), 500


@app.route('/embed/batch', methods=['POST'])
def generate_batch_embeddings():
    """Generate embeddings for multiple texts"""
    try:
        data = request.get_json()

        if not data or 'texts' not in data:
            return jsonify({
                'error': 'Missing "texts" field in request body'
            }), 400

        texts = data['texts']

        if not isinstance(texts, list):
            return jsonify({
                'error': 'texts must be an array'
            }), 400

        if not texts:
            return jsonify({
                'error': 'texts array cannot be empty'
            }), 400

        # Generate embeddings
        logger.info(f"Generating embeddings for {len(texts)} texts")
        embeddings = model.encode(texts, convert_to_tensor=False)

        # Convert to list for JSON serialization
        embeddings_list = [emb.tolist() for emb in embeddings]

        return jsonify({
            'embeddings': embeddings_list,
            'count': len(embeddings_list),
            'dimension': len(embeddings_list[0]) if embeddings_list else 0
        })

    except Exception as e:
        logger.error(f"Error generating batch embeddings: {str(e)}")
        return jsonify({
            'error': f'Failed to generate batch embeddings: {str(e)}'
        }), 500


if __name__ == '__main__':
    print("="*50)
    print("🚀 Local Embedding Service")
    print("="*50)
    print("Model: sentence-transformers/all-MiniLM-L6-v2")
    print("Embedding Dimension: 384")
    print("Server running on http://localhost:5001")
    print("="*50)

    app.run(host='0.0.0.0', port=5001, debug=False)
