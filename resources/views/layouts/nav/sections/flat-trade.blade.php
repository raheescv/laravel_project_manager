@if (auth()->user()->is_super_admin && auth()->user()->can('flat_trade.view'))
    <li class="nav-item has-sub">
        <a href="#" class="mininav-toggle nav-link {{ request()->is(['flat_trade/*']) ? 'active' : '' }}">
            <i class="fa fa-chart-line fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">FlatTrade</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('flat_trade.view')
                <li class="nav-item">
                    <a href="{{ route('flat_trade::dashboard') }}"
                        class="nav-link {{ request()->is(['flat_trade/dashboard']) ? 'active' : '' }}">Dashboard</a>
                </li>
            @endcan
            @can('flat_trade.view')
                <li class="nav-item">
                    <a href="{{ route('flat_trade::trades') }}"
                        class="nav-link {{ request()->is(['flat_trade/trades']) ? 'active' : '' }}">Trade History</a>
                </li>
            @endcan
            @can('flat_trade.connect')
                <li class="nav-item">
                    <a href="{{ route('flat_trade::connect') }}"
                        class="nav-link {{ request()->is(['flat_trade/connect']) ? 'active' : '' }}">Connect Account</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
