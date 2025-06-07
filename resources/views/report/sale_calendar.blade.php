<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item" aria-current="page">Report</li>
                    <li class="breadcrumb-item active" aria-current="page">Sales Calendar</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Sales Calendar Report</h1>
            <p class="lead">
                View daily sales in a calendar format
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('report.sale.calendar-report')
            </div>
        </div>
    </div>
    @push('scripts')
        @include('components.select.branchSelect')
        @include('components.select.paymentMethodSelect')
    @endpush
</x-app-layout>
