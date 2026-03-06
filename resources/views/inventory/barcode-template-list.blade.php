<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory::index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Barcode Templates</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Barcode Design Templates</h1>
            <p class="lead">Manage barcode label templates and choose which one is used by default for printing.</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('inventory.barcode-template-list')
        </div>
    </div>
</x-app-layout>
