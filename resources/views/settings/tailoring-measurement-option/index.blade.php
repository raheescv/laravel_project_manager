<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings::index') }}">Settings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tailoring Measurement Option</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Tailoring Measurement Option</h1>
            <p class="lead">
                Manage measurement options (cuff, collar, stitching, etc.) for tailoring orders
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('settings.tailoring-measurement-option.table')
        </div>
    </div>

    <x-settings.tailoring-measurement-option.tailoring-measurement-option-modal />
</x-app-layout>
