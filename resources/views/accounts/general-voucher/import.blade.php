<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('account::general-voucher::index') }}">General Voucher</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Import</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">General Voucher Import</h1>
            <p class="lead">
                Import general vouchers from Excel or CSV files with automatic account head creation and column mapping.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('account.general-voucher.import')
        </div>
    </div>
</x-app-layout>
