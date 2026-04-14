<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="demo-psi-home"></i></a></li>
                    <li class="breadcrumb-item">Properties</li>
                    <li class="breadcrumb-item">Rent</li>
                    <li class="breadcrumb-item active" aria-current="page">Service Report</li>
                </ol>
            </nav>
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mt-2">
                <div>
                    <h1 class="page-title mb-0">RentOut Service Report</h1>
                    <p class="lead mb-0">Track service charges, payments and outstanding balances across all rent agreements.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('rent-out.service-payment-table')
        </div>
    </div>
    @push('scripts')
        <x-select.propertyGroupSelect />
        <x-select.propertyBuildingSelect />
        <x-select.propertySelect />
        <x-select.customerSelect />
    @endpush
</x-app-layout>
