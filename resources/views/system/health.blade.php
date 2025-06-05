<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">System Health</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">System Health Monitor</h1>
            <p class="lead">
                Real-time monitoring of system health and performance metrics
            </p>
        </div>
    </div>

    <div class="content__boxed">
        <div class="content__wrap">
            <div class="mb-3">
                @livewire('system.health-monitor')
            </div>
        </div>
    </div>
</x-app-layout>
