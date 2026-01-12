<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings::index') }}">Settings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Working Day</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Working Day Configuration</h1>
            <p class="lead">
                Configure which days of the week are working days
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('settings.working-day')
        </div>
    </div>
</x-app-layout>
