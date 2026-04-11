@if (auth()->user()->can('appointment.view'))
    <li class="nav-item has-sub">
        <a href="#"
            class="mininav-toggle nav-link {{ request()->is(['appointment/employee-calendar', 'appointment']) ? 'active' : '' }}">
            <i class="fa fa-calendar fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Appointments</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('appointment.view')
                <li class="nav-item">
                    <a href="{{ route('appointment::index') }}"
                        class="nav-link {{ request()->is(['appointment/employee-calendar']) ? 'active' : '' }}">Employee Calendar</a>
                </li>
            @endcan
            @can('appointment.view')
                <li class="nav-item">
                    <a href="{{ route('appointment::list') }}"
                        class="nav-link {{ request()->is(['appointment']) ? 'active' : '' }}">List</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
