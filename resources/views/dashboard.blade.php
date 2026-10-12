<x-app-layout>
    {{--
        The home dashboard is a Vue app (resources/js/components/Dashboard).
        Every widget loads its own data from dashboard::widget as it scrolls
        into view; the arrangement is per user (dashboard.customize to edit).
    --}}
    <div class="content__boxed">
        <div class="content__wrap">
            <div id="dashboard-app" data-props='@json($dashboard)'></div>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/dashboard.js')
    @endpush
</x-app-layout>
