<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Sale</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Day Session Management</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Day Session Management</h1>
            <p class="lead">
                Open and close each branch's sales day, track cash, and reconcile the drawer.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('sale-day-session.branch-sale-day-session-manager')
        </div>
    </div>
    @push('scripts')
    @endpush
</x-app-layout>
