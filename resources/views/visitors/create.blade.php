<x-app-layout>
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-0 text-gray-800">
                    <i class="fa fa-user-plus me-2"></i>Register New Visitor
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('visitors.index') }}">Visitors</a></li>
                        <li class="breadcrumb-item active">Register</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('visitors.index') }}" class="btn btn-light">
                <i class="fa fa-arrow-left me-2"></i>Back to List
            </a>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <form action="{{ route('visitors.store') }}" method="POST" enctype="multipart/form-data" id="visitorForm">
                    @csrf
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-transparent border-0 py-3">
                            <h5 class="card-title mb-0">
                                <i class="fa fa-id-card me-2 text-primary"></i>ID Card Scan
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <!-- Webcam Capture -->
                                <div class="col-md-6">
                                    <div class="card bg-light bg-opacity-50 border-0">
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-3 text-muted">Capture ID Card</h6>
                                            <div class="ratio ratio-4x3 mb-3">
                                                <video id="webcam" class="rounded" autoplay playsinline></video>
                                            </div>
                                            <div class="d-grid">
                                                <button type="button" id="captureBtn" class="btn btn-primary">
                                                    <i class="fa fa-camera me-2"></i>Capture
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- File Upload -->
                                <div class="col-md-6">
                                    <div class="card bg-light bg-opacity-50 border-0">
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-3 text-muted">Upload ID Card Image</h6>
                                            <div class="mb-3">
                                                <input type="file" name="id_card_image" id="idCardImage" class="form-control" accept="image/*" required>
                                                <div class="form-text">Upload a clear image of the ID card</div>
                                            </div>
                                            <div id="previewContainer" class="d-none">
                                                <div class="ratio ratio-4x3 mb-3">
                                                    <img id="imagePreview" class="rounded" alt="ID Card Preview">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Extracted Information -->
                            <div id="extractedInfo" class="mt-4 d-none">
                                <div class="alert alert-info mb-0">
                                    <h6 class="alert-heading mb-3">
                                        <i class="fa fa-info-circle me-2"></i>Extracted Information
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Name</label>
                                            <input type="text" class="form-control" id="extractedName" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Date of Birth</label>
                                            <input type="text" class="form-control" id="extractedDOB" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">ID Card Number</label>
                                            <input type="text" class="form-control" id="extractedIDNumber" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Address</label>
                                            <input type="text" class="form-control" id="extractedAddress" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Visitor Information -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 py-3">
                            <h5 class="card-title mb-0">
                                <i class="fa fa-user me-2 text-primary"></i>Visitor Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                                </div>
                                <div class="col-md-6">
                                    <label for="id_card_number" class="form-label">ID Card Number</label>
                                    <input type="text" class="form-control" id="id_card_number" name="id_card_number">
                                </div>
                                <div class="col-md-6">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address">
                                </div>
                                <div class="col-md-12">
                                    <label for="purpose_of_visit" class="form-label">Purpose of Visit</label>
                                    <textarea class="form-control" id="purpose_of_visit" name="purpose_of_visit" rows="2" required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="host_employee_id" class="form-label">Host Employee</label>
                                    <select class="form-select" id="host_employee_id" name="host_employee_id">
                                        <option value="">Select Host Employee</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="host_department" class="form-label">Department</label>
                                    <input type="text" class="form-control" id="host_department" name="host_department">
                                </div>
                                <div class="col-md-12">
                                    <label for="notes" class="form-label">Additional Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 py-3">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('visitors.index') }}" class="btn btn-light">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save me-2"></i>Register Visitor
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Instructions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h5 class="card-title mb-0">
                            <i class="fa fa-info-circle me-2 text-info"></i>Instructions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light mb-0">
                            <h6 class="alert-heading mb-3">How to Register a Visitor</h6>
                            <ol class="mb-0 ps-3">
                                <li class="mb-2">Capture or upload a clear image of the visitor's ID card</li>
                                <li class="mb-2">Verify the extracted information</li>
                                <li class="mb-2">Fill in any missing or additional information</li>
                                <li class="mb-2">Select the host employee and department</li>
                                <li>Add any additional notes if needed</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Tips -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h5 class="card-title mb-0">
                            <i class="fa fa-lightbulb me-2 text-warning"></i>Tips
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning bg-opacity-10 mb-0">
                            <ul class="mb-0 ps-3">
                                <li class="mb-2">Ensure good lighting when capturing ID card</li>
                                <li class="mb-2">Keep the ID card steady and in focus</li>
                                <li class="mb-2">Verify all information before submitting</li>
                                <li>Double-check the host employee selection</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Webcam setup
            const webcam = document.getElementById('webcam');
            const captureBtn = document.getElementById('captureBtn');
            const imagePreview = document.getElementById('imagePreview');
            const previewContainer = document.getElementById('previewContainer');
            const idCardImage = document.getElementById('idCardImage');
            const extractedInfo = document.getElementById('extractedInfo');
            const form = document.getElementById('visitorForm');

            // Initialize webcam
            async function initWebcam() {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: true
                    });
                    webcam.srcObject = stream;
                } catch (err) {
                    console.error('Error accessing webcam:', err);
                    alert('Unable to access webcam. Please upload an image instead.');
                }
            }

            // Capture image from webcam
            captureBtn.addEventListener('click', () => {
                const canvas = document.createElement('canvas');
                canvas.width = webcam.videoWidth;
                canvas.height = webcam.videoHeight;
                canvas.getContext('2d').drawImage(webcam, 0, 0);

                // Convert to blob and create file
                canvas.toBlob((blob) => {
                    const file = new File([blob], 'id-card.jpg', {
                        type: 'image/jpeg'
                    });
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    idCardImage.files = dataTransfer.files;

                    // Show preview
                    imagePreview.src = canvas.toDataURL('image/jpeg');
                    previewContainer.classList.remove('d-none');

                    // Process image
                    processImage(file);
                }, 'image/jpeg');
            });

            // Handle file upload
            idCardImage.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    // Show preview
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        imagePreview.src = e.target.result;
                        previewContainer.classList.remove('d-none');
                    };
                    reader.readAsDataURL(file);

                    // Process image
                    processImage(file);
                }
            });

            // Process image with ID scanner service
            async function processImage(file) {
                const formData = new FormData();
                formData.append('image', file);

                try {
                    const response = await fetch('{{ config('services.id_scanner.url') }}/scan', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData,
                        mode: 'cors',
                        credentials: 'omit'
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({}));
                        throw new Error(errorData.error || `Failed to process image: ${response.status} ${response.statusText}`);
                    }

                    const data = await response.json();

                    // Update form fields
                    document.getElementById('name').value = data.name || '';
                    document.getElementById('date_of_birth').value = data.dob || '';
                    document.getElementById('id_card_number').value = data.id_number || '';
                    document.getElementById('address').value = data.address || '';

                    // Update extracted info display
                    document.getElementById('extractedName').value = data.name || '';
                    document.getElementById('extractedDOB').value = data.dob || '';
                    document.getElementById('extractedIDNumber').value = data.id_number || '';
                    document.getElementById('extractedAddress').value = data.address || '';

                    extractedInfo.classList.remove('d-none');
                } catch (error) {
                    console.error('Error processing image:', error);
                    alert(error.message || 'Failed to process ID card image. Please try again or enter information manually.');
                }
            }

            // Initialize webcam on page load
            initWebcam();
        </script>
    @endpush
</x-app-layout>
