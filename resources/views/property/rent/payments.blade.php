<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Rental Payments</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Rental Payments</h1>
            <p class="lead">
                Manage rental payments
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('rent-out.rent.payment-table')
            </div>
        </div>
    </div>
</x-app-layout>
