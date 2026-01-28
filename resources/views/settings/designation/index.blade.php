<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Designation</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Designation</h1>
            <p class="lead">
                A table is an arrangement of Designation
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('settings.designation.table')
        </div>
    </div>

    <x-settings.designation.designation-modal />
    @push('scripts')
        <x-select.designationSelect />
    @endpush
</x-app-layout>
