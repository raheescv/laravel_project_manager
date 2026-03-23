<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('purchase-request::index') }}">Purchase Requests</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Decide Purchase Request -
                        {{ $purchaseRequest->id }}</li>
                </ol>
            </nav>
            <h1 class="mt-2 mb-0 page-title">Decide Purchase Request</h1>
            <p class="lead">
                Approve or reject the purchase request and provide feedback if necessary.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('purchase-request.decide', ['purchase_request_id' => $purchaseRequest->id])
        </div>
    </div>
</x-app-layout>
