<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Unit</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Unit</h1>
            <p class="lead">
                A table is an arrangement of Unit
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('settings.unit.table')
        </div>
    </div>

    <x-settings.unit.unit-modal />
    @push('scripts')
        @include('components.select.unitSelect')
    @endpush
</x-app-layout>
