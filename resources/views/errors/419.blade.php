<x-guest-layout>
    <div class="content__wrap">
        <div class="pt-5 mb-4 text-center">
            <div class="error-code page-title fw-semibold mb-3">419</div>
            <h3 class="mb-4">
                <div class="badge bg-warning text-uppercase px-5">Page Expired</div>
            </h3>
            <p class="lead mb-5 text-body-emphasis">Your session has expired. Please refresh the page or return to the home page.</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="d-flex justify-content-center gap-3">
                <button type="button" onclick="window.location.reload()" class="btn btn-light hstack gap-2">
                    <i class="demo-psi-arrow-right-3 fs-5 opacity-30"></i>
                    <span class="vr"></span>
                    Refresh Page
                </button>
                <a href="{{ route('home') }}" class="btn btn-primary hstack gap-2">
                    <i class="demo-psi-home fs-5"></i>
                    <span class="vr"></span>
                    Return home
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>

