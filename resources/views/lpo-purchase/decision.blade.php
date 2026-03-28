<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('lpo-purchase::index') }}">LPO Purchases</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Decide - {{ $purchase->invoice_no }}</li>
                </ol>
            </nav>
            <h1 class="mt-2 mb-0 page-title">Decide LPO Purchase</h1>
            <p class="lead">
                Accept or reject the LPO purchase and provide feedback if necessary.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('lpo-purchase.view', ['purchase_id' => $purchase->id, 'is_approvable' => true])
        </div>
    </div>
</x-app-layout>
