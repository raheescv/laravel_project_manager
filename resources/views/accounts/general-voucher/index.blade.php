<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Account</li>
                    <li class="breadcrumb-item active" aria-current="page">General Voucher</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">General Voucher</h1>
            <p class="lead">
                A table is an arrangement of General Vouchers
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('account.general-voucher.table')
            </div>
        </div>
    </div>
    <x-account.account-modal />
    <x-account.general-voucher.general-voucher-modal />
    @push('scripts')
        <x-select.branchSelect />
        <x-select.accountSelect />
    @endpush
</x-app-layout>

