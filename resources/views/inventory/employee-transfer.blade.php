<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory::index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Employee Transfer</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Employee Stock Transfer</h1>
            <p class="lead">
                Hand several products from branch stock to one employee in a single pass
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('employee-inventory.page')
        </div>
    </div>
</x-app-layout>
