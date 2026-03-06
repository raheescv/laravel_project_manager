<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tailoring::order::index') }}">Tailoring</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Receipts</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Tailoring Receipts</h1>
            <p class="lead">
                View and collect payments for tailoring orders. Search by customer name or mobile, filter by branch and date.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('tailoring.receipts')
            </div>
        </div>
    </div>
    <x-tailoring.customer-receipt-modal />
    @push('scripts')
        @include('components.select.customerSelect')
        @include('components.select.branchSelect')
        @include('components.select.paymentMethodSelect')
    @endpush
</x-app-layout>
