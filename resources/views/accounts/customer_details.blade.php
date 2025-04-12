<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('account::index') }}">Account</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('account::customer::index') }}">Customers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Customer</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Customer </h1>
            <p class="lead">
                A customer details
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                <div class="card-body">
                    @livewire('account.customer.view', ['account_id' => $id])
                </div>
            </div>
        </div>
    </div>
    <x-account.customer-modal />
</x-app-layout>
