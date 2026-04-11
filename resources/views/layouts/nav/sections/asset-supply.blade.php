@if (auth()->user()->can('supply request.view'))
    <li class="nav-item has-sub">
        <a href="#"
            class="mininav-toggle nav-link {{ request()->routeIs(['supply-request::*', 'supply-return::*']) ? 'active' : '' }}">
            <i class="fa fa-truck fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Asset Supply</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            <li class="nav-item">
                <a href="{{ route('supply-request::index') }}"
                    class="nav-link {{ request()->routeIs('supply-request::*') ? 'active' : '' }}">
                    Supply List
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('supply-return::index') }}"
                    class="nav-link {{ request()->routeIs('supply-return::*') ? 'active' : '' }}">
                    Supply Return List
                </a>
            </li>
        </ul>
    </li>
@endif
