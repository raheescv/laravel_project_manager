<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings::index') }}">Settings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Holiday Calendar</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Holiday Calendar</h1>
            <p class="lead">
                The dates the business is closed — no appointments are offered on any of them
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('settings.holiday')
        </div>
    </div>
</x-app-layout>
