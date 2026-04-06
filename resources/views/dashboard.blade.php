<x-app-layout>

    @push('scripts')
        <script src="{{ secure_asset('assets/vendors/chart.js/chart.umd.min.js') }}"></script>
        <script src="{{ secure_asset('assets/pages/dashboard-1.js') }}"></script>
        <script src="{{ secure_asset('assets/vendors/chart.js/chartjs-plugin-datalabels@2.min.js') }}"></script>
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

            @can('package.dashboard package calendar')
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5 class="mb-3 text-secondary fw-semibold border-start border-4 border-purple ps-3">Package Calendar</h5>
                    </div>
                    <div class="col-xl-12 mb-4">
                        @livewire('package.package-calendar', ['package_id' => null])
                    </div>
                </div>
            @endcan

            {{-- Property Overview Dashboard --}}
            @can('property.dashboard overview')
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5 class="mb-3 text-secondary fw-semibold border-start border-4 border-teal ps-3">
                            <i class="fa fa-building me-2"></i>Property Overview
                        </h5>
                    </div>
                    <div class="col-xl-12">
                        @livewire('dashboard.property-overview-dashboard')
                    </div>
                </div>
            @endcan

            {{-- Property Maintenance Dashboard --}}
            @can('property dashboard.maintenance overview')
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5 class="mb-3 text-secondary fw-semibold border-start border-4 border-info ps-3">
                            <i class="fa fa-wrench me-2"></i>Maintenance Overview
                        </h5>
                    </div>
                    <div class="col-xl-12">
                        @livewire('dashboard.property-maintenance-dashboard')
                    </div>
                </div>
            @endcan

            {{-- Property Complaint Dashboard --}}
            @can('property dashboard.complaint overview')
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5 class="mb-3 text-secondary fw-semibold border-start border-4 border-danger ps-3">
                            <i class="fa fa-exclamation-triangle me-2"></i>Complaint Analytics
                        </h5>
                    </div>
                    <div class="col-xl-12">
                        @livewire('dashboard.property-complaint-dashboard')
                    </div>
                </div>
            @endcan

            {{-- Property Financial Dashboard --}}
            @can('property dashboard.financial overview')
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5 class="mb-3 text-secondary fw-semibold border-start border-4 border-warning ps-3">
                            <i class="fa fa-dollar me-2"></i>Property Financial Overview
                        </h5>
                    </div>
                    <div class="col-xl-12">
                        @livewire('dashboard.property-financial-dashboard')
                    </div>
                </div>
            @endcan

            {{-- Supply Request Dashboard --}}
            @can('property dashboard.supply request overview')
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5 class="mb-3 text-secondary fw-semibold border-start border-4 border-cyan ps-3">
                            <i class="fa fa-truck me-2"></i>Supply Request Overview
                        </h5>
                    </div>
                    <div class="col-xl-12">
                        @livewire('dashboard.supply-request-dashboard')
                    </div>
                </div>
            @endcan

            {{-- Rent Out Expiry Dashboard --}}
            @can('property dashboard.rent out expiry overview')
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5 class="mb-3 text-secondary fw-semibold border-start border-4 border-orange ps-3">
                            <i class="fa fa-calendar me-2"></i>Rent Out Expiry Tracker
                        </h5>
                    </div>
                    <div class="col-xl-12">
                        @livewire('dashboard.rent-out-expiry-dashboard')
                    </div>
                </div>
            @endcan

            <div class="text-center text-muted small py-3">
                <p class="mb-0">© {{ date('Y') }} {{ config('app.name') }} | Dashboard last updated: {{ date('d M Y, H:i A') }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
