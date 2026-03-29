<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $type === 'Return' ? 'Supply Return' : 'Asset Supply' }}</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">{{ $type === 'Return' ? 'Supply Return' : 'Asset Supply' }}</h1>
            <p class="lead">{{ $type === 'Return' ? 'Supply return list' : 'Supply request list' }}</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('supply-request.table', ['type' => $type])
        </div>
    </div>
    @push('scripts')
        @include('components.select.branchSelect')
        @include('components.select.propertySelect')
        @include('components.select.propertyGroupSelect')
        @include('components.select.propertyBuildingSelect')
        @include('components.select.propertyTypeSelect')
        @include('components.select.userSelect')
    @endpush
</x-app-layout>
