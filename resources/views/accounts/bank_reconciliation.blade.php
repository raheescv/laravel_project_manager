<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('account::index') }}">Accounts</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Bank Reconciliation Report</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Bank Reconciliation Report</h1>
            <p class="lead">
                Reconcile bank and credit card transactions with delivered dates
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('account.bank-reconciliation-report')
        </div>
    </div>
</x-app-layout>

