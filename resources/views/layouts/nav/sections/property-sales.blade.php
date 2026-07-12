@if (auth()->user()->can('rent out lease.view') ||
        auth()->user()->can('rent out lease.payment') ||
        auth()->user()->can('rent out lease.cheque management') ||
        auth()->user()->can('rent out service.view') ||
        auth()->user()->can('rent out security.view'))
    <li class="nav-item has-sub">
        @php
            $salesList = [
                'property/sale',
                'property/sale/*',
                'property/sale/booking/edit/*',
                'property/sale/booking/view/*',
                'property/report/service-charge',
                'property/report/customer-property/lease',
                'property/report/security/lease',
                'property/report/daybook/lease',
            ];
        @endphp
        <a href="#" class="mininav-toggle nav-link {{ request()->is($salesList) ? 'active' : '' }}">
            <i class="fa fa-dollar fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Sales</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('rent out lease.view')
                <li class="nav-item">
                    <a href="{{ route('property::sale::index') }}"
                        class="nav-link {{ request()->is(['property/sale', 'property/sale/view/*', 'property/sale/edit/*', 'property/sale/create']) ? 'active' : '' }}">Sales</a>
                </li>
            @endcan
            @can('rent out lease.view')
                <li class="nav-item">
                    <a href="{{ route('property::sale::booking') }}"
                        class="nav-link {{ request()->is(['property/sale/booking', 'property/sale/booking/edit/*', 'property/sale/booking/view/*', 'property/sale/booking/create']) ? 'active' : '' }}">Booking</a>
                </li>
            @endcan
            @can('rent out lease.payment')
                <li class="nav-item">
                    <a href="{{ route('property::sale::payments') }}"
                        class="nav-link {{ request()->is(['property/sale/payments']) ? 'active' : '' }}">Payments</a>
                </li>
            @endcan
            @can('rent out service.view')
                <li class="nav-item">
                    <a href="{{ route('property::sale::services') }}"
                        class="nav-link {{ request()->is(['property/sale/services']) ? 'active' : '' }}">Services</a>
                </li>
            @endcan
            @can('rent out lease.payment')
                <li class="nav-item">
                    <a href="{{ route('property::sale::payment-due') }}"
                        class="nav-link {{ request()->is(['property/sale/payment-due']) ? 'active' : '' }}">Payment Due</a>
                </li>
            @endcan
            @can('rent out lease.view')
                <li class="nav-item">
                    <a href="{{ route('property::report::service-charge') }}"
                        class="nav-link {{ request()->is(['property/report/service-charge']) ? 'active' : '' }}">Service Charge Report</a>
                </li>
            @endcan
            @can('rent out lease.cheque management')
                <li class="nav-item">
                    <a href="{{ route('property::sale::cheque-management') }}"
                        class="nav-link {{ request()->is(['property/sale/cheque-management']) ? 'active' : '' }}">Cheque Management</a>
                </li>
            @endcan
            @can('rent out lease.payment')
                <li class="nav-item">
                    <a href="{{ route('property::sale::payment-history') }}"
                        class="nav-link {{ request()->is(['property/sale/payment-history']) ? 'active' : '' }}">Payment History</a>
                </li>
            @endcan
            @can('rent out.view')
                <li class="nav-item">
                    <a href="{{ route('property::report::customer-property', 'lease') }}"
                        class="nav-link {{ request()->is(['property/report/customer-property/lease']) ? 'active' : '' }}">Customer Property</a>
                </li>
            @endcan
            @can('rent out security.view')
                <li class="nav-item">
                    <a href="{{ route('property::report::security', 'lease') }}"
                        class="nav-link {{ request()->is(['property/report/security/lease']) ? 'active' : '' }}">Security Report</a>
                </li>
            @endcan
            @can('rent out lease.view')
                <li class="nav-item">
                    <a href="{{ route('property::report::daybook', 'lease') }}"
                        class="nav-link {{ request()->is(['property/report/daybook/lease']) ? 'active' : '' }}">Day Book</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
