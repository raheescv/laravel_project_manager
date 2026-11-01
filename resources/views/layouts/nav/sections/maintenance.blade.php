@if (auth()->user()->can('maintenance.view') ||
        auth()->user()->can('maintenance.create') ||
        auth()->user()->can('maintenance.technician view') ||
        auth()->user()->can('property service request.view') ||
        auth()->user()->can('property service request.report'))
    <li class="nav-item has-sub">
        @php
            $maintenanceList = [
                'property/maintenance',
                'property/maintenance/*',
                'property/service-request',
                'property/service-request/*',
            ];
        @endphp
        <a href="#" class="mininav-toggle nav-link {{ request()->is($maintenanceList) ? 'active' : '' }}">
            <i class="fa fa-wrench fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Maintenance</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('maintenance.view')
                <li class="nav-item">
                    <a href="{{ route('property::maintenance::index') }}"
                        class="nav-link {{ request()->is(['property/maintenance', 'property/maintenance/edit/*', 'property/maintenance/create']) ? 'active' : '' }}">Registration</a>
                </li>
            @endcan
            @can('maintenance.technician view')
                <li class="nav-item">
                    <a href="{{ route('property::maintenance::technician') }}"
                        class="nav-link {{ request()->is('property/maintenance/technician') ? 'active' : '' }}">Technician</a>
                </li>
            @endcan
            @can('property service request.view')
                <li class="nav-item">
                    <a href="{{ route('property::service_request::index') }}"
                        class="nav-link {{ request()->is(['property/service-request', 'property/service-request/edit/*', 'property/service-request/create']) ? 'active' : '' }}">Service Requests</a>
                </li>
            @endcan
            @can('property service request.report')
                <li class="nav-item">
                    <a href="{{ route('property::service_request::report') }}"
                        class="nav-link {{ request()->is('property/service-request/report') ? 'active' : '' }}">Service Request Report</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
