<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">API Logs</h1>
            <p class="lead">
                Monitor and manage API calls to external services
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('api-log.table')
            </div>
        </div>
    </div>

    <!-- API Log Detail Modal Component -->
    @livewire('api-log.detail-modal')

    @push('scripts')
    @endpush
</x-app-layout>
