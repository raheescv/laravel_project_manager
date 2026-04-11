@if (auth()->user()->can('day session.list') || auth()->user()->can('day session.print'))
    <li class="nav-item has-sub">
        @php
            $list = ['sale/day-management', 'sale/day-sessions-report', 'sale/day-session/*'];
        @endphp
        <a href="#" class="mininav-toggle nav-link {{ request()->is($list) ? 'active' : '' }}">
            <i class="fa fa-sun-o fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Day Session</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('day session.view')
                <li class="nav-item">
                    <a href="{{ route('sale::day-management') }}"
                        class="nav-link {{ request()->is(['sale/day-management']) ? 'active' : '' }}">Day Management</a>
                </li>
            @endcan
            @can('day session.list')
                <li class="nav-item">
                    <a href="{{ route('sale::day-sessions-report') }}"
                        class="nav-link {{ request()->is(['sale/day-sessions-report', 'sale/day-session/*']) ? 'active' : '' }}">Day Sessions Report</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
