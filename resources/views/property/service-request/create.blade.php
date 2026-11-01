<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('property::service_request::index') }}">Service Requests</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $id ? '#' . $id : 'New' }}</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Service Request <small class="text-muted">{{ $id ? '#' . $id : 'Form' }}</small></h1>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('property.service-request.page', ['id' => $id ?? null])
        </div>
    </div>
    @push('scripts')
        @include('components.select.propertyGroupSelect')
        @include('components.select.propertyBuildingSelect')
        @include('components.select.propertyTypeSelect')
        @include('components.select.propertySelect')
    @endpush
</x-app-layout>
