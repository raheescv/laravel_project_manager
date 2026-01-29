<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings::index') }}">Settings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tailoring Category</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Tailoring Category</h1>
            <p class="lead">
                Manage tailoring categories for your orders
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('settings.tailoring-category.table')
            <div class="mt-4">
                @livewire('settings.tailoring-category-model.table')
            </div>
        </div>
    </div>

    <x-settings.tailoring-category.tailoring-category-modal />
    <x-settings.tailoring-category.tailoring-category-model-modal />
</x-app-layout>
