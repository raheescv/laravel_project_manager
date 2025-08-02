<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('api_log::index') }}">API Logs</a></li>
                    <li class="breadcrumb-item active" aria-current="page">MOQ API Settings</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Moq API Settings</h1>
            <p class="lead">
                Configure Moq Solutions API credentials
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('api-log.m-o-q-settings')
            </div>
        </div>
    </div>
</x-app-layout>
