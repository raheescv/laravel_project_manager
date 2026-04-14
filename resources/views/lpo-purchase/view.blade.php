<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('lpo-purchase::index') }}">LPO Purchases</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View - {{ $purchase->invoice_no }}</li>
                </ol>
            </nav>
            <h1 class="mt-2 mb-0 page-title">View LPO Purchase</h1>
            <p class="lead">
                Review purchase details and approval status.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('lpo-purchase.view', ['purchase_id' => $purchase->id])
        </div>
    </div>
</x-app-layout>
