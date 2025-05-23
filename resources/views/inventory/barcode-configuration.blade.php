<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory::index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active" aria-current="page"> Barcode Configuration</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Inventory Barcode Configuration</h1>
            <p class="lead">
                A table is an arrangement of Inventory Barcode Configuration
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('inventory.barcode-configuration')
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
        <style>
            .barcode-designer:hover {
                border-color: #0d6efd;
                box-shadow: 0 0 20px rgba(13, 110, 253, 0.15);
            }

            .barcode-designer.dragover {
                background-color: rgba(13, 110, 253, 0.05);
                border: 2px dashed #0d6efd;
            }

            .designer-toolbar {
                margin-bottom: 1rem;
                padding: 1rem;
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 0.5rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            .designer-toolbar .btn-group {
                margin-right: 0.5rem;
            }

            .designer-toolbar .btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
                transition: all 0.2s ease;
            }

            .designer-toolbar .btn.active {
                background-color: #0d6efd;
                color: white;
                box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
            }

            /* Smart Guides */
            .smart-guide {
                position: absolute;
                background: rgba(13, 110, 253, 0.5);
                pointer-events: none;
                z-index: 1000;
            }

            .smart-guide.horizontal {
                height: 1px;
                left: 0;
                right: 0;
            }

            .smart-guide.vertical {
                width: 1px;
                top: 0;
                bottom: 0;
            }

            .smart-guide.center {
                background: rgba(220, 53, 69, 0.5);
            }

            /* Distance indicator */
            .distance-indicator {
                position: absolute;
                background: rgba(13, 110, 253, 0.1);
                border: 1px solid rgba(13, 110, 253, 0.5);
                border-radius: 2px;
                padding: 2px 4px;
                font-size: 10px;
                color: #0d6efd;
                pointer-events: none;
                z-index: 1001;
                white-space: nowrap;
            }

            border-color: #0d6efd;
            }

            .btn-outline-primary:hover {
                transform: translateY(-1px);
            }

            .position-info {
                padding: 0.75rem;
                font-size: 0.875rem;
                border-left: 4px solid #0d6efd;
                background: rgba(13, 110, 253, 0.05);
                animation: slideIn 0.2s ease-out;
            }

            .position-info .coordinates span {
                display: inline-flex;
                align-items: center;
                background: rgba(13, 110, 253, 0.1);
                padding: 2px 8px;
                border-radius: 4px;
                font-family: monospace;
                font-size: 0.8125rem;
            }

            .position-info .element-name {
                opacity: 0.7;
                font-size: 0.8125rem;
            }

            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .element-controls {
                position: absolute;
                top: -30px;
                left: 50%;
                transform: translateX(-50%);
                background: white;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                padding: 4px;
                display: none;
                z-index: 1000;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .design-element:hover .element-controls {
                display: flex;
            }

            .design-element {
                position: absolute;
                background: rgba(255, 255, 255, 0.95);
                border: 1px dashed rgba(153, 153, 153, 0.5);
                cursor: move;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                transition: all 0.2s ease-in-out;
                min-width: 40px;
                min-height: 30px;
                padding: 8px;
                border-radius: 4px;
            }

            .design-element:hover {
                border: 1px dashed #0d6efd;
                background: rgba(255, 255, 255, 1);
                box-shadow: 0 2px 8px rgba(13, 110, 253, 0.15);
                transform: translateY(-1px);
            }

            .design-element.selected {
                border: 2px solid #0d6efd;
                background: #fff;
                z-index: 100;
                box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
            }

            .design-element.dragging {
                opacity: 0.9;
                transform: scale(1.02);
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            }

            .resize-handle {
                width: 8px;
                height: 8px;
                background-color: #fff;
                border: 1.5px solid #0d6efd;
                position: absolute;
                border-radius: 50%;
                opacity: 0;
                transition: all 0.15s ease-in-out;
            }

            .design-element:hover .resize-handle,
            .design-element.selected .resize-handle {
                opacity: 1;
            }

            .resize-handle:hover {
                background-color: #0d6efd;
                transform: scale(1.2);
            }

            .resize-handle.nw {
                top: -4px;
                left: -4px;
                cursor: nw-resize;
            }

            .resize-handle.ne {
                top: -4px;
                right: -4px;
                cursor: ne-resize;
            }

            .resize-handle.sw {
                bottom: -4px;
                left: -4px;
                cursor: sw-resize;
            }

            .resize-handle.se {
                bottom: -4px;
                right: -4px;
                cursor: se-resize;
            }

            .design-element.dragging .resize-handle {
                opacity: 0;
            }

            /* Configuration Panel Styles */
            .config-panel {
                background: #fff;
                border-left: 1px solid #dee2e6;
                height: 100%;
                padding: 1.5rem;
                overflow-y: auto;
            }

            .config-panel .nav-pills .nav-link {
                color: #6c757d;
                padding: 0.5rem 1rem;
                border-radius: 0.5rem;
                transition: all 0.2s ease;
            }

            .config-panel .nav-pills .nav-link.active {
                background-color: #0d6efd;
                color: white;
            }

            .config-panel .tab-content {
                padding-top: 1.5rem;
            }

            .config-section {
                margin-bottom: 1.5rem;
                padding: 1rem;
                background: #f8f9fa;
                border-radius: 0.5rem;
                border: 1px solid #dee2e6;
            }

            .config-section h6 {
                color: #495057;
                margin-bottom: 1rem;
                padding-bottom: 0.5rem;
                border-bottom: 1px solid #dee2e6;
            }

            .designer-container {
                display: flex;
                gap: 2rem;
                min-height: calc(100vh - 280px);
            }

            .designer-main {
                flex: 1;
                display: flex;
                flex-direction: column;
            }

            .designer-sidebar {
                width: 400px;
                flex-shrink: 0;
            }

            .preview-container {
                margin-top: 1rem;
                padding: 1rem;
                background: #f8f9fa;
                border-radius: 0.5rem;
                border: 1px solid #dee2e6;
            }

            .preview-container iframe {
                width: 100%;
                height: 200px;
                border: none;
                border-radius: 0.25rem;
            }

            /* Element Style Controls */
            .element-style-controls {
                padding: 1rem;
                background: white;
                border-radius: 0.5rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            .element-style-controls .form-group {
                margin-bottom: 1rem;
            }

            .element-style-controls label {
                font-size: 0.875rem;
                color: #495057;
                margin-bottom: 0.5rem;
            }

            .input-group-sm .form-control,
            .input-group-sm .input-group-text {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }

            .workspace-container {
                display: flex;
                gap: 1.5rem;
                min-height: calc(100vh - 100px);
                padding: 1rem;
            }

            .workspace-main {
                flex: 1;
                min-width: 0;
                display: flex;
                flex-direction: column;
            }

            .workspace-sidebar {
                width: 400px;
                flex-shrink: 0;
                position: sticky;
                top: 1rem;
                height: calc(100vh - 2rem);
                overflow-y: auto;
                padding-right: 0.5rem;
            }

            .preview-section {
                background: white;
                border: 1px solid #dee2e6;
                border-radius: 0.5rem;
                overflow: hidden;
                margin-bottom: 1rem;
            }

            .preview-section .preview-header {
                padding: 1rem;
                border-bottom: 1px solid #dee2e6;
                background: #f8f9fa;
            }

            .preview-section .preview-content {
                padding: 1rem;
            }

            .preview-frame {
                width: 100%;
                height: 400px;
                border: 1px solid #dee2e6;
                border-radius: 0.25rem;
                background: white;
            }

            .configuration-tabs {
                margin-top: 1rem;
            }

            .configuration-tabs .nav-link {
                padding: 0.75rem 1rem;
                color: #6c757d;
                font-weight: 500;
            }

            .configuration-tabs .nav-link.active {
                color: #0d6efd;
                background: #fff;
                border-bottom-color: #fff;
            }

            .configuration-content {
                background: white;
                border: 1px solid #dee2e6;
                border-top: 0;
                border-radius: 0 0 0.5rem 0.5rem;
                padding: 1.5rem;
            }

            .settings-section {
                margin-bottom: 1.5rem;
            }

            .settings-section:last-child {
                margin-bottom: 0;
            }

            .settings-section-title {
                font-size: 0.875rem;
                font-weight: 600;
                color: #495057;
                margin-bottom: 1rem;
                padding-bottom: 0.5rem;
                border-bottom: 1px solid #dee2e6;
            }

            .page-layout {
                display: flex;
                gap: 1.5rem;
                padding: 1rem;
            }

            .main-content {
                flex: 1;
                min-width: 0;
            }

            .preview-sidebar {
                width: 400px;
                position: sticky;
                top: 1rem;
                align-self: flex-start;
                background: white;
                border-radius: 0.5rem;
                border: 1px solid #dee2e6;
                overflow: hidden;
            }

            .preview-header {
                padding: 1rem;
                background: #f8f9fa;
                border-bottom: 1px solid #dee2e6;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .preview-content {
                padding: 1rem;
            }

            .preview-frame {
                width: 100%;
                height: 400px;
                border: 1px solid #dee2e6;
                border-radius: 0.25rem;
                background: white;
            }

            .barcode-settings {
                margin-top: 1.5rem;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    @endpush
</x-app-layout>
