<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Sale</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Sale</h1>
            <p class="lead">
                A table is an arrangement of Sale
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('sale.table')
            </div>
        </div>
    </div>
    <x-sale.column-visibility-canvas />
    @push('scripts')
        @include('components.select.customerSelect')
        @include('components.select.userSelect')
        @include('components.select.branchSelect')
        @include('components.select.paymentMethodSelect')
    @endpush
</x-app-layout>
