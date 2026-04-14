<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Properties</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Properties</h1>
            <p class="lead">
                Manage properties and their details
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('property.property.table')
            </div>
        </div>
    </div>
    <x-property.property.property-modal />
</x-app-layout>
