<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="demo-psi-home"></i></a></li>
                    <li class="breadcrumb-item">Properties</li>
                    <li class="breadcrumb-item">Report</li>
                    <li class="breadcrumb-item active" aria-current="page">Rentout Security Report</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Rentout Security Report</h1>
            <p class="lead">View security deposit reports</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('rent-out.report.security-table')
        </div>
    </div>
    @push('scripts')
        <x-select.propertyGroupSelect />
        <x-select.propertyBuildingSelect />
        <x-select.propertySelect />
        <x-select.customerSelect />
    @endpush
</x-app-layout>
