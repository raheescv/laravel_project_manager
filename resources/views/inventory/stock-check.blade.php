<x-app-layout>
    @push('head')
        <meta name="branch-id" content="{{ session('branch_id') }}">
    @endpush
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory::index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Stock Check</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Stock Check</h1>
            <p class="lead">
                Create and manage stock checks for inventory verification
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3 p-1">
                <div id="stock-check-list" data-branch-id="{{ $branch_id }}"></div>
            </div>
        </div>
    </div>
    @push('scripts')
        @vite('resources/js/stock-check.js')
    @endpush
</x-app-layout>
