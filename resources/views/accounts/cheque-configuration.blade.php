<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('account::index') }}">Accounts</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cheque Configuration</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Cheque Print Configuration</h1>
            <p class="lead">
                Configure cheque print layout and design
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('accounts.cheque-configuration')
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
        <style>
            .cheque-designer {
                background: #fff;
                border: 2px solid #ddd;
                position: relative;
                width: 100%;
                aspect-ratio: 210 / 100;
                background-size: 20px 20px;
                background-image:
                    linear-gradient(to right, rgba(13, 110, 253, 0.05) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(13, 110, 253, 0.05) 1px, transparent 1px);
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
                border-radius: 4px;
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
            }

            .design-element.selected {
                border: 2px solid #0d6efd;
                background: #fff;
                z-index: 100;
                box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
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
        </style>
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    @endpush
</x-app-layout>

