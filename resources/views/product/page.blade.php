<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    @if ($type == 'product')
                        <li class="breadcrumb-item"><a href="{{ route('product::index') }}">Product</a></li>
                    @else
                        <li class="breadcrumb-item"><a href="{{ route('service::index') }}">Service</a></li>
                    @endif
                    <li class="breadcrumb-item active" aria-current="page">Page</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">{{ ucFirst($type) }}</h1>
            <p class="lead">
                A {{ $type }} Form
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('product.page', ['type' => $type, 'table_id' => $id])
        </div>
    </div>
    <x-settings.unit.unit-modal />
    <x-settings.category.category-modal />
    <x-settings.department.department-modal />
    @if ($id)
        @component('components.product.units-modal', ['product_id' => $id])
        @endcomponent
        @component('components.product.prices-modal', ['product_id' => $id])
        @endcomponent
    @endif
    @push('scripts')
        @include('components.select.departmentSelect')
        @include('components.select.categorySelect')
    @endpush
</x-app-layout>
