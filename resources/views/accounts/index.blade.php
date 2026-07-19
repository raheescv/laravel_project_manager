<x-app-layout>
    <div class="content__boxed">
        <div class="content__wrap">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item">Accounts</li>
                    <li class="breadcrumb-item active" aria-current="page">Chart of Accounts</li>
                </ol>
            </nav>
            @livewire('account.table')
        </div>
    </div>
    <x-account.account-modal />
</x-app-layout>
