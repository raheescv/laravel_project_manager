<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('lpo::index') }}">Local Purchase Orders</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Confirm Local Purchase Order -
                        {{ $localPurchaseOrder->id }}</li>
                </ol>
            </nav>
            <h1 class="mt-2 mb-0 page-title">Confirm Local Purchase Order</h1>
            <p class="lead">
                Confirm this approved local purchase order and provide a note if necessary.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('local-purchase-order.view', ['local_purchase_order_id' => $localPurchaseOrder->id, 'is_confirmable' => true])
        </div>
    </div>
</x-app-layout>
