<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item">Accounts</li>
                    <li class="breadcrumb-item active" aria-current="page">Chart of Accounts</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Chart of Accounts</h1>
            <p class="lead">Manage all account heads, categories, and ledgers</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('account.table')
        </div>
    </div>
    <x-account.account-modal />
</x-app-layout>
