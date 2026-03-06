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
            <h1 class="page-title mb-0 mt-2">{{ $product?->name }} Inventory</h1>
            <p class="lead">
                A page is an details of {{ $product?->type }} : <b> <a href="{{ url($product?->type.'/edit/'.$product_id) }}">{{ $product?->name }}{{ $product?->name_arabic ? ' (' . $product?->name_arabic . ')' : '' }}</a> </b>
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('inventory.view', ['product_id' => $product_id])
        </div>
    </div>
    <x-inventory.inventory-modal />
    <x-employee-inventory.transfer-modal />
    @push('scripts')
        @include('components.select.branchSelect')
        @include('components.select.employeeSelect')
    @endpush
</x-app-layout>
