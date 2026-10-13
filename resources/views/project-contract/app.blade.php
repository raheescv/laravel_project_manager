{{--
    Agreement Module: one Vue app for every project-contract screen. The route
    picks the screen it opens on; the app handles in-module navigation with
    ordinary links back to these routes.
--}}
<x-app-layout>
    <div class="content__boxed">
        <div class="content__wrap pt-3">
            <div id="project-contract-app"
                data-screen="{{ $screen }}"
                data-record-id="{{ $recordId }}"
                data-permissions='@json($permissions)'
                data-user-id="{{ auth()->id() }}"
                data-urls='@json($urls)'></div>
        </div>
    </div>
    @push('scripts')
        @vite(['resources/js/project-contract.js', 'resources/js/journal-entries-modal.js'])
    @endpush
</x-app-layout>
