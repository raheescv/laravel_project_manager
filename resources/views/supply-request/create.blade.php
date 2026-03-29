<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supply-request::index') }}">Asset Supply</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Page</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Asset Supply</h1>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('supply-request.create', ['id' => $id ?? null, 'type' => $type ?? 'Add'])
        </div>
    </div>
    @push('scripts')
        <x-select.propertyGroupSelect />
        <x-select.propertyBuildingSelect />
        <x-select.propertyTypeSelect />
        <x-select.propertySelect />
        <x-select.productSelect />
    @endpush
</x-app-layout>
