<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item">Reports</li>
                    <li class="breadcrumb-item active" aria-current="page">Balance Sheet</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Balance Sheet</h1>
            <p class="lead">Financial position as of a specific date</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <livewire:reports.balance-sheet />
        </div>
    </div>
    @push('scripts')
        <x-select.branchSelect />
    @endpush
</x-app-layout>
