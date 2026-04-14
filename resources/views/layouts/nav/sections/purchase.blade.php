@if (auth()->user()->can('purchase.view') ||
        auth()->user()->can('purchase.create') ||
        auth()->user()->can('report.purchase item') ||
        auth()->user()->can('purchase.payments') ||
        auth()->user()->can('purchase return.view') ||
        auth()->user()->can('purchase return.create') ||
        auth()->user()->can('report.purchase return item') ||
        auth()->user()->can('purchase return.payments'))
    <li class="nav-item has-sub">
        <a href="#"
            class="mininav-toggle nav-link {{ request()->is(['purchase', 'purchase/create', 'purchase/edit/*', 'report/purchase_item', 'purchase/payments', 'purchase_return', 'purchase_return/create', 'purchase_return/edit/*', 'purchase_return/view/*', 'report/purchase_return_item', 'purchase_return/payments']) ? 'active' : '' }}">
            <i class="fa fa-cart-plus fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Purchase</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('purchase.create')
                <li class="nav-item">
                    <a href="{{ route('purchase::create') }}"
                        class="nav-link {{ request()->is(['purchase/create']) ? 'active' : '' }}">Create</a>
                </li>
            @endcan
            @can('purchase.view')
                <li class="nav-item">
                    <a href="{{ route('purchase::index') }}"
                        class="nav-link {{ request()->is(['purchase', 'purchase/edit/*']) ? 'active' : '' }}">List</a>
                </li>
            @endcan
            @can('report.purchase item')
                <li class="nav-item">
                    <a href="{{ route('report::purchase_item') }}"
                        class="nav-link {{ request()->is(['report/purchase_item']) ? 'active' : '' }}">Item Wise Report</a>
                </li>
            @endcan
            @can('purchase.payments')
                <li class="nav-item">
                    <a href="{{ route('purchase::payments') }}"
                        class="nav-link {{ request()->is(['purchase/payments']) ? 'active' : '' }}">Payments</a>
                </li>
            @endcan
            @can('purchase return.create')
                <li class="nav-item">
                    <a href="{{ route('purchase_return::create') }}"
                        class="nav-link {{ request()->is(['purchase_return/create']) ? 'active' : '' }}">Return Create</a>
                </li>
            @endcan
            @can('purchase return.view')
                <li class="nav-item">
                    <a href="{{ route('purchase_return::index') }}"
                        class="nav-link {{ request()->is(['purchase_return', 'purchase_return/edit/*', 'purchase_return/view/*']) ? 'active' : '' }}">Return List</a>
                </li>
            @endcan
            @can('report.purchase return item')
                <li class="nav-item">
                    <a href="{{ route('report::purchase_return_item') }}"
                        class="nav-link {{ request()->is(['report/purchase_return_item']) ? 'active' : '' }}">Return Item Wise Report</a>
                </li>
            @endcan
            @can('purchase return.payments')
                <li class="nav-item">
                    <a href="{{ route('purchase_return::payments') }}"
                        class="nav-link {{ request()->is(['purchase_return/payments']) ? 'active' : '' }}">Return Payments</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
