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
                @livewire('product.table')
            </div>
        </div>
    </div>
    <x-product.product-import-modal />
    @push('scripts')
        @include('components.select.departmentSelect')
        @include('components.select.categorySelect')
        @include('components.select.unitSelect')
    @endpush
</x-app-layout>
