<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="demo-psi-home"></i></a></li>
                    <li class="breadcrumb-item">Properties</li>
                    <li class="breadcrumb-item">Sales</li>
                    <li class="breadcrumb-item active" aria-current="page">Commissions</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Commissions</h1>
            <p class="lead">Approve and pay salesman commissions across all sale agreements.</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('rent-out.commission-table')
        </div>
    </div>
    <x-rent-out.commission-pay-modal />
    @push('scripts')
        <x-select.employeeSelect />
    @endpush
</x-app-layout>
