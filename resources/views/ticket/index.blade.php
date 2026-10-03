{{--
    Chromeless console, like the email-template and barcode consoles: no
    sidebar, header or footer. The Vue app owns the whole window; its console
    bar carries the only way back — the Dashboard button.
--}}
<x-layouts.standalone title="Support Tickets">
    @push('head')
        <meta name="pusher-key" content="{{ config('broadcasting.connections.pusher.key') }}">
        <meta name="pusher-cluster" content="{{ config('broadcasting.connections.pusher.options.cluster') }}">
    @endpush

    <div id="ticket-console"
        data-view="{{ $view }}"
        data-permissions='@json($permissions)'
        data-live-channel="{{ $liveChannel }}"
        data-dashboard-url="{{ route('dashboard') }}"
        data-board-url="{{ route('ticket::index') }}"
        data-import-url="{{ route('ticket::import') }}"></div>

    @push('scripts')
        @vite('resources/js/ticket-console.js')
    @endpush
</x-layouts.standalone>
