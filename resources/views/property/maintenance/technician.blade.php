<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('property::maintenance::index') }}">Maintenance</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Technicians Dashboard</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center justify-content-between">
                <h1 class="page-title mb-0 mt-2">Technicians <small class="text-muted">Dashboard</small></h1>
                <a href="{{ route('property::maintenance::index') }}" class="btn btn-info btn-sm rounded-pill px-3">
                    <i class="fa fa-list me-1"></i> Registration List
                </a>
            </div>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('maintenance.technician')
        </div>
    </div>
    @push('scripts')
        @include('components.select.propertyGroupSelect')
        @include('components.select.propertyBuildingSelect')
        @include('components.select.propertySelect')
        @include('components.select.customerSelect')
    @endpush
</x-app-layout>
