<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Local Purchase Orders</li>
                </ol>
            </nav>
            <h1 class="mt-2 mb-0 page-title">Local Purchase Orders</h1>
            <p class="lead">
                A table is an arrangement of LocalPurchase Orders
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="mb-3 card">
                @livewire('local-purchase-order.table')
            </div>
        </div>
    </div>
     @push('scripts')
        @include('components.select.vendorSelect')
        @include('components.select.branchSelect')
    @endpush
</x-app-layout>
