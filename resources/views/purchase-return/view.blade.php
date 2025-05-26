<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item" aria-current="page">Purchase</li>
                    <li class="breadcrumb-item active" aria-current="page">Purchase Returns</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Purchase Returns</h1>
            <p class="lead">
                View and manage returned purchases, track refunds, and handle supplier claims
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('purchase-return.view', ['id' => $id])
            </div>
        </div>
    </div>
</x-app-layout>
