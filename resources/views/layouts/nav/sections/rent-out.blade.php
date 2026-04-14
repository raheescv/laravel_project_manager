@if (auth()->user()->can('rent out.view') ||
        auth()->user()->can('rent out.payment') ||
        auth()->user()->can('rent out utility.view') ||
        auth()->user()->can('rent out service.view') ||
        auth()->user()->can('rent out cheque.view') ||
        auth()->user()->can('rent out security.view') ||
        auth()->user()->can('tenant detail.view'))
    <li class="nav-item has-sub">
        @php
            $rentOutList = [
                'property/rent',
                'property/rent/*',
                'property/rent/booking/view/*',
                'property/tenant',
                'property/report/customer-property',
                'property/report/security',
                'property/report/daybook',
            ];
        @endphp
        <a href="#" class="mininav-toggle nav-link {{ request()->is($rentOutList) ? 'active' : '' }}">
            <i class="fa fa-home fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Rent Out</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('rent out.view')
                <li class="nav-item">
                    <a href="{{ route('property::rent::booking') }}"
                        class="nav-link {{ request()->is(['property/rent/booking', 'property/rent/booking/view/*', 'property/rent/booking/create', 'property/rent/booking/edit/*']) ? 'active' : '' }}">Booking</a>
                </li>
            @endcan
            @can('tenant detail.view')
                <li class="nav-item">
                    <a href="{{ route('property::tenant::index') }}"
                        class="nav-link {{ request()->is(['property/tenant']) ? 'active' : '' }}">Tenant Details</a>
                </li>
            @endcan
            @can('rent out.view')
                <li class="nav-item">
                    <a href="{{ route('property::rent::index') }}"
                        class="nav-link {{ request()->is(['property/rent', 'property/rent/view/*']) ? 'active' : '' }}">Rentouts</a>
                </li>
            @endcan
            @can('rent out.payment')
                <li class="nav-item">
                    <a href="{{ route('property::rent::payments') }}"
                        class="nav-link {{ request()->is(['property/rent/payments']) ? 'active' : '' }}">Payments</a>
                </li>
            @endcan
            @can('rent out utility.view')
                <li class="nav-item">
                    <a href="{{ route('property::rent::utilities') }}"
                        class="nav-link {{ request()->is(['property/rent/utilities']) ? 'active' : '' }}">Utilities</a>
                </li>
            @endcan
            @can('rent out service.view')
                <li class="nav-item">
                    <a href="{{ route('property::rent::services') }}"
                        class="nav-link {{ request()->is(['property/rent/services']) ? 'active' : '' }}">Services</a>
                </li>
            @endcan
            @can('rent out.payment')
                <li class="nav-item">
                    <a href="{{ route('property::rent::payment-due') }}"
                        class="nav-link {{ request()->is(['property/rent/payment-due']) ? 'active' : '' }}">Payment Due</a>
                </li>
            @endcan
            @can('rent out cheque.view')
                <li class="nav-item">
                    <a href="{{ route('property::rent::cheque-management') }}"
                        class="nav-link {{ request()->is(['property/rent/cheque-management']) ? 'active' : '' }}">Cheque Management</a>
                </li>
            @endcan
            @can('rent out.payment')
                <li class="nav-item">
                    <a href="{{ route('property::rent::payment-history') }}"
                        class="nav-link {{ request()->is(['property/rent/payment-history']) ? 'active' : '' }}">Payment History</a>
                </li>
            @endcan
            @can('rent out.view')
                <li class="nav-item">
                    <a href="{{ route('property::report::customer-property') }}"
                        class="nav-link {{ request()->is(['property/report/customer-property']) ? 'active' : '' }}">Customer Property</a>
                </li>
            @endcan
            @can('rent out security.view')
                <li class="nav-item">
                    <a href="{{ route('property::report::security') }}"
                        class="nav-link {{ request()->is(['property/report/security']) ? 'active' : '' }}">Security Report</a>
                </li>
            @endcan
            @can('rent out.view')
                <li class="nav-item">
                    <a href="{{ route('property::report::daybook') }}"
                        class="nav-link {{ request()->is(['property/report/daybook']) ? 'active' : '' }}">Day Book</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
