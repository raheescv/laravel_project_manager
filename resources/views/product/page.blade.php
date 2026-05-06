<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    @if ($type == 'product')
                        <li class="breadcrumb-item"><a href="{{ route('product::index') }}">Product</a></li>
                    @elseif ($type == 'asset')
                        <li class="breadcrumb-item"><a href="{{ route('asset::index') }}">Assets</a></li>
                    @else
                        <li class="breadcrumb-item"><a href="{{ route('service::index') }}">Service</a></li>
                    @endif
                    <li class="breadcrumb-item active" aria-current="page">Page</li>
                </ol>
            </nav>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
                <div>
                    <h1 class="page-title mb-0">{{ ucFirst($type) }}</h1>
                    <p class="lead mb-0">A {{ $type }} Form</p>
                </div>
                @if ($id)
                    @can('inventory.view')
                        <a href="{{ route('inventory::product::view', $id) }}"
                           class="btn btn-outline-primary btn-sm d-inline-flex align-items-center"
                           target="_blank"
                           rel="noopener">
                            <i class="fa fa-eye me-2"></i>
                            <span>View Product</span>
                        </a>
                    @endcan
                @endif
            </div>
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
        @include('components.select.brandSelect')
        @include('components.select.categorySelect')
        @include('components.select.accountSelect')
    @endpush
</x-app-layout>
