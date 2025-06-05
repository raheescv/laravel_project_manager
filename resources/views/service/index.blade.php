<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Service</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Service Management</h1>
            <p class="lead">
                Manage your services with advanced filtering
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('service.table')
            </div>
        </div>
    </div>
    <x-service.service-import-modal />
    @push('scripts')
        @include('components.select.departmentSelect')
        @include('components.select.categorySelect')
        @include('components.select.unitSelect')
    @endpush
</x-app-layout>
