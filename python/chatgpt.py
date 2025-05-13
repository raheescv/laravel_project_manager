import base64
import os
from openai import OpenAI
from PIL import Image
from io import BytesIO
import requests

def initialize_openai():
    # Initialize OpenAI client with error handling
    try:
        api_key = os.environ.get("OPENAI_API_KEY")
        if not api_key:
            raise ValueError("OPENAI_API_KEY environment variable is not set")
        client = OpenAI(api_key=api_key)
        # Test the client
        client.models.list()
        return client
    except Exception as e:
        print(f"Error initializing OpenAI client: {str(e)}")
        raise

def generate_and_save_image(client, prompt, output_path, size="1024x1024"):
    # Check if file already exists
    if os.path.exists(output_path):
        print(f"Image already exists at {output_path}")
        return

    try:
        print(f"Generating image with prompt: {prompt[:100]}...")  # Print first 100 chars of prompt

        # Generate the image
        result = client.images.generate(
            model="dall-e-3",
            prompt=prompt,
            size=size,
            quality="standard",
            n=1,
        )

        # Get the image URL from the response
        image_url = result.data[0].url

        # Save the image
        response = requests.get(image_url)
        response.raise_for_status()

        # Open and process the image
        image = Image.open(BytesIO(response.content))
        image = image.resize((300, 300), Image.Resampling.LANCZOS)
        image.save(output_path, format="JPEG", quality=80, optimize=True)
        print(f"Image successfully saved to {output_path}")

    except Exception as e:
        print(f"Error generating or saving image: {str(e)}")
        if hasattr(e, 'response'):
            print(f"Response details: {e.response.text if hasattr(e.response, 'text') else 'No response text'}")
        raise

# Create imgs/ folder for storing generated images
folder_path = "imgs"
os.makedirs(folder_path, exist_ok=True)

# Simplified prompt
prompt1 = """Create a cute, friendly alien character named Glorptak. It should be an amorphous, gelatinous blob with a translucent lavender color. Include 3-5 floating orb-like eyes and make it glow with bioluminescent accents in neon pink and blue. The character should appear bouncy and playful."""

if __name__ == "__main__":
    try:
        # Initialize the OpenAI client
        client = initialize_openai()
        print("OpenAI client initialized successfully")

        # Generate and save the image
        img_path1 = "imgs/glorptak.jpg"
        generate_and_save_image(client, prompt1, img_path1)
    except Exception as e:
        print(f"An error occurred: {str(e)}")
        if hasattr(e, 'response'):
            print(f"Response details: {e.response.text if hasattr(e.response, 'text') else 'No response text'}")
