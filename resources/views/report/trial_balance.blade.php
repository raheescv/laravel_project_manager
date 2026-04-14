<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item">Reports</li>
                    <li class="breadcrumb-item active" aria-current="page">Trial Balance</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Trial Balance</h1>
            <p class="lead">View debit and credit balances for all accounts</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('reports.trial-balance')
        </div>
    </div>
    @push('scripts')
        <x-select.branchSelect />
        <x-select.accountSelect />
    @endpush
</x-app-layout>
