<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory::index') }}">Inventories</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Inventory</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Inventory Transfer</h1>
            <p class="lead">
                A page for Inventory Transfer
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('inventory-transfer.page', ['table_id' => $id])
        </div>
    </div>
    @push('scripts')
        @include('components.select.branchSelect')
        @include('components.select.inventoryProductSelect')
    @endpush
</x-app-layout>
