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
                    <li class="breadcrumb-item active" aria-current="page">Opening Balance</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Opening Balance</h1>
            <p class="lead">
                Set opening balance for inventory items
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3 p-1">
                <div id="opening-balance-form"></div>
            </div>
        </div>
    </div>
    @push('scripts')
        @vite('resources/js/inventory-opening-balance.js')
    @endpush
</x-app-layout>
