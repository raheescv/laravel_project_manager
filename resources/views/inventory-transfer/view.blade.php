<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory::transfer::index') }}">Inventory Transfer</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Details</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Inventory Transfer Details</h1>
            <p class="lead">
                A page is an details of Inventory Transfer
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('inventory-transfer.view', ['table_id' => $id])
        </div>
    </div>
</x-app-layout>
