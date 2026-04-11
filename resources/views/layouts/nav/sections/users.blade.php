@if (auth()->user()->can('user.view') || auth()->user()->can('role.view'))
    <li class="nav-item has-sub">
        <a href="#"
            class="mininav-toggle nav-link {{ request()->is(['users', 'users/view/*', 'settings/roles', 'settings/roles/*']) ? 'active' : '' }}">
            <i class="fa fa-user fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Users</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('user.view')
                <li class="nav-item">
                    <a href="{{ route('users::index') }}"
                        class="nav-link {{ request()->is(['users', 'users/view/*']) ? 'active' : '' }}">List</a>
                </li>
            @endcan
            @can('role.view')
                <li class="nav-item">
                    <a href="{{ route('settings::roles::index') }}"
                        class="nav-link {{ request()->is(['settings/roles', 'settings/roles/*']) ? 'active' : '' }}">Roles</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
