<x-app-layout>
    @push('head')
        <meta name="branch-id" content="{{ session('branch_id') }}">
        <meta name="stock-check-id" content="{{ $stockCheck->id }}">
    @endpush
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory::index') }}">Inventory</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory::stock-check::index') }}">Stock Check</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $stockCheck->title }}</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">{{ $stockCheck->title }}</h1>
            <p class="lead">
                Stock check for {{ $stockCheck->branch->name ?? 'Branch' }} - {{ systemDate($stockCheck->date) }}
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3 p-1">
                <div id="stock-check-show"></div>
            </div>
        </div>
    </div>
    @push('scripts')
        @vite('resources/js/stock-check-show.js')
    @endpush
</x-app-layout>
