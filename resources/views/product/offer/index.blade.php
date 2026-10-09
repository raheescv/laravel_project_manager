@php
    $isService = $type === 'service';
    $parentRoute = $isService ? route('service::index') : route('product::index');
@endphp
<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $parentRoute }}">{{ $isService ? 'Service' : 'Product' }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Offers</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">{{ $isService ? 'Service Offers' : 'Product Offers' }}</h1>
            <p class="lead">Dated offer prices for groups of {{ $isService ? 'services' : 'products' }}, sorted by category.</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div id="product-offer-list"
                data-type="{{ $type }}"
                data-permissions='@json($permissions)'
                data-create-url="{{ route('product::offer::create', ['type' => $type]) }}"
                data-edit-url="{{ route('product::offer::edit', ['id' => '__ID__']) }}"></div>
        </div>
    </div>
    @push('scripts')
        @vite('resources/js/product-offer.js')
    @endpush
</x-app-layout>
