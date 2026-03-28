<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">GRN</li>
                </ol>
            </nav>
            <h1 class="mt-2 mb-0 page-title">Goods Received Notes</h1>
            <p class="lead">
                Manage goods received notes for local purchase orders
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="mb-3 card">
                @livewire('grn.table')
            </div>
        </div>
    </div>
    @push('scripts')
        @include('components.select.branchSelect')
    @endpush
</x-app-layout>
