<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create Local Purchase Order</li>
                </ol>
            </nav>
            <h1 class="mt-2 mb-0 page-title">Create Local Purchase Order</h1>
            <p class="lead">
                Create a new local purchase order.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('local-purchase-order.page')
        </div>
    </div>
</x-app-layout>
