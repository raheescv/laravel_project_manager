<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item" aria-current="page">Report</li>
                    <li class="breadcrumb-item active" aria-current="page">Tax Report</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Tax Report</h1>
            <p class="lead">
                Tax credit and liability breakdown from Purchase, Purchase Return, Sale, and Sale Return transactions
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('report.day-wise-tax-report')
            </div>
        </div>
    </div>
    @push('scripts')
        <x-select.branchSelect />
    @endpush
</x-app-layout>


