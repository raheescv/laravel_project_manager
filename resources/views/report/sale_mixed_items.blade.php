<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item" aria-current="page">Report</li>
                    <li class="breadcrumb-item active" aria-current="page">Sale & Return Items</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Sale & Sale Return Items</h1>
            <p class="lead">Mixed list of completed Sale Items and Sale Return Items.</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('report.sale-mixed-item-report')
            </div>
        </div>
    </div>
    <x-report.sale-mixed-item-column-visibility-canvas />
    @push('scripts')
        @include('components.select.productSelect')
        @include('components.select.employeeSelect')
        @include('components.select.branchSelect')
        @include('components.select.departmentSelect')
        @include('components.select.categorySelect')
        @include('components.select.brandSelect')
    @endpush
</x-app-layout>

