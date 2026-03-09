<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('product::index') }}">Product</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Gallery</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Product Image Gallery</h1>
            <p class="lead">
                Browse, filter, and manage all product images in one place.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('product.gallery')
        </div>
    </div>
    @push('scripts')
        @include('components.select.departmentSelect')
        @include('components.select.brandSelect')
        @include('components.select.categorySelect')
    @endpush
</x-app-layout>
