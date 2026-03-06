<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory::index') }}">Inventory</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory::barcode::configuration') }}">Barcode Templates</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Barcode Configuration</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Inventory Barcode Configuration</h1>
            <p class="lead">Edit the barcode sticker design using your existing template keys and print settings.</p>
        </div>
    </div>

    <div class="content__boxed">
        <div class="content__wrap">
            <div
                id="barcode-template-designer"
                data-template-key="{{ $templateKey }}"
                data-list-url="{{ route('inventory::barcode::configuration') }}"
                data-data-url="{{ route('inventory::barcode::configuration.data', $templateKey) }}"
                data-save-url="{{ route('inventory::barcode::configuration.save', $templateKey) }}"
                data-reset-url="{{ route('inventory::barcode::configuration.reset', $templateKey) }}"
                data-csrf="{{ csrf_token() }}"
            ></div>
        </div>
    </div>

    @vite('resources/js/barcode-template-config.js')
</x-app-layout>
