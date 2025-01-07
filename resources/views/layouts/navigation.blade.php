<nav id="mainnav-container" class="mainnav">
    <div class="mainnav__inner">
        <div class="mainnav__top-content scrollable-content pb-5">
            <div id="_dm-mainnavProfile" class="mainnav__widget my-3 hv-outline-parent">
                <div class="mininav-toggle text-center py-2">
                    <img class="mainnav__avatar img-md rounded-circle hv-oc" src="{{ asset('assets/img/profile-photos/1.png') }}" alt="Profile Picture">
                </div>
                <div class="mininav-content collapse d-mn-max">
                    <span data-popper-arrow class="arrow"></span>
                    <div class="d-grid">
                        <button class="mainnav-widget-toggle d-block btn border-0 p-2" data-bs-toggle="collapse" data-bs-target="#usernav" aria-expanded="false" aria-controls="usernav">
                            <span class="dropdown-toggle d-flex justify-content-center align-items-center">
                                <h5 class="mb-0 me-3">{{ auth()->user()->name }}</h5>
                            </span>
                            <small class="text-body-secondary">{{ getUserRoles(auth()->user()) }}</small>
                        </button>
                        <div id="usernav" class="nav flex-column collapse">
                            <a href="#" class="nav-link">
                                <i class="demo-pli-male fs-5 me-2"></i>
                                <span class="ms-1">Profile</span>
                            </a>
                            <a href="{{ route('settings::index') }}" class="nav-link">
                                <i class="demo-pli-gear fs-5 me-2"></i>
                                <span class="ms-1">Settings</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <ul class="mainnav__menu nav flex-column">
                <li class="nav-item">
                    <a href="{{ route('home') }}" class="nav-link mininav-toggle {{ request()->is(['/', 'dashboard']) ? 'active' : '' }}">
                        <i class="demo-pli-home fs-5 me-2"></i>
                        <span class="nav-label mininav-content ms-1 collapse show" style="">
                            Dashboard
                        </span>
                    </a>
                </li>
            </ul>
            <div class="mainnav__categoriy py-3">
                <ul class="mainnav__menu nav flex-column">
                    @if (auth()->user()->can('inventory.view'))
                        <li class="nav-item has-sub">
                            <a href="#" class="mininav-toggle nav-link {{ request()->is(['inventory', 'inventory/product/*']) ? 'active' : '' }}"><i
                                    class="demo-pli-split-vertical-2 fs-5 me-2"></i>
                                <span class="nav-label ms-1">Inventory</span>
                            </a>
                            <ul class="mininav-content nav collapse">
                                <li data-popper-arrow class="arrow"></li>
                                @can('inventory.view')
                                    <li class="nav-item">
                                        <a href="{{ route('inventory::index') }}" class="nav-link {{ request()->is(['inventory', 'inventory/product/*']) ? 'active' : '' }}">List</a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->can('sale.view'))
                        <li class="nav-item has-sub">
                            <a href="#" class="mininav-toggle nav-link {{ request()->is(['sale', 'sale/create', 'sale/edit/*']) ? 'active' : '' }}"><i
                                    class="demo-pli-split-vertical-2 fs-5 me-2"></i>
                                <span class="nav-label ms-1">Sale</span>
                            </a>
                            <ul class="mininav-content nav collapse">
                                <li data-popper-arrow class="arrow"></li>
                                @can('sale.create')
                                    <li class="nav-item">
                                        <a href="{{ route('sale::create') }}" class="nav-link {{ request()->is(['sale/create']) ? 'active' : '' }}">Create</a>
                                    </li>
                                @endcan
                                @can('sale.view')
                                    <li class="nav-item">
                                        <a href="{{ route('sale::index') }}" class="nav-link {{ request()->is(['sale', 'sale/edit/*']) ? 'active' : '' }}">List</a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->can('account.view'))
                        <li class="nav-item has-sub">
                            <a href="#" class="mininav-toggle nav-link {{ request()->is(['account', 'account/customer']) ? 'active' : '' }}"><i class="demo-pli-split-vertical-2 fs-5 me-2"></i>
                                <span class="nav-label ms-1">Account</span>
                            </a>
                            <ul class="mininav-content nav collapse">
                                <li data-popper-arrow class="arrow"></li>
                                @can('account.view')
                                    <li class="nav-item">
                                        <a href="{{ route('account::index') }}" class="nav-link {{ request()->is(['account']) ? 'active' : '' }}">Chart Of Account</a>
                                    </li>
                                @endcan
                                @can('customer.view')
                                    <li class="nav-item">
                                        <a href="{{ route('account::customer::index') }}" class="nav-link {{ request()->is(['account/customer']) ? 'active' : '' }}">Customers</a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->can('user.view') || auth()->user()->can('role.view'))
                        <li class="nav-item has-sub">
                            <a href="#" class="mininav-toggle nav-link {{ request()->is(['users', 'settings/roles', 'settings/roles/*']) ? 'active' : '' }}"><i
                                    class="demo-pli-split-vertical-2 fs-5 me-2"></i>
                                <span class="nav-label ms-1">Users</span>
                            </a>
                            <ul class="mininav-content nav collapse">
                                <li data-popper-arrow class="arrow"></li>
                                @can('user.view')
                                    <li class="nav-item">
                                        <a href="{{ route('users::index') }}" class="nav-link {{ request()->is(['users']) ? 'active' : '' }}">List</a>
                                    </li>
                                @endcan
                                @can('role.view')
                                    <li class="nav-item">
                                        <a href="{{ route('settings::roles::index') }}" class="nav-link {{ request()->is(['settings/roles', 'settings/roles/*']) ? 'active' : '' }}">Roles</a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
        <div class="mainnav__bottom-content border-top pb-2">
            <ul id="mainnav" class="mainnav__menu nav flex-column">
                <li class="nav-item has-sub">
                    <a href="#" class="nav-link mininav-toggle collapsed" aria-expanded="false">
                        <i class="demo-pli-unlock fs-5 me-2"></i>
                        <span class="nav-label ms-1">Logout</span>
                    </a>
                    <ul class="mininav-content nav flex-column collapse">
                        <li data-popper-arrow class="arrow"></li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
