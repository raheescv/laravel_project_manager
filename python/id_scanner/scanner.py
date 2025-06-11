from flask import Flask, request, jsonify
import cv2
import numpy as np
import pytesseract
from PIL import Image
import io
import re
from datetime import datetime
import os
from dotenv import load_dotenv
from flask_cors import CORS

load_dotenv()

# Configure Tesseract path for macOS
pytesseract.pytesseract.tesseract_cmd = '/opt/homebrew/bin/tesseract'

app = Flask(__name__)
# Configure CORS with specific settings
CORS(app, resources={
    r"/*": {
        "origins": ["http://localhost:8000", "http://127.0.0.1:8000","https://project_manager.test"],  # Add your Laravel app URL
        "methods": ["GET", "POST", "OPTIONS"],
        "allow_headers": ["Content-Type", "Accept", "X-Requested-With"],
        "supports_credentials": False
    }
})

class IDCardScanner:
    def __init__(self):
        self.ocr = pytesseract
        # Configure Tesseract path if needed
        # pytesseract.pytesseract.tesseract_cmd = r'/usr/local/bin/tesseract'

    def preprocess_image(self, image):
        # Convert to grayscale
        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)

        # Apply thresholding to get better text
        _, thresh = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)

        # Noise removal
        kernel = np.ones((1, 1), np.uint8)
        opening = cv2.morphologyEx(thresh, cv2.MORPH_OPEN, kernel)

        return opening

    def extract_text(self, image):
        # Preprocess the image
        processed_image = self.preprocess_image(image)

        # Extract text using Tesseract
        text = self.ocr.image_to_string(processed_image)
        return text

    def parse_id_data(self, text):
        # Initialize result dictionary
        result = {
            'name': None,
            'dob': None,
            'id_number': None,
            'address': None
        }

        # Extract name (assuming it's in the first few lines)
        lines = text.split('\n')
        for line in lines[:3]:
            if line.strip() and not any(char.isdigit() for char in line):
                result['name'] = line.strip()
                break

        # Extract DOB (looking for date patterns)
        dob_pattern = r'\d{2}[-/]\d{2}[-/]\d{4}'
        dob_match = re.search(dob_pattern, text)
        if dob_match:
            try:
                dob_str = dob_match.group()
                result['dob'] = datetime.strptime(dob_str, '%d/%m/%Y').strftime('%Y-%m-%d')
            except:
                pass

        # Extract ID number (assuming it's a sequence of numbers)
        id_pattern = r'[A-Z0-9]{10,}'
        id_match = re.search(id_pattern, text)
        if id_match:
            result['id_number'] = id_match.group()

        # Extract address (assuming it's a longer line with common address terms)
        address_pattern = r'(?:Address|Addr|Location)[:\s]+(.+?)(?:\n|$)'
        address_match = re.search(address_pattern, text, re.IGNORECASE)
        if address_match:
            result['address'] = address_match.group(1).strip()

        return result

    def process_image(self, image_data):
        try:
            # Convert image data to numpy array
            nparr = np.frombuffer(image_data, np.uint8)
            img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

            if img is None:
                return {'error': 'Invalid image data'}, 400

            # Extract text from image
            text = self.extract_text(img)

            # Parse the extracted text
            result = self.parse_id_data(text)

            return result, 200

        except Exception as e:
            return {'error': str(e)}, 500

@app.route('/scan', methods=['POST'])
def scan_id_card():
    if 'image' not in request.files:
        return jsonify({'error': 'No image provided'}), 400

    try:
        image_file = request.files['image']
        image_data = image_file.read()

        scanner = IDCardScanner()
        result, status_code = scanner.process_image(image_data)

        return jsonify(result), status_code

    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/health', methods=['GET'])
def health_check():
    return jsonify({'status': 'healthy'}), 200

if __name__ == '__main__':
    port = int(os.getenv('ID_SCANNER_PORT', 5000))
    app.run(host='0.0.0.0', port=port)
