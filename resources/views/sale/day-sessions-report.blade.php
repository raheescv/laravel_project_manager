<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item">Sale</li>
                    <li class="breadcrumb-item active" aria-current="page">Day Sessions Report</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Day Sessions Report</h1>
            <p class="lead">
                Every branch's opened and closed sales days in the selected range — invoices, collections and drawer variance.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('sale-day-session.sale-day-sessions-report')
        </div>
    </div>
</x-app-layout>
