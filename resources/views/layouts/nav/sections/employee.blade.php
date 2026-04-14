@if (auth()->user()->can('employee.view'))
    <li class="nav-item has-sub">
        <a href="#"
            class="mininav-toggle nav-link {{ request()->is(['users/employee', 'users/employee/attendance', 'users/employee/commission']) ? 'active' : '' }}">
            <i class="fa fa-users fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Employee</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('employee.view')
                <li class="nav-item">
                    <a href="{{ route('users::employee::index') }}"
                        class="nav-link {{ request()->is(['users/employee']) ? 'active' : '' }}">List</a>
                </li>
            @endcan
            @can('employee commission.view')
                <li class="nav-item">
                    <a href="{{ route('users::employee::commission') }}"
                        class="nav-link {{ request()->is(['users/employee/commission']) ? 'active' : '' }}">Commission</a>
                </li>
            @endcan
            @can('employee attendance.view')
                <li class="nav-item">
                    <a href="{{ route('users::employee::attendance') }}"
                        class="nav-link {{ request()->is(['users/employee/attendance']) ? 'active' : '' }}">Attendance</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
