<x-app-layout>

    @push('scripts')
        <script src="{{ asset('assets/vendors/chart.js/chart.umd.min.js') }}"></script>
        <script src="{{ asset('assets/pages/dashboard-1.js') }}"></script>
        <script src="{{ asset('assets/vendors/chart.js/chartjs-plugin-datalabels@2.min.js') }}"></script>
    @endpush
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="bg-primary p-4 rounded-4 mb-4">
                <h1 class="display-5 text-white mb-2">Welcome back, {{ auth()->user()->name }}</h1>
                <p class="lead mb-0 text-white">Here's what's happening with your business today.</p>
            </div>

            <div class="row mb-4">
                @if (auth()->user()->can('sale.dashboard weekly summary') || auth()->user()->can('inventory.dashboard status'))
                    <div class="col-xl-12 mb-4">
                        <div class="row g-3">
                            @livewire('dashboard.top-card')
                        </div>
                    </div>
                @endif
            </div>

            <div class="row mb-4">
                @can('sale.dashboard bar chart')
                    <div class="col-xl-8 mb-4">
                        @livewire('dashboard.sale.overview')
                    </div>
                @endcan
                @can('sale.dashboard top items')
                    <div class="col-xl-4 mb-4">
                        @livewire('dashboard.top-sale-items')
                    </div>
                @endcan
            </div>

            <div class="row">
                @can('report.income vs expense dashboard bar chart')
                    <div class="col-xl-8 mb-4">
                        @livewire('dashboard.income-expense-bar-chart')
                    </div>
                @endcan
                @can('report.income vs expense dashboard pie chart')
                    <div class="col-xl-4 mb-4">
                        @livewire('dashboard.income-expense-chart')
                    </div>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
