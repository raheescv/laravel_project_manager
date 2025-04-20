<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Account</li>
                    <li class="breadcrumb-item active" aria-current="page">Expense</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Expense </h1>
            <p class="lead">
                A table is an arrangement of Expenses
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('account.expense.table')
            </div>
        </div>
    </div>
    <x-account.expense.expense-modal />
    <x-account.account-modal />
    @push('scripts')
        <x-select.branchSelect />
        <x-select.paymentMethodSelect />
        <x-select.accountSelect />
    @endpush
</x-app-layout>
