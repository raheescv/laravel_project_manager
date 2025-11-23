<div>
    <div class="container-fluid p-0">
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa fa-barcode me-2"></i>
                        Barcode Design Configuration
                    </h5>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-8">
                        <form wire:submit.prevent="save" class="needs-validation" novalidate>
                            <div class="card-body">
                                <div class="designer-toolbar mb-3" hidden>
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Grid</span>
                                                <input type="number" class="form-control" id="gridSize" value="20" min="5" max="50" style="width: 70px">
                                                <span class="input-group-text">px</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="barcode-designer" id="barcodeDesigner">
                                    @foreach ($barcode['elements'] as $elementId => $position)
                                        @if ($barcode[$elementId]['visible'] === false)
                                            @continue
                                        @endif
                                        <div class="design-element" id="{{ $elementId }}" data-element="{{ $elementId }}"
                                            style="top: {{ $position['top']??0 }}px; left: {{ $position['left']??0 }}px; width: {{ $position['width']??0 }}; height: {{ $position['height']??0 }};">
                                            <div class="resize-handle nw"></div>
                                            <div class="resize-handle ne"></div>
                                            <div class="resize-handle sw"></div>
                                            <div class="resize-handle se"></div>
                                            <div class="element-content">
                                                @switch($elementId)
                                                    @case('product_name')
                                                        <span>Product Name</span>
                                                    @break

                                                    @case('size')
                                                        <span>Size: M</span>
                                                    @break

                                                    @case('product_name_arabic')
                                                        <span style="direction: rtl;">اسم المنتج</span>
                                                    @break

                                                    @case('barcode')
                                                        @php
                                                            $barcodeType = $barcode['barcode']['type'] ?? 'C128';
                                                            $scale = $barcode['barcode']['scale'] ?? 1.5;
                                                        @endphp
                                                        <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG('SAMPLE', $barcodeType, $scale, 30) }}" alt="Sample Barcode">
                                                    @break

                                                    @case('price')
                                                        <span>QR 99.99</span>
                                                    @break

                                                    @case('price_arabic')
                                                        <span style="direction: rtl;">ق.ر ٩٩.٩٩ </span>
                                                    @break

                                                    @case('company_name')
                                                        <span>Your Company Name</span>
                                                    @break
                                                @endswitch
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="position-info alert alert-info mb-3" style="display: none;">
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
                            </div>
                            <!-- Main Dimensions with Visual Aid -->
                            <div class="card mb-4">
                                <div class="card-header py-2 bg-light">
                                    <h6 class="mb-0"><i class="fa fa-expand me-2"></i>Label Dimensions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Width (mm)</label>
                                                    <div class="input-group">
                                                        <input type="number" step="0.1" class="form-control" wire:model.live="barcode.width">
                                                        <span class="input-group-text">mm</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Height (mm)</label>
                                                    <div class="input-group">
                                                        <input type="number" step="0.1" class="form-control" wire:model.live="barcode.height">
                                                        <span class="input-group-text">mm</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-3 text-center bg-light">
                                                <div style="border: 2px dashed #ccc; width: 100%; height: 60px; display: flex; align-items: center; justify-content: center;">
                                                    <small class="text-muted">{{ $barcode['width'] ?? 0 }} × {{ $barcode['height'] ?? 0 }} mm</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Barcode Image Configuration -->
                            <div class="card mb-4">
                                <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="fa fa-barcode me-2"></i>Barcode Image Settings
                                    </h6>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="showBarcodeValue" wire:model.live="barcode.barcode.show_value">
                                        <label class="form-check-label" for="showBarcodeValue">Show Value</label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <!-- Appearance Settings -->
                                        <div class="col-12">
                                            <div class="border rounded p-3 bg-light bg-opacity-50">
                                                <h6 class="mb-3"><i class="fa fa-paint-brush me-2"></i>Appearance</h6>
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label">
                                                            <i class="fa fa-barcode me-1"></i>Barcode Type
                                                        </label>
                                                        <select class="form-select" wire:model.live="barcode.barcode.type"
                                                            data-bs-toggle="tooltip" title="Select the barcode type">
                                                            @foreach ($barcodeTypes as $type => $label)
                                                                <option value="{{ $type }}">{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">
                                                            <i class="fa fa-magnifying-glass me-1"></i>Scale Factor
                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fa fa-search-plus"></i></span>
                                                            <input type="number" step="0.1" min="1" max="5" class="form-control" wire:model.live="barcode.barcode.scale"
                                                                data-bs-toggle="tooltip" title="Scale multiplier for the barcode size">
                                                            <span class="input-group-text">x</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">
                                                            <i class="fa fa-text-height me-1"></i>Font Size
                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fa fa-font"></i></span>
                                                            <input type="number" step="1" min="8" max="24" class="form-control" wire:model.live="barcode.barcode.font_size"
                                                                data-bs-toggle="tooltip" title="Font size for barcode text">
                                                            <span class="input-group-text">px</span>
                                                        </div>
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
                                                                <input type="number" class="form-control" wire:model.live="barcode.elements.barcode.top">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-light">Left</span>
                                                                <input type="number" class="form-control" wire:model.live="barcode.elements.barcode.left">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-light">W</span>
                                                                <input type="number" class="form-control" wire:model.live="barcode.elements.barcode.width">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-light">H</span>
                                                                <input type="number" class="form-control" wire:model.live="barcode.elements.barcode.height">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Elements with Visual Preview -->
                            @php
                                $data = [
                                    'Product Name' => ['product_name', 'fa-font'],
                                    'Arabic Name' => ['product_name_arabic', 'fa-font'],
                                    'Company Name' => ['company_name', 'fa-building'],
                                    'Size' => ['size', 'fa-maximize'],
                                    'Price (English)' => ['price', 'fa-tag'],
                                    'Price (Arabic)' => ['price_arabic', 'fa-tag'],
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
                                                <input class="form-check-input" type="checkbox" wire:model.live="barcode.{{ $element }}.visible">
                                                <label class="form-check-label">Visible</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <!-- Style Controls -->
                                            <div class="col-md-12">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label">
                                                            <i class="fa fa-text-height me-1"></i>
                                                            Font Size
                                                        </label>
                                                        <div class="input-group">
                                                            <input type="number" class="form-control" wire:model.live="barcode.{{ $element }}.font_size">
                                                            <span class="input-group-text">px</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">
                                                            <i class="fa fa-align-left me-1"></i>
                                                            Alignment
                                                        </label>
                                                        <select class="form-select" wire:model.live="barcode.{{ $element }}.align">
                                                            <option value="left">Left</option>
                                                            <option value="center">Center</option>
                                                            <option value="right">Right</option>
                                                        </select>
                                                    </div>
                                                    @if (in_array($element, ['product_name', 'product_name_arabic', 'company_name']))
                                                        <div class="col-md-4">
                                                            <label class="form-label">
                                                                <i class="fa fa-text-width me-1"></i>
                                                                Character Limit
                                                            </label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fa fa-font"></i></span>
                                                                <input type="number" class="form-control" wire:model.live="barcode.{{ $element }}.char_limit" min="10" max="100"
                                                                    step="1" data-bs-toggle="tooltip" title="Maximum number of characters to display">
                                                                <span class="input-group-text">chars</span>
                                                            </div>
                                                        </div>
                                                    @endif
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
                                                                <input type="number" class="form-control" wire:model.live="barcode.elements.{{ $element }}.top">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-light">Left</span>
                                                                <input type="number" class="form-control" wire:model.live="barcode.elements.{{ $element }}.left">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-light">W</span>
                                                                <input type="number" class="form-control" wire:model.live="barcode.elements.{{ $element }}.width">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-light">H</span>
                                                                <input type="number" class="form-control" wire:model.live="barcode.elements.{{ $element }}.height">
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
                    <div class="col-lg-4">
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
                                    <button type="button" class="btn btn-outline-primary" onclick="window.open('{{ route('inventory::barcode::print') }}', '_blank')">
                                        <i class="fa fa-print me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="position-relative">
                                    <iframe src="{{ route('inventory::barcode::view') }}" id="barcodeSample" class="w-100 border-0" style="height: 130px"></iframe>
                                    <div class="position-absolute top-0 start-0 w-100 h-100"
                                        style="background-image: linear-gradient(rgba(0,0,0,.1) 1px, transparent 1px), linear-gradient(90deg, rgba(0,0,0,.1) 1px, transparent 1px); background-size: 20px 20px;">
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
                    document.getElementById('barcodeSample').contentWindow.location.reload();
                });

                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
        </script>
        <script>
            document.addEventListener('livewire:initialized', () => {
                const designer = document.getElementById('barcodeDesigner');
                const elements = document.querySelectorAll('.design-element');
                const gridSizeInput = document.getElementById('gridSize');
                const positionInfo = document.querySelector('.position-info');
                const toggleGridBtn = document.getElementById('toggleGrid');
                const toggleSnapBtn = document.getElementById('toggleSnap');

                let gridSize = parseInt(gridSizeInput.value);
                let snapToGrid = false;
                let selectedElements = new Set();

                // Create smart guides
                const guides = {
                    horizontal: document.createElement('div'),
                    vertical: document.createElement('div'),
                    centerH: document.createElement('div'),
                    centerV: document.createElement('div')
                };

                Object.values(guides).forEach(guide => {
                    guide.className = 'smart-guide';
                    guide.style.display = 'none';
                    designer.appendChild(guide);
                });

                guides.horizontal.classList.add('horizontal');
                guides.vertical.classList.add('vertical');
                guides.centerH.classList.add('horizontal', 'center');
                guides.centerV.classList.add('vertical', 'center');

                // Initialize draggable elements
                elements.forEach(element => {
                    $(element).draggable({
                        containment: "parent",
                        grid: snapToGrid ? [gridSize, gridSize] : null,
                        snap: '.design-element',
                        snapTolerance: 5,
                        snapMode: 'outer',
                        start: function(event, ui) {
                            element.classList.add('dragging');
                            showPositionInfo(element);
                        },
                        drag: function(event, ui) {
                            updatePositionInfo(element);
                            showSmartGuides(element, ui.position);

                            // Show distances to nearby elements
                            const distances = getNearbyElementDistances(element);
                            showDistanceIndicators(distances);
                        },
                        stop: function(event, ui) {
                            element.classList.remove('dragging');
                            updateElementPosition(element);
                            hidePositionInfo();
                            hideSmartGuides();
                            hideDistanceIndicators();
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
                        grid: snapToGrid ? [gridSize, gridSize] : null,
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

                // Toggle grid
                toggleGridBtn.addEventListener('click', () => {
                    designer.classList.toggle('show-grid');
                    toggleGridBtn.classList.toggle('active');
                });

                // Toggle snap to grid
                toggleSnapBtn.addEventListener('click', () => {
                    snapToGrid = !snapToGrid;
                    toggleSnapBtn.classList.toggle('active');
                    elements.forEach(element => {
                        $(element).draggable('option', 'grid', snapToGrid ? [gridSize, gridSize] : null);
                        $(element).resizable('option', 'grid', snapToGrid ? [gridSize, gridSize] : null);
                    });
                });

                // Multi-select with Ctrl/Cmd key
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

                // Position info display
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

                // Distribution functions
                document.querySelector('[data-distribute="horizontal"]').addEventListener('click', () => {
                    if (selectedElements.size < 3) return;

                    const elements = Array.from(selectedElements);
                    elements.sort((a, b) => a.offsetLeft - b.offsetLeft);

                    const first = elements[0];
                    const last = elements[elements.length - 1];
                    const totalSpace = last.offsetLeft - first.offsetLeft;
                    const spacing = totalSpace / (elements.length - 1);

                    elements.slice(1, -1).forEach((element, index) => {
                        element.style.left = (first.offsetLeft + spacing * (index + 1)) + 'px';
                        updateElementPosition(element);
                    });
                });

                document.querySelector('[data-distribute="vertical"]').addEventListener('click', () => {
                    if (selectedElements.size < 3) return;

                    const elements = Array.from(selectedElements);
                    elements.sort((a, b) => a.offsetTop - b.offsetTop);

                    const first = elements[0];
                    const last = elements[elements.length - 1];
                    const totalSpace = last.offsetTop - first.offsetTop;
                    const spacing = totalSpace / (elements.length - 1);

                    elements.slice(1, -1).forEach((element, index) => {
                        element.style.top = (first.offsetTop + spacing * (index + 1)) + 'px';
                        updateElementPosition(element);
                    });
                });

                // Update grid size
                gridSizeInput.addEventListener('input', () => {
                    const newSize = parseInt(gridSizeInput.value);
                    if (newSize >= 5 && newSize <= 50) {
                        gridSize = newSize;
                        designer.style.backgroundSize = `${gridSize}px ${gridSize}px`;
                        if (snapToGrid) {
                            elements.forEach(element => {
                                $(element).draggable('option', 'grid', [gridSize, gridSize]);
                                $(element).resizable('option', 'grid', [gridSize, gridSize]);
                            });
                        }
                    }
                });

                // Keyboard shortcuts
                document.addEventListener('keydown', (e) => {
                    if (selectedElements.size === 0) return;

                    const step = e.shiftKey ? 10 : 1;
                    let handled = false;

                    switch (e.key) {
                        case 'ArrowLeft':
                            selectedElements.forEach(element => {
                                element.style.left = (element.offsetLeft - step) + 'px';
                            });
                            handled = true;
                            break;
                        case 'ArrowRight':
                            selectedElements.forEach(element => {
                                element.style.left = (element.offsetLeft + step) + 'px';
                            });
                            handled = true;
                            break;
                        case 'ArrowUp':
                            selectedElements.forEach(element => {
                                element.style.top = (element.offsetTop - step) + 'px';
                            });
                            handled = true;
                            break;
                        case 'ArrowDown':
                            selectedElements.forEach(element => {
                                element.style.top = (element.offsetTop + step) + 'px';
                            });
                            handled = true;
                            break;
                    }

                    if (handled) {
                        e.preventDefault();
                        selectedElements.forEach(updateElementPosition);
                        if (selectedElements.size === 1) {
                            updatePositionInfo(Array.from(selectedElements)[0]);
                        }
                    }
                });

                // Update Livewire component with new position
                function updateElementPosition(element) {
                    const position = {
                        top: element.style.top,
                        left: element.style.left,
                        width: element.style.width,
                        height: element.style.height
                    };
                    @this.updateElementPosition(element.dataset.element, position);
                }

                function showSmartGuides(element, position) {
                    const rect = element.getBoundingClientRect();
                    const designerRect = designer.getBoundingClientRect();
                    const elementCenter = {
                        x: position.left + rect.width / 2,
                        y: position.top + rect.height / 2
                    };

                    // Get center of designer
                    const designerCenter = {
                        x: designerRect.width / 2,
                        y: designerRect.height / 2
                    };

                    // Check for center alignment
                    if (Math.abs(elementCenter.x - designerCenter.x) < 5) {
                        guides.centerV.style.left = `${designerCenter.x}px`;
                        guides.centerV.style.display = 'block';
                    } else {
                        guides.centerV.style.display = 'none';
                    }

                    if (Math.abs(elementCenter.y - designerCenter.y) < 5) {
                        guides.centerH.style.top = `${designerCenter.y}px`;
                        guides.centerH.style.display = 'block';
                    } else {
                        guides.centerH.style.display = 'none';
                    }

                    // Check alignment with other elements
                    elements.forEach(other => {
                        if (other === element || other.classList.contains('dragging')) return;

                        const otherRect = other.getBoundingClientRect();
                        const otherCenter = {
                            x: otherRect.left - designerRect.left + otherRect.width / 2,
                            y: otherRect.top - designerRect.top + otherRect.height / 2
                        };

                        // Vertical alignment
                        if (Math.abs(position.left - (otherRect.left - designerRect.left)) < 5) {
                            guides.vertical.style.left = `${otherRect.left - designerRect.left}px`;
                            guides.vertical.style.display = 'block';
                        } else if (Math.abs(position.left + rect.width - (otherRect.right - designerRect.left)) < 5) {
                            guides.vertical.style.left = `${otherRect.right - designerRect.left}px`;
                            guides.vertical.style.display = 'block';
                        } else {
                            guides.vertical.style.display = 'none';
                        }

                        // Horizontal alignment
                        if (Math.abs(position.top - (otherRect.top - designerRect.top)) < 5) {
                            guides.horizontal.style.top = `${otherRect.top - designerRect.top}px`;
                            guides.horizontal.style.display = 'block';
                        } else if (Math.abs(position.top + rect.height - (otherRect.bottom - designerRect.top)) < 5) {
                            guides.horizontal.style.top = `${otherRect.bottom - designerRect.top}px`;
                            guides.horizontal.style.display = 'block';
                        } else {
                            guides.horizontal.style.display = 'none';
                        }
                    });
                }

                function hideSmartGuides() {
                    Object.values(guides).forEach(guide => {
                        guide.style.display = 'none';
                    });
                }

                function getNearbyElementDistances(element) {
                    const distances = [];
                    const rect = element.getBoundingClientRect();
                    const designerRect = designer.getBoundingClientRect();

                    elements.forEach(other => {
                        if (other === element || other.classList.contains('dragging')) return;

                        const otherRect = other.getBoundingClientRect();

                        // Calculate horizontal distance
                        if (Math.abs(rect.top - otherRect.top) < 50) {
                            distances.push({
                                type: 'horizontal',
                                distance: Math.abs(rect.left - otherRect.left),
                                position: {
                                    top: rect.top - designerRect.top,
                                    left: Math.min(rect.left, otherRect.left) - designerRect.left + Math.abs(rect.left - otherRect.left) / 2
                                }
                            });
                        }

                        // Calculate vertical distance
                        if (Math.abs(rect.left - otherRect.left) < 50) {
                            distances.push({
                                type: 'vertical',
                                distance: Math.abs(rect.top - otherRect.top),
                                position: {
                                    top: Math.min(rect.top, otherRect.top) - designerRect.top + Math.abs(rect.top - otherRect.top) / 2,
                                    left: rect.left - designerRect.left
                                }
                            });
                        }
                    });

                    return distances;
                }

                function showDistanceIndicators(distances) {
                    // Remove existing indicators
                    document.querySelectorAll('.distance-indicator').forEach(el => el.remove());

                    distances.forEach(d => {
                        const indicator = document.createElement('div');
                        indicator.className = 'distance-indicator';
                        indicator.textContent = `${Math.round(d.distance)}px`;
                        indicator.style.top = `${d.position.top}px`;
                        indicator.style.left = `${d.position.left}px`;

                        if (d.type === 'vertical') {
                            indicator.style.transform = 'translate(-50%, -50%) rotate(-90deg)';
                        } else {
                            indicator.style.transform = 'translate(-50%, -50%)';
                        }

                        designer.appendChild(indicator);
                    });
                }

                function hideDistanceIndicators() {
                    document.querySelectorAll('.distance-indicator').forEach(el => el.remove());
                }

                // Update element style controls when an element is selected
                function updateElementControls(element) {
                    const elementStyleControls = document.getElementById('elementStyleControls');
                    const noElementSelected = document.getElementById('noElementSelected');

                    if (element) {
                        elementStyleControls.style.display = 'block';
                        noElementSelected.style.display = 'none';

                        // Update the controls with current element settings
                        const elementId = element.dataset.element;
                        document.querySelector('.element-name').textContent = elementId.replace(/_/g, ' ').toUpperCase();

                        // Update font size input
                        const fontSize = parseInt(window.getComputedStyle(element).fontSize);
                        document.getElementById('elementFontSize').value = fontSize;

                        // Update alignment select
                        const textAlign = window.getComputedStyle(element).textAlign;
                        document.getElementById('elementAlignment').value = textAlign;
                    } else {
                        elementStyleControls.style.display = 'none';
                        noElementSelected.style.display = 'block';
                    }
                }

                // Add event listeners for element style controls
                document.getElementById('elementFontSize').addEventListener('change', function(e) {
                    const selectedElement = document.querySelector('.design-element.selected');
                    if (selectedElement) {
                        selectedElement.style.fontSize = e.target.value + 'px';
                        updateElementPosition(selectedElement);
                    }
                });

                document.getElementById('elementAlignment').addEventListener('change', function(e) {
                    const selectedElement = document.querySelector('.design-element.selected');
                    if (selectedElement) {
                        selectedElement.style.textAlign = e.target.value;
                        updateElementPosition(selectedElement);
                    }
                });

                // Add to existing click handler for elements
                elements.forEach(element => {
                    element.addEventListener('mousedown', (e) => {
                        if (!e.ctrlKey && !e.metaKey) {
                            selectedElements.clear();
                            elements.forEach(el => el.classList.remove('selected'));
                        }
                        element.classList.add('selected');
                        selectedElements.add(element);
                        showPositionInfo(element);
                        updateElementControls(element);
                        e.stopPropagation();
                    });
                });

                // Click outside to deselect - update to clear controls
                designer.addEventListener('mousedown', (e) => {
                    if (e.target === designer) {
                        selectedElements.clear();
                        elements.forEach(el => el.classList.remove('selected'));
                        hidePositionInfo();
                        updateElementControls(null);
                    }
                });
            });
        </script>
    @endpush
    @push('styles')
        <style>
            .barcode-designer {
                background: #fff;
                border: 2px solid #ddd;
                position: relative;
                width: 100%;
                aspect-ratio: 50 / 10;
                background-size: 20px 20px;
                background-image:
                    linear-gradient(to right, rgba(13, 110, 253, 0.05) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(13, 110, 253, 0.05) 1px, transparent 1px);
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
                border-radius: 4px;
                transition: background-size 0.2s ease;
            }
        </style>
    @endpush
</div>
