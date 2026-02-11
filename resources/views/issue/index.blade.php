<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Issue</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Issue</h1>
            <p class="lead">
                Assign products to customers (issue / return) by date
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('issue.table')
            </div>
        </div>
    </div>
    @push('scripts')
        @include('components.select.customerSelect')
        @include('components.select.productSelect')
    @endpush
</x-app-layout>
