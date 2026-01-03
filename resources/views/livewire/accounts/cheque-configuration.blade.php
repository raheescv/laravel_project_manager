<div>
    <div class="container-fluid p-0">
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa fa-money-check me-2"></i>
                        Cheque Design Configuration
                    </h5>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-5">
                        <form wire:submit.prevent="save" class="needs-validation" novalidate>
                            <!-- Main Dimensions -->
                            <div class="card mb-4">
                                <div class="card-header py-2 bg-light">
                                    <h6 class="mb-0"><i class="fa fa-expand me-2"></i>Cheque Dimensions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Width (mm)</label>
                                                    <div class="input-group">
                                                        <input type="number" step="0.1" class="form-control" wire:model.live="cheque.width">
                                                        <span class="input-group-text">mm</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Height (mm)</label>
                                                    <div class="input-group">
                                                        <input type="number" step="0.1" class="form-control" wire:model.live="cheque.height">
                                                        <span class="input-group-text">mm</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-3 text-center bg-light">
                                                <div style="border: 2px dashed #ccc; width: 100%; height: 60px; display: flex; align-items: center; justify-content: center;">
                                                    <small class="text-muted">{{ $cheque['width'] ?? 0 }} × {{ $cheque['height'] ?? 0 }} mm</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Template Option -->
                            <div class="card mb-4">
                                <div class="card-header py-2 bg-light">
                                    <h6 class="mb-0"><i class="fa fa-file me-2"></i>Template Settings</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="useTemplate" wire:model.live="cheque.use_template">
                                        <label class="form-check-label" for="useTemplate">Use Cheque Template (with borders and styling)</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Background Image Option -->
                            <div class="card mb-4">
                                <div class="card-header py-2 bg-light">
                                    <h6 class="mb-0"><i class="fa fa-image me-2"></i>Background Image</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Cheque Background Image</label>
                                        <p class="text-muted small mb-2">Upload a background image to help align elements. Supported formats: JPG, PNG, GIF (Max: 5MB)</p>
                                        <input type="file" class="form-control" wire:model="backgroundImage" accept="image/*">
                                        @error('backgroundImage')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                        @if ($this->backgroundImage && $this->backgroundImage->isProcessing())
                                            <div class="text-info small mt-1">
                                                <i class="fa fa-spinner fa-spin me-1"></i>Uploading...
                                            </div>
                                        @endif
                                    </div>
                                    @if (!empty($cheque['background_image']))
                                        <div class="mb-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="text-muted small">Current background image:</span>
                                                <a href="{{ asset('storage/' . $cheque['background_image']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fa fa-eye me-1"></i>View
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeBackgroundImage" onclick="return confirm('Are you sure you want to remove the background image?')">
                                                    <i class="fa fa-trash me-1"></i>Remove
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Elements Configuration -->
                            @php
                                $data = [
                                    'Date' => ['date', 'fa-calendar'],
                                    'Payee' => ['payee', 'fa-user'],
                                    'Amount in Words' => ['amount_in_words', 'fa-file-text'],
                                    'Amount in Numbers' => ['amount_in_numbers', 'fa-dollar-sign'],
                                    'Signature' => ['signature', 'fa-pen'],
                                    'Cheque Number' => ['cheque_number', 'fa-hashtag'],
                                    'Account Number' => ['account_number', 'fa-credit-card'],
                                    'Bank Name' => ['bank_name', 'fa-university'],
                                ];
                            @endphp
                            @foreach ($data as $label => [$element, $icon])
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center py-2 bg-light">
                                        <h6 class="mb-0">
                                            <i class="fa {{ $icon }} me-2"></i>
                                            {{ $label }}
                                        </h6>
                                        <div class="d-flex align-items-center">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" wire:model.live="cheque.{{ $element }}.visible">
                                                <label class="form-check-label">Visible</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label">
                                                            <i class="fa fa-text-height me-1"></i>
                                                            Font Size
                                                        </label>
                                                        <div class="input-group">
                                                            <input type="number" class="form-control" wire:model.live="cheque.{{ $element }}.font_size">
                                                            <span class="input-group-text">px</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">
                                                            <i class="fa fa-align-left me-1"></i>
                                                            Alignment
                                                        </label>
                                                        <select class="form-select" wire:model.live="cheque.{{ $element }}.align">
                                                            <option value="left">Left</option>
                                                            <option value="center">Center</option>
                                                            <option value="right">Right</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <!-- Position Controls -->
                                                <div class="mt-3">
                                                    <label class="form-label">
                                                        <i class="fa fa-arrows me-1"></i>
                                                        Position
                                                    </label>
                                                    <div class="row g-2">
                                                        <div class="col-md-3">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-light">Top</span>
                                                                <input type="number" class="form-control" wire:model.live="cheque.elements.{{ $element }}.top">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-light">Left</span>
                                                                <input type="number" class="form-control" wire:model.live="cheque.elements.{{ $element }}.left">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-light">W</span>
                                                                <input type="number" class="form-control" wire:model.live="cheque.elements.{{ $element }}.width">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-light">H</span>
                                                                <input type="number" class="form-control" wire:model.live="cheque.elements.{{ $element }}.height">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </form>
                    </div>

                    <!-- Live Preview -->
                    <div class="col-lg-7">
                        <div class="card sticky-top" style="top: 85px">
                            <div class="card-header d-flex justify-content-between align-items-center bg-light py-2">
                                <h6 class="mb-0">
                                    <i class="fa fa-eye me-2"></i>Live Preview
                                </h6>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-primary" wire:click="save">
                                        <i class="fa fa-save me-1"></i>Save
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" wire:click="resetToDefaults"
                                            onclick="return confirm('Are you sure you want to reset all settings to default? This action cannot be undone.')">
                                        <i class="fa fa-undo me-1"></i>Reset
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="window.open('{{ route('account::cheque::print') }}', '_blank')">
                                        <i class="fa fa-print me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="p-3">
                                    @php
                                        $bgStyle = !empty($cheque['background_image'])
                                            ? 'background-image: url(' . $cheque['background_image'] . '); background-size: contain; background-repeat: no-repeat; background-position: center;'
                                            : '';
                                    @endphp
                                    <div class="cheque-designer" id="chequeDesigner" style="{{ $bgStyle }}">
                                        @foreach ($cheque['elements'] as $elementId => $position)
                                            @if (($cheque[$elementId]['visible'] ?? true) === false)
                                                @continue
                                            @endif
                                            <div class="design-element" id="{{ $elementId }}" data-element="{{ $elementId }}"
                                                style="top: {{ $position['top']??0 }}px; left: {{ $position['left']??0 }}px; width: {{ $position['width']??0 }}px; height: {{ $position['height']??0 }}px;">
                                                <div class="resize-handle nw"></div>
                                                <div class="resize-handle ne"></div>
                                                <div class="resize-handle sw"></div>
                                                <div class="resize-handle se"></div>
                                                <div class="element-content">
                                                    @switch($elementId)
                                                        @case('date')
                                                            <span>02-Feb-2019</span>
                                                        @break

                                                        @case('payee')
                                                            <span>Pay against this cheque to Strana Management and Business Consultancy Service</span>
                                                        @break

                                                        @case('amount_in_words')
                                                            <span>One Million Five Hundred Thirty Two Thousand Three Hundred Eighty Four and Fifty Fils Only**</span>
                                                        @break

                                                        @case('amount_in_numbers')
                                                            <span>1,532,364.50</span>
                                                        @break

                                                        @case('signature')
                                                            <span>________________</span>
                                                        @break

                                                        @case('cheque_number')
                                                            <span>CHQ-000001</span>
                                                        @break

                                                        @case('account_number')
                                                            <span>5002-626450536-14516568</span>
                                                        @break

                                                        @case('bank_name')
                                                            <span>Bank Name</span>
                                                        @break
                                                    @endswitch
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="position-info alert alert-info mb-3 mx-3 mt-3" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="coordinates">
                                            <span class="me-3">X: <span id="elementX">0</span>px</span>
                                            <span class="me-3">Y: <span id="elementY">0</span>px</span>
                                            <span class="me-3">W: <span id="elementW">0</span>px</span>
                                            <span>H: <span id="elementH">0</span>px</span>
                                        </div>
                                        <div class="element-name fw-bold"></div>
                                    </div>
                                </div>
                                <div class="p-3 border-top">
                                    <div class="position-relative">
                                        <iframe src="{{ route('account::cheque::view') }}" id="chequeSample" class="w-100 border-0" style="height: 400px"></iframe>
                                        <div class="position-absolute top-0 start-0 w-100 h-100"
                                            style="background-image: linear-gradient(rgba(0,0,0,.1) 1px, transparent 1px), linear-gradient(90deg, rgba(0,0,0,.1) 1px, transparent 1px); background-size: 20px 20px; pointer-events: none;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
        <script>
            document.addEventListener('livewire:initialized', () => {
                window.addEventListener('reloadIframe', event => {
                    document.getElementById('chequeSample').contentWindow.location.reload();
                });

                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
        </script>
        <script>
            document.addEventListener('livewire:initialized', () => {
                const designer = document.getElementById('chequeDesigner');
                const elements = document.querySelectorAll('.design-element');
                const positionInfo = document.querySelector('.position-info');

                let selectedElements = new Set();

                // Initialize draggable elements
                elements.forEach(element => {
                    $(element).draggable({
                        containment: "parent",
                        snap: '.design-element',
                        snapTolerance: 5,
                        start: function(event, ui) {
                            element.classList.add('dragging');
                            showPositionInfo(element);
                        },
                        drag: function(event, ui) {
                            updatePositionInfo(element);
                        },
                        stop: function(event, ui) {
                            element.classList.remove('dragging');
                            updateElementPosition(element);
                            hidePositionInfo();
                        }
                    });

                    $(element).resizable({
                        handles: {
                            'nw': '.nw',
                            'ne': '.ne',
                            'sw': '.sw',
                            'se': '.se'
                        },
                        containment: "parent",
                        start: function(event, ui) {
                            showPositionInfo(element);
                        },
                        resize: function(event, ui) {
                            updatePositionInfo(element);
                        },
                        stop: function(event, ui) {
                            updateElementPosition(element);
                            hidePositionInfo();
                        }
                    });
                });

                // Select elements
                elements.forEach(element => {
                    element.addEventListener('mousedown', (e) => {
                        if (!e.ctrlKey && !e.metaKey) {
                            selectedElements.clear();
                            elements.forEach(el => el.classList.remove('selected'));
                        }
                        element.classList.add('selected');
                        selectedElements.add(element);
                        showPositionInfo(element);
                        e.stopPropagation();
                    });
                });

                // Click outside to deselect
                designer.addEventListener('mousedown', (e) => {
                    if (e.target === designer) {
                        selectedElements.clear();
                        elements.forEach(el => el.classList.remove('selected'));
                        hidePositionInfo();
                    }
                });

                function showPositionInfo(element) {
                    const rect = element.getBoundingClientRect();
                    const designerRect = designer.getBoundingClientRect();
                    const x = Math.round(rect.left - designerRect.left);
                    const y = Math.round(rect.top - designerRect.top);

                    document.getElementById('elementX').textContent = x;
                    document.getElementById('elementY').textContent = y;
                    document.getElementById('elementW').textContent = Math.round(rect.width);
                    document.getElementById('elementH').textContent = Math.round(rect.height);
                    document.querySelector('.element-name').textContent = element.dataset.element.replace(/_/g, ' ').toUpperCase();

                    positionInfo.style.display = 'block';
                }

                function updatePositionInfo(element) {
                    const rect = element.getBoundingClientRect();
                    const designerRect = designer.getBoundingClientRect();
                    const x = Math.round(rect.left - designerRect.left);
                    const y = Math.round(rect.top - designerRect.top);

                    document.getElementById('elementX').textContent = x;
                    document.getElementById('elementY').textContent = y;
                    document.getElementById('elementW').textContent = Math.round(rect.width);
                    document.getElementById('elementH').textContent = Math.round(rect.height);
                }

                function hidePositionInfo() {
                    if (selectedElements.size === 0) {
                        positionInfo.style.display = 'none';
                    }
                }

                function updateElementPosition(element) {
                    const position = {
                        top: element.style.top,
                        left: element.style.left,
                        width: element.style.width,
                        height: element.style.height
                    };
                    @this.updateElementPosition(element.dataset.element, position);
                }

                function updateElementSize(element) {
                    const size = {
                        width: element.style.width,
                        height: element.style.height
                    };
                    @this.updateElementSize(element.dataset.element, size);
                }
            });
        </script>
    @endpush
    @push('styles')
        <style>
            .cheque-designer {
                background: #fff;
                border: 2px solid #ddd;
                position: relative;
                width: 100%;
                aspect-ratio: 210 / 100;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
                border-radius: 4px;
                transition: background-size 0.2s ease;
                overflow: hidden;
            }

            .cheque-designer::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-size: 20px 20px;
                background-image:
                    linear-gradient(to right, rgba(13, 110, 253, 0.1) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(13, 110, 253, 0.1) 1px, transparent 1px);
                pointer-events: none;
                z-index: 1;
            }

            .cheque-designer .design-element {
                position: relative;
                z-index: 2;
            }
        </style>
    @endpush
</div>

