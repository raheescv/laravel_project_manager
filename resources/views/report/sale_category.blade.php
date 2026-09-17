<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item" aria-current="page">Report</li>
                    <li class="breadcrumb-item"><a href="{{ route('report::sale_item') }}">Sale Item Wise</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Category Wise</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Category Wise Sale Report</h1>
            <p class="lead">
                Net sales, quantity and share for each product category
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('report.sale.category-wise-report')
        </div>
    </div>
    @push('scripts')
        @include('components.select.employeeSelect')
        @include('components.select.branchSelect')
    @endpush
</x-app-layout>
