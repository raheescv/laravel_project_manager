<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('product::index') }}">Product</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Import</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Product Import</h1>
            <p class="lead">
                Easily import products from Excel or CSV files with custom column mapping and preview.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('product.import')
        </div>
    </div>
</x-app-layout>
