@if (auth()->user()->can('log.inventory'))
    <li class="nav-item has-sub">
        <a href="#"
            class="mininav-toggle nav-link {{ request()->is(['log/inventory', 'log/jobs', 'log/failed-jobs', 'api_log', 'visitor-analytics', 'health', 'log-viewer', 'log-viewer/*']) ? 'active' : '' }}">
            <i class="fa fa-clipboard fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Log</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('api_log.view')
                <li class="nav-item">
                    <a href="{{ route('api_log::index') }}"
                        class="nav-link {{ request()->is(['api_log']) ? 'active' : '' }}">Api Log</a>
                </li>
            @endcan
            <li class="nav-item">
                <a href="{{ route('log::jobs') }}"
                    class="nav-link {{ request()->is(['log/jobs']) ? 'active' : '' }}">Jobs</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('log::failed_jobs') }}"
                    class="nav-link {{ request()->is(['log/failed-jobs']) ? 'active' : '' }}">Failed Jobs</a>
            </li>
            @can('log.inventory')
                <li class="nav-item">
                    <a href="{{ route('log::inventory') }}"
                        class="nav-link {{ request()->is(['log/inventory']) ? 'active' : '' }}">Inventory</a>
                </li>
            @endcan
            @can('visitor analytics.view')
                <li class="nav-item">
                    <a href="{{ route('visitor-analytics') }}"
                        class="nav-link {{ request()->is(['visitor-analytics']) ? 'active' : '' }}">Visitor Analytics</a>
                </li>
            @endcan
            @can('system health.view')
                <li class="nav-item">
                    <a href="{{ route('health') }}"
                        class="nav-link {{ request()->is(['health']) ? 'active' : '' }}">System Health</a>
                </li>
            @endcan
            @can('log.log viewer')
                <li class="nav-item">
                    <a href="{{ url('/log-viewer') }}"
                        class="nav-link {{ request()->is(['log-viewer', 'log-viewer/*']) ? 'active' : '' }}">Log Viewer</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
