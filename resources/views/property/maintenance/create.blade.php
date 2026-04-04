<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('property::maintenance::index') }}">Maintenance</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $id ? 'Edit' : 'Registration' }}</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Complaints <small class="text-muted">Form</small></h1>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('maintenance.page', ['id' => $id ?? null])
        </div>
    </div>
    @push('scripts')
        @include('components.select.propertyGroupSelect')
        @include('components.select.propertyBuildingSelect')
        @include('components.select.propertyTypeSelect')
        @include('components.select.propertySelect')
    @endpush
</x-app-layout>
