<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">LPO Purchases</li>
                </ol>
            </nav>
            <h1 class="mt-2 mb-0 page-title">LPO Purchases</h1>
            <p class="lead">
                Manage purchases created from local purchase orders
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="mb-3 card">
                @livewire('lpo-purchase.table')
            </div>
        </div>
    </div>
    @push('scripts')
        @include('components.select.branchSelect')
        @include('components.select.vendorSelect')
        @include('components.select.userSelect')
        @include('components.select.lpoSelect')
    @endpush
</x-app-layout>
