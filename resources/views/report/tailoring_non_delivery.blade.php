<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tailoring::order::index') }}">Tailoring</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Non-Delivery Report</li>
                </ol>
            </nav>
            <div class="card border-0 shadow mt-2">
                <div class="card-body py-3 px-3 px-md-4 bg-primary-subtle border-bottom border-primary-subtle">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-2">
                        <div>
                            <h1 class="h3 mb-1 fw-bold text-primary-emphasis">
                                <i class="fa fa-truck text-danger me-2"></i>Tailoring Non-Delivery Report
                            </h1>
                            <p class="mb-0 text-secondary-emphasis">
                                <i class="fa fa-chart-line me-1"></i>Order-wise non-delivery summary with quantity totals and export options.
                            </p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge rounded-pill text-bg-danger px-3 py-2">
                                <i class="fa fa-clock-o me-1"></i>Pending Delivery Focus
                            </span>
                            <span class="badge rounded-pill text-bg-light border text-dark px-3 py-2">
                                <i class="fa fa-scissors me-1"></i>Tailoring
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card border-0 shadow rounded-3 mb-3">
                @livewire('report.tailoring-non-delivery-report')
            </div>
        </div>
    </div>
    @push('scripts')
        @include('components.select.customerSelect')
        @include('components.select.branchSelect')
        @include('components.select.productSelect')
    @endpush
</x-app-layout>
