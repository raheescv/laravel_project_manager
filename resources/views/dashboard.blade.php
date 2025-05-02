<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <h1 class="page-title mb-2">Dashboard</h1>
            <h2 class="h5">Welcome back to the Dashboard.</h2>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="row  mb-3">
                @if (auth()->user()->can('sale.dashboard weekly summary') || auth()->user()->can('inventory.dashboard status'))
                    <div class="col-xl-4 mb-xl-0">
                        @livewire('dashboard.top-card')
                    </div>
                @endif
                @can('report.income vs expense dashboard pie chart')
                    <div class="col-xl-3">
                        @livewire('income-expense-chart')
                    </div>
                @endcan
                @can('sale.dashboard top items')
                    <div class="col-xl-5">
                        @livewire('dashboard.top-sale-items')
                    </div>
                @endcan
            </div>
            @can('sale.dashboard bar chart')
                <div class="row">
                    <div class="col-xl-12 mb-xl-0">
                        @livewire('dashboard.sale.overview')
                    </div>
                </div>
            @endcan
        </div>
    </div>
    @push('scripts')
        <script src="{{ asset('assets/vendors/chart.js/chart.umd.min.js') }}"></script>
        <script src="{{ asset('assets/pages/dashboard-1.js') }}"></script>
    @endpush
</x-app-layout>
