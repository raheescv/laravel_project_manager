<x-app-layout>

    @push('scripts')
        <script src="{{ asset('assets/vendors/chart.js/chart.umd.min.js') }}"></script>
        <script src="{{ asset('assets/pages/dashboard-1.js') }}"></script>
        <script src="{{ asset('assets/vendors/chart.js/chartjs-plugin-datalabels@2.min.js') }}"></script>
    @endpush
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="bg-primary bg-gradient p-4 p-md-3 rounded-4 mb-4 shadow-sm position-relative overflow-hidden">
                <div class="position-absolute top-0 end-0 opacity-10 d-none d-md-block">
                    <i class="demo-pli-monitor-2 display-1 text-white" style="font-size: 8rem;"></i>
                </div>
                <div class="position-relative">
                    <h1 class="display-5 fw-bold text-white mb-2">Welcome back, {{ auth()->user()->name }}</h1>
                    <p class="lead mb-0 text-white text-opacity-75">Here's what's happening with your business today.</p>
                </div>
            </div>

            @if (auth()->user()->can('sale.dashboard weekly summary') || auth()->user()->can('inventory.dashboard status'))
                <div class="row mb-4">
                    <div class="col-xl-12 mb-3">
                        <h5 class="mb-3 text-secondary fw-semibold border-start border-4 border-primary ps-3">Business Overview</h5>
                        <div class="row g-3">
                            @livewire('dashboard.top-card')
                        </div>
                    </div>
                </div>
            @endif
            @can('appointment.dashboard')
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5 class="mb-3 text-secondary fw-semibold border-start border-4 border-info ps-3">Appointment Analytics</h5>
                    </div>
                    <div class="col-xl-8 mb-4">
                        @livewire('dashboard.appointment.appointment-chart')
                    </div>
                    <div class="col-xl-4 mb-4">
                        @livewire('dashboard.appointment.upcoming-appointments')
                    </div>
                </div>
            @endcan
            @if (auth()->user()->can('inventory.dashboard status') || auth()->user()->can('sale.dashboard weekly summary'))
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5 class="mb-3 text-secondary fw-semibold border-start border-4 border-danger ps-3">Weekly Summary</h5>
                    </div>
                    @can('inventory.dashboard status')
                        <div class="col-xl-6 mb-4">
                            @livewire('dashboard.inventory.status')
                        </div>
                    @endcan
                    @can('sale.dashboard weekly summary')
                        <div class="col-xl-6 mb-4">
                            @livewire('dashboard.sale.weekly-summary')
                        </div>
                    @endcan
                </div>
            @endif
            @if (auth()->user()->can('sale.dashboard bar chart') || auth()->user()->can('sale.dashboard top items'))
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5 class="mb-3 text-secondary fw-semibold border-start border-4 border-success ps-3">Sales Performance</h5>
                    </div>
                    @can('sale.dashboard bar chart')
                        <div class="col-xl-8 mb-4">
                            @livewire('dashboard.sale.overview')
                        </div>
                    @endcan
                    @can('sale.dashboard top items')
                        <div class="col-xl-4 mb-4">
                            <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden">
                                <div class="card-body">
                                    @livewire('dashboard.top-sale-items')
                                </div>
                            </div>
                        </div>
                    @endcan
                </div>
            @endif

            @if (auth()->user()->can('report.income vs expense dashboard bar chart') || auth()->user()->can('report.income vs expense dashboard pie chart'))
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5 class="mb-3 text-secondary fw-semibold border-start border-4 border-warning ps-3">Financial Overview</h5>
                    </div>
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
            @endif
            <div class="text-center text-muted small py-3">
                <p class="mb-0"> Dashboard last updated: {{ date('d M Y, H:i A') }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
