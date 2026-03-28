<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('lpo::index') }}">Local Purchase Orders</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Local Purchase Order</li>
                </ol>
            </nav>
            <h1 class="mt-2 mb-0 page-title">Edit Local Purchase Order #{{ $localPurchaseOrder->id }}</h1>
            <p class="lead">
                Edit local purchase order details.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('local-purchase-order.page', ['order_id' => $localPurchaseOrder->id])
        </div>
    </div>
</x-app-layout>
