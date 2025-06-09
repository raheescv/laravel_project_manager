<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">sale day sessions report</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">sale day sessions report</h1>
            <p class="lead">
                A table is an arrangement of sale day sessions
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('sale-day-session.sale-day-sessions-report')
            </div>
        </div>
    </div>
    @push('scripts')
    @endpush
</x-app-layout>
