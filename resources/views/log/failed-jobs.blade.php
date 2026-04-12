<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item" aria-current="page">Log</li>
                    <li class="breadcrumb-item active" aria-current="page">Failed Jobs</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Failed Jobs</h1>
            <p class="lead">Recently failed queue jobs with the failure reason and queue context.</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('log.failed-jobs')
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#root').attr('class', 'root mn--push');
            })
        </script>
    @endpush
</x-app-layout>
