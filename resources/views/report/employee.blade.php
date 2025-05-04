<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item" aria-current="page">Report</li>
                    <li class="breadcrumb-item active" aria-current="page">Employee</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Employee Report</h1>
            <p class="lead">
                Report For Employee
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('report.employee.employee-report')
        </div>
    </div>
    @push('scripts')
        <script type="text/javascript" src="https://cdn.canvasjs.com/jquery.canvasjs.min.js"></script>
        <x-select.employeeSelect />
        <x-select.branchSelect />
        <x-select.productSelect />
    @endpush
</x-app-layout>
