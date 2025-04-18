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
                                <h5 class="mb-0 me-3">{{ auth()->user()->name }} </h5>
                            </span>
                            <small class="text-body-secondary">{{ getUserRoles(auth()->user()) }}</small>
                            <p><small class="text-body-secondary">{{ auth()->user()->branch?->name }}</small></p>

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
                            <a href="#"
                                class="mininav-toggle nav-link {{ request()->is(['inventory', 'inventory/product/*', 'inventory/transfer', 'inventory/transfer/edit/*', 'inventory/transfer/create', 'inventory/transfer/view/*']) ? 'active' : '' }}"><i
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
                                @can('inventory transfer.create')
                                    <li class="nav-item">
                                        <a href="{{ route('inventory::transfer::index') }}"
                                            class="nav-link {{ request()->is(['inventory/transfer', 'inventory/transfer/edit/*', 'inventory/transfer/create', 'inventory/transfer/view/*']) ? 'active' : '' }}">
                                            Inventory Transfer
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->can('sale.view') || auth()->user()->can('report.sale item'))
                        <li class="nav-item has-sub">
                            <a href="#"
                                class="mininav-toggle nav-link {{ request()->is(['sale', 'sale/create', 'sale/edit/*', 'sale/view/*', 'report/sale_item', 'sale/receipts']) ? 'active' : '' }}"><i
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
                                        <a href="{{ route('sale::index') }}" class="nav-link {{ request()->is(['sale', 'sale/edit/*', 'sale/view/*']) ? 'active' : '' }}">List</a>
                                    </li>
                                @endcan
                                @can('report.sale item')
                                    <li class="nav-item">
                                        <a href="{{ route('report::sale_item') }}" class="nav-link {{ request()->is(['report/sale_item']) ? 'active' : '' }}">Item Wise Report</a>
                                    </li>
                                @endcan
                                @can('sale.receipts')
                                    <li class="nav-item">
                                        <a href="{{ route('sale::receipts') }}" class="nav-link {{ request()->is(['sale/receipts']) ? 'active' : '' }}">Receipts</a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->can('sales return.view') || auth()->user()->can('report.sale return item'))
                        <li class="nav-item has-sub">
                            <a href="#"
                                class="mininav-toggle nav-link {{ request()->is(['sale_return', 'sale_return/create', 'sale_return/edit/*', 'sale_return/view/*', 'report/sale_return_item', 'sale_return/payments']) ? 'active' : '' }}">
                                <i class="demo-pli-split-vertical-2 fs-5 me-2"></i>
                                <span class="nav-label ms-1">Sale Return</span>
                            </a>
                            <ul class="mininav-content nav collapse">
                                <li data-popper-arrow class="arrow"></li>
                                @can('sales return.create')
                                    <li class="nav-item">
                                        <a href="{{ route('sale_return::create') }}" class="nav-link {{ request()->is(['sale_return/create']) ? 'active' : '' }}">Create</a>
                                    </li>
                                @endcan
                                @can('sales return.view')
                                    <li class="nav-item">
                                        <a href="{{ route('sale_return::index') }}"
                                            class="nav-link {{ request()->is(['sale_return', 'sale_return/edit/*', 'sale_return/view/*']) ? 'active' : '' }}">List</a>
                                    </li>
                                @endcan
                                @can('report.sale return item')
                                    <li class="nav-item">
                                        <a href="{{ route('report::sale_return_item') }}" class="nav-link {{ request()->is(['report/sale_return_item']) ? 'active' : '' }}">Item Wise Report</a>
                                    </li>
                                @endcan
                                @can('sales return.payments')
                                    <li class="nav-item">
                                        <a href="{{ route('sale_return::payments') }}" class="nav-link {{ request()->is(['sale/receipts']) ? 'active' : '' }}">Payments</a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->can('purchase.view') || auth()->user()->can('report.purchase item'))
                        <li class="nav-item has-sub">
                            <a href="#"
                                class="mininav-toggle nav-link {{ request()->is(['purchase', 'purchase/create', 'purchase/edit/*', 'report/purchase_item', 'purchase/payments']) ? 'active' : '' }}"><i
                                    class="demo-pli-split-vertical-2 fs-5 me-2"></i>
                                <span class="nav-label ms-1">Purchase</span>
                            </a>
                            <ul class="mininav-content nav collapse">
                                <li data-popper-arrow class="arrow"></li>
                                @can('purchase.create')
                                    <li class="nav-item">
                                        <a href="{{ route('purchase::create') }}" class="nav-link {{ request()->is(['purchase/create']) ? 'active' : '' }}">Create</a>
                                    </li>
                                @endcan
                                @can('purchase.view')
                                    <li class="nav-item">
                                        <a href="{{ route('purchase::index') }}" class="nav-link {{ request()->is(['purchase', 'purchase/edit/*']) ? 'active' : '' }}">List</a>
                                    </li>
                                @endcan
                                @can('report.purchase item')
                                    <li class="nav-item">
                                        <a href="{{ route('report::purchase_item') }}" class="nav-link {{ request()->is(['report/purchase_item']) ? 'active' : '' }}">Item Wise Report</a>
                                    </li>
                                @endcan
                                @can('purchase.payments')
                                    <li class="nav-item">
                                        <a href="{{ route('purchase::payments') }}" class="nav-link {{ request()->is(['purchase/payments']) ? 'active' : '' }}">Payments</a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->can('account.view'))
                        <li class="nav-item has-sub">
                            <a href="#"
                                class="mininav-toggle nav-link {{ request()->is(['account', 'account/customer', 'account/customer/view/*', 'account/vendor', 'report/day_book']) ? 'active' : '' }}"><i
                                    class="demo-pli-split-vertical-2 fs-5 me-2"></i>
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
                                        <a href="{{ route('account::customer::index') }}"
                                            class="nav-link {{ request()->is(['account/customer', 'account/customer/view/*']) ? 'active' : '' }}">Customers</a>
                                    </li>
                                @endcan
                                @can('vendor.view')
                                    <li class="nav-item">
                                        <a href="{{ route('account::vendor::index') }}" class="nav-link {{ request()->is(['account/vendor']) ? 'active' : '' }}">Vendors</a>
                                    </li>
                                @endcan
                                @can('report.day book')
                                    <li class="nav-item">
                                        <a href="{{ route('report::day_book') }}" class="nav-link {{ request()->is(['report/day_book']) ? 'active' : '' }}">Day Book</a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->can('employee.view'))
                        <li class="nav-item has-sub">
                            <a href="#" class="mininav-toggle nav-link {{ request()->is(['users/employee', 'users/employee/view/*']) ? 'active' : '' }}"><i
                                    class="demo-pli-split-vertical-2 fs-5 me-2"></i>
                                <span class="nav-label ms-1">Employees</span>
                            </a>
                            <ul class="mininav-content nav collapse">
                                <li data-popper-arrow class="arrow"></li>
                                @can('employee.view')
                                    <li class="nav-item">
                                        <a href="{{ route('users::employee::index') }}" class="nav-link {{ request()->is(['users/employee', 'users/employee/view/*']) ? 'active' : '' }}">List</a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->can('user.view') || auth()->user()->can('role.view'))
                        <li class="nav-item has-sub">
                            <a href="#" class="mininav-toggle nav-link {{ request()->is(['users', 'users/view/*', 'settings/roles', 'settings/roles/*']) ? 'active' : '' }}"><i
                                    class="demo-pli-split-vertical-2 fs-5 me-2"></i>
                                <span class="nav-label ms-1">Users</span>
                            </a>
                            <ul class="mininav-content nav collapse">
                                <li data-popper-arrow class="arrow"></li>
                                @can('user.view')
                                    <li class="nav-item">
                                        <a href="{{ route('users::index') }}" class="nav-link {{ request()->is(['users', 'users/view/*']) ? 'active' : '' }}">List</a>
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
                    @if (auth()->user()->can('log.inventory'))
                        <li class="nav-item has-sub">
                            <a href="#" class="mininav-toggle nav-link {{ request()->is(['log/inventory']) ? 'active' : '' }}"><i class="demo-pli-split-vertical-2 fs-5 me-2"></i>
                                <span class="nav-label ms-1">Log</span>
                            </a>
                            <ul class="mininav-content nav collapse">
                                <li data-popper-arrow class="arrow"></li>
                                @can('log.inventory')
                                    <li class="nav-item">
                                        <a href="{{ route('log::inventory') }}" class="nav-link {{ request()->is(['log/inventory']) ? 'active' : '' }}">Inventory</a>
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
