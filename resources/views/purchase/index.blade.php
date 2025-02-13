<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Purchase</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Purchase</h1>
            <p class="lead">
                A table is an arrangement of Purchase
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('purchase.table')
            </div>
        </div>
    </div>
    <x-purchase.column-visibility-canvas />
    @push('scripts')
        @include('components.select.vendorSelect')
        @include('components.select.branchSelect')
    @endpush
</x-app-layout>
