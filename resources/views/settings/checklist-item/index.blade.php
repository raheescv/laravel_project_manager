<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item">Settings</li>
                    <li class="breadcrumb-item active" aria-current="page">Checklist Items</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Checklist Items</h1>
            <p class="lead">
                Manage rent out checklist items
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('settings.checklist-item.table')
            </div>
        </div>
    </div>
    <x-settings.checklist-item.checklist-item-modal />
    <x-image-preview-modal />
</x-app-layout>
