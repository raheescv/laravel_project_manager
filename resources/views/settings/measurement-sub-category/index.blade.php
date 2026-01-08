<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}">Home</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                       Model
                    </li>
                </ol>
            </nav>

            <h1 class="page-title mb-0 mt-2">Model</h1>

            <p class="lead">
                A table is an arrangement of Models
            </p>
        </div>
    </div>

    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">

                {{-- Livewire table --}}
                @livewire('settings.measurement-sub-category.table')

            </div>
        </div>
    </div>

    {{-- Modals --}}
    <x-settings.measurement-subcategory.category-modal />
    

    @push('scripts')
        @include('components.select.measurementCategorySelect')
    @endpush
</x-app-layout>
