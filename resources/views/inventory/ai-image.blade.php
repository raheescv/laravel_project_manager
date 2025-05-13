<x-app-layout>

    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Generate Product Images using AI</h5>
                </div>
                <div class="card-body">
                    <form id="imageGeneratorForm" class="row g-3">
                        @csrf
                        <div class="col-md-12">
                            <label for="product_name" class="form-label">Product Description</label>
                            <input class="form-control" id="product_name" name="product_name" value='Hair Cut' placeholder="Enter detailed product description for better results">
                            <small class="text-muted">Tip: Include details like color, material, style, and perspective for better results</small>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary" id="generateBtn">
                                Generate Image
                            </button>
                        </div>
                    </form>

                    <div class="mt-4" id="resultContainer" style="display: none;">
                        <h6>Generated Image:</h6>
                        <div class="text-center">
                            <img id="generatedImage" src="" alt="Generated product image" class="img-fluid" style="max-width: 512px;">
                        </div>
                        <div class="mt-3 text-center">
                            <button class="btn btn-success" id="saveImageBtn">Save Image</button>
                        </div>
                    </div>

                    <div class="mt-3" id="loadingIndicator" style="display: none;">
                        <div class="d-flex justify-content-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <p class="text-center mt-2">Generating image... This may take a few moments.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('imageGeneratorForm');
                const loadingIndicator = document.getElementById('loadingIndicator');
                const resultContainer = document.getElementById('resultContainer');
                const generatedImage = document.getElementById('generatedImage');
                const generateBtn = document.getElementById('generateBtn');

                form.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    // Show loading indicator
                    loadingIndicator.style.display = 'block';
                    resultContainer.style.display = 'none';
                    generateBtn.disabled = true;

                    try {
                        const response = await fetch('{{ route('inventory::ai-image.generate') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                            },
                            body: JSON.stringify({
                                product_name: document.getElementById('product_name').value
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            generatedImage.src = data.image_url;
                            resultContainer.style.display = 'block';
                        } else {
                            // Show error message in a more user-friendly way
                            const errorMsg = data.message || 'Failed to generate image';
                            document.getElementById('errorAlert')?.remove(); // Remove any existing error alert
                            const errorDiv = document.createElement('div');
                            errorDiv.id = 'errorAlert';
                            errorDiv.className = 'alert alert-danger mt-3';
                            errorDiv.textContent = errorMsg;
                            form.insertAdjacentElement('afterend', errorDiv);
                            throw new Error(errorMsg);
                        }
                    } catch (error) {
                        // Show the full error message from the server or the catch block
                        const errorMsg = error.message || 'An unexpected error occurred';
                        document.getElementById('errorAlert')?.remove(); // Remove any existing error alert
                        const errorDiv = document.createElement('div');
                        errorDiv.id = 'errorAlert';
                        errorDiv.className = 'alert alert-danger mt-3';
                        errorDiv.textContent = errorMsg;
                        form.insertAdjacentElement('afterend', errorDiv);
                    } finally {
                        loadingIndicator.style.display = 'none';
                        generateBtn.disabled = false;
                    }
                });

                // Handle save image button
                document.getElementById('saveImageBtn').addEventListener('click', async function() {
                    if (generatedImage.src) {
                        const link = document.createElement('a');
                        link.href = generatedImage.src;
                        link.download = 'generated-product-image.png';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                });
            });
        </script>
    @endpush

</x-app-layout>
