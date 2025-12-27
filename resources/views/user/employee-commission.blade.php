<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Employee Commission</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Employee Commission</h1>
            <p class="lead">
                A table is an arrangement of Employee Commission
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('user.employee-commission.table')
            </div>
        </div>
    </div>
    <x-user.employee-commission-modal />
    @push('scripts')
    @endpush
</x-app-layout>

