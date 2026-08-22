{{--
    Chromeless console, like the barcode designer: no sidebar, header or
    footer — the console IS the window (100dvh). The console bar carries a
    back button to Settings, and toastr is wired here because the standalone
    shell ships without the app layout's toast stack.
--}}
<x-layouts.standalone title="Email Templates">
    {{-- 'styles' stack, not 'head': it must come AFTER bootstrap.min.css or
         Bootstrap's own .toast class paints the toasts white-on-white. --}}
    @push('styles')
        <link rel="stylesheet" href="{{ https_asset('assets/vendors/toaster/toastr.min.css') }}">
    @endpush

    <div class="etx-page etx-standalone">
        @livewire('settings.email-template')
    </div>

    @push('scripts')
        <script src="{{ https_asset('assets/js/jquery-3.7.1.min.js') }}"></script>
        <script src="{{ https_asset('assets/vendors/toaster/toastr.min.js') }}"></script>
        <script>
            // Same options and event mapping as layouts/app.blade.php, so the
            // component's success/error dispatches behave like everywhere else.
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "timeOut": "5000"
            };
            window.addEventListener('success', event => {
                var data = event.detail[0] || event.detail;
                if (data && data.title && data.message) {
                    toastr.info(data.message, data.title);
                } else if (data && data.title) {
                    toastr.info(data.title);
                } else if (data && data.message) {
                    toastr.info(data.message);
                }
            });
            window.addEventListener('warning', event => {
                var data = event.detail[0] || event.detail;
                if (data && data.title && data.message) {
                    toastr.warning(data.message, data.title);
                } else if (data && data.title) {
                    toastr.warning(data.title);
                } else if (data && data.message) {
                    toastr.warning(data.message);
                }
            });
            window.addEventListener('error', event => {
                var data = event.detail[0] || event.detail;
                if (data && data.message) {
                    toastr.error(data.message);
                } else if (event.error && event.error.message) {
                    toastr.error(event.error.message);
                }
            });
        </script>
    @endpush
</x-layouts.standalone>
