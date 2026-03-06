<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tailoring::order::index') }}">Tailoring</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Item Wise Report</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Tailoring Item Wise Report</h1>
            <p class="lead mb-0">Report for tailoring order line items with filters and export.</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('report.tailoring-order-item-report')
            </div>
        </div>
    </div>
    @push('scripts')
        @include('components.select.customerSelect')
        @include('components.select.productSelect')
    @endpush
</x-app-layout>
