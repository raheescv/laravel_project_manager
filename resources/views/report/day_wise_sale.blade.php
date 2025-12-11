<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item" aria-current="page">Report</li>
                    <li class="breadcrumb-item active" aria-current="page">Day Wise Sale Report</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Day Wise Sale Report</h1>
            <p class="lead">
                Daily sales report with count, net sale, gross sale, tax amount, discount, and return amount
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('report.sale.day-wise-sale-report')
            </div>
        </div>
    </div>
    @push('scripts')
        <x-select.branchSelect />
    @endpush
</x-app-layout>

