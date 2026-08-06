<x-app-layout>
    <x-barcode.premium />

    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory::index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Barcode Print</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Barcode Print Cart</h1>
            <p class="lead">Build a batch of labels, then print them all in one pass.</p>
        </div>
    </div>

    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('inventory.barcode.cart-page')
        </div>
    </div>
</x-app-layout>
