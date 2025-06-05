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
                            <a href="{{ route('profile.edit') }}" class="nav-link">
                                <i class="fa fa-user fs-5 me-2"></i>
                                <span class="ms-1">Profile</span>
                            </a>
                            <a href="{{ route('settings::index') }}" class="nav-link">
                                <i class="fa fa-cog fs-5 me-2"></i>
                                <span class="ms-1">Settings</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <ul class="mainnav__menu nav flex-column">
                <li class="nav-item">
                    <a href="{{ route('home') }}" class="nav-link mininav-toggle {{ request()->is(['/', 'dashboard']) ? 'active' : '' }}">
                        <i class="fa fa-dashboard fs-5 me-2"></i>
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
                                class="mininav-toggle nav-link {{ request()->is(['inventory', 'inventory/product/*', 'inventory/transfer', 'inventory/transfer/edit/*', 'inventory/transfer/create', 'inventory/transfer/view/*', 'report/product']) ? 'active' : '' }}"><i
                                    class="fa fa-cubes fs-5 me-2"></i>
                                <span class="nav-label mininav-content ms-1 collapse show" style="">Inventory</span>
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
                                @can('report.product')
                                    <li class="nav-item">
                                        <a href="{{ route('report::product') }}" class="nav-link {{ request()->is(['report/product']) ? 'active' : '' }}">
                                            Product Check
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->can('appointment.view'))
                        <li class="nav-item has-sub">
                            <a href="#" class="mininav-toggle nav-link {{ request()->is(['appointment/employee-calendar', 'appointment']) ? 'active' : '' }}"><i
                                    class="fa fa-calendar fs-5 me-2"></i>
                                <span class="nav-label mininav-content ms-1 collapse show" style="">Appointments</span>
                            </a>
                            <ul class="mininav-content nav collapse">
                                <li data-popper-arrow class="arrow"></li>
                                @can('appointment.view')
                                    <li class="nav-item">
                                        <a href="{{ route('appointment::index') }}" class="nav-link {{ request()->is(['appointment/employee-calendar']) ? 'active' : '' }}">Employee Calendar</a>
                                    </li>
                                @endcan
                                @can('appointment.view')
                                    <li class="nav-item">
                                        <a href="{{ route('appointment::list') }}" class="nav-link {{ request()->is(['appointment']) ? 'active' : '' }}"> List </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->can('sale.view') || auth()->user()->can('report.sale item'))
                        <li class="nav-item has-sub">
                            @php
                                $list = ['report/sale_summary', 'report/sales_overview', 'sale', 'sale/create', 'sale/edit/*', 'sale/view/*', 'report/sale_item', 'sale/receipts'];
                            @endphp
                            <a href="#" class="mininav-toggle nav-link {{ request()->is($list) ? 'active' : '' }}"><i class="fa fa-shopping-cart fs-5 me-2"></i>
                                <span class="nav-label mininav-content ms-1 collapse show" style="">Sale</span>
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
                                <i class="fa fa-rotate-left fs-5 me-2"></i>
                                <span class="nav-label mininav-content ms-1 collapse show" style="">Sale Return</span>
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
                                    class="fa fa-cart-plus fs-5 me-2"></i>
                                <span class="nav-label mininav-content ms-1 collapse show" style="">Purchase</span>
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
                    @if (auth()->user()->can('purchase return.view') || auth()->user()->can('report.purchase return item'))
                        <li class="nav-item has-sub">
                            <a href="#"
                                class="mininav-toggle nav-link {{ request()->is(['purchase_return', 'purchase_return/create', 'purchase_return/edit/*', 'purchase_return/view/*', 'report/purchase_item', 'purchase_return/payments']) ? 'active' : '' }}"><i
                                    class="fa fa-reply fs-5 me-2"></i>
                                <span class="nav-label mininav-content ms-1 collapse show" style="">Purchase Return</span>
                            </a>
                            <ul class="mininav-content nav collapse">
                                <li data-popper-arrow class="arrow"></li>
                                @can('purchase return.create')
                                    <li class="nav-item">
                                        <a href="{{ route('purchase_return::create') }}" class="nav-link {{ request()->is(['purchase_return/create']) ? 'active' : '' }}">Create</a>
                                    </li>
                                @endcan
                                @can('purchase return.view')
                                    <li class="nav-item">
                                        <a href="{{ route('purchase_return::index') }}"
                                            class="nav-link {{ request()->is(['purchase_return', 'purchase_return/edit/*', 'purchase_return/view/*']) ? 'active' : '' }}">List</a>
                                    </li>
                                @endcan
                                @can('report.purchase return item')
                                    <li class="nav-item">
                                        <a href="{{ route('report::purchase_item') }}" class="nav-link {{ request()->is(['report/purchase_item']) ? 'active' : '' }}">Item Wise Report</a>
                                    </li>
                                @endcan
                                @can('purchase return.payments')
                                    <li class="nav-item">
                                        <a href="{{ route('purchase_return::payments') }}" class="nav-link {{ request()->is(['purchase_return/payments']) ? 'active' : '' }}">Payments</a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->can('account.view'))
                        <li class="nav-item has-sub">
                            <a href="#"
                                class="mininav-toggle nav-link {{ request()->is(['account', 'account/expense', 'account/income', 'account/view/*', 'report/day_book']) ? 'active' : '' }}"><i
                                    class="fa fa-bank fs-5 me-2"></i>
                                <span class="nav-label mininav-content ms-1 collapse show" style="">Account</span>
                            </a>
                            <ul class="mininav-content nav collapse">
                                <li data-popper-arrow class="arrow"></li>
                                @can('account.view')
                                    <li class="nav-item">
                                        <a href="{{ route('account::index') }}" class="nav-link {{ request()->is(['account', 'account/view/*']) ? 'active' : '' }}">Chart Of Account</a>
                                    </li>
                                @endcan
                                @can('expense.view')
                                    <li class="nav-item">
                                        <a href="{{ route('account::expense::index') }}" class="nav-link {{ request()->is(['account/expense']) ? 'active' : '' }}">Expense</a>
                                    </li>
                                @endcan
                                @can('income.view')
                                    <li class="nav-item">
                                        <a href="{{ route('account::income::index') }}" class="nav-link {{ request()->is(['account/income']) ? 'active' : '' }}">Income</a>
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
                            <a href="#" class="mininav-toggle nav-link {{ request()->is(['users/employee', 'users/employee/attendance']) ? 'active' : '' }}"><i
                                    class="fa fa-users fs-5 me-2"></i>
                                <span class="nav-label mininav-content ms-1 collapse show" style="">Employee</span>
                            </a>
                            <ul class="mininav-content nav collapse">
                                <li data-popper-arrow class="arrow"></li>
                                @can('employee.view')
                                    <li class="nav-item">
                                        <a href="{{ route('users::employee::index') }}" class="nav-link {{ request()->is(['users/employee']) ? 'active' : '' }}">List</a>
                                    </li>
                                @endcan
                                @can('employee attendance.view')
                                    <li class="nav-item">
                                        <a href="{{ route('users::employee::attendance') }}" class="nav-link {{ request()->is(['users/employee/attendance']) ? 'active' : '' }}">Attendance</a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->can('user.view') || auth()->user()->can('role.view'))
                        <li class="nav-item has-sub">
                            <a href="#" class="mininav-toggle nav-link {{ request()->is(['users', 'users/view/*', 'settings/roles', 'settings/roles/*']) ? 'active' : '' }}"><i
                                    class="fa fa-user fs-5 me-2"></i>
                                <span class="nav-label mininav-content ms-1 collapse show" style="">Users</span>
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
                            <a href="#" class="mininav-toggle nav-link {{ request()->is(['log/inventory', 'visitor-analytics', 'health']) ? 'active' : '' }}"><i
                                    class="fa fa-clipboard fs-5 me-2"></i>
                                <span class="nav-label mininav-content ms-1 collapse show" style="">Log</span>
                            </a>
                            <ul class="mininav-content nav collapse">
                                <li data-popper-arrow class="arrow"></li>
                                @can('log.inventory')
                                    <li class="nav-item">
                                        <a href="{{ route('log::inventory') }}" class="nav-link {{ request()->is(['log/inventory']) ? 'active' : '' }}">Inventory</a>
                                    </li>
                                @endcan
                                @can('visitor analytics.view')
                                    <li class="nav-item">
                                        <a href="{{ route('visitor-analytics') }}" class="nav-link {{ request()->is(['visitor-analytics']) ? 'active' : '' }}">Visitor Analytics</a>
                                    </li>
                                @endcan
                                @can('system health.view')
                                    <li class="nav-item">
                                        <a href="{{ route('health') }}" class="nav-link {{ request()->is(['health']) ? 'active' : '' }}">System Health</a>
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
                        <i class="fa fa-sign-out fs-5 me-2"></i>
                        <span class="nav-label mininav-content ms-1 collapse show" style="">Logout</span>
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
