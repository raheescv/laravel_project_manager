@if (auth()->user()->can('sale.view') ||
        auth()->user()->can('sale.create') ||
        auth()->user()->can('report.sale item') ||
        auth()->user()->can('sale.receipts') ||
        auth()->user()->can('sales return.view') ||
        auth()->user()->can('sales return.create') ||
        auth()->user()->can('report.sale return item') ||
        auth()->user()->can('sales return.payments'))
    <li class="nav-item has-sub">
        @php
            $list = [
                'report/sale_summary',
                'report/sales_overview',
                'sale',
                'sale/create',
                'sale/pos',
                'sale/edit/*',
                'sale/view/*',
                'report/sale_item',
                'sale/receipts',
                'sale_return',
                'sale_return/create',
                'sale_return/edit/*',
                'sale_return/view/*',
                'sale_return/payments',
            ];
        @endphp
        <a href="#" class="mininav-toggle nav-link {{ request()->is($list) ? 'active' : '' }}">
            <i class="fa fa-shopping-cart fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Sale</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('sale.create')
                <li class="nav-item">
                    <a href="{{ route('sale::create') }}"
                        class="nav-link {{ request()->is(['sale/create', 'sale/pos']) ? 'active' : '' }}">Create</a>
                </li>
            @endcan
            @can('sale.view')
                <li class="nav-item">
                    <a href="{{ route('sale::index') }}"
                        class="nav-link {{ request()->is(['sale', 'sale/edit/*', 'sale/view/*']) ? 'active' : '' }}">List</a>
                </li>
            @endcan
            @can('report.sale item')
                <li class="nav-item">
                    <a href="{{ route('report::sale_item') }}"
                        class="nav-link {{ request()->is(['report/sale_item']) ? 'active' : '' }}">Item Wise Report</a>
                </li>
            @endcan
            @can('sale.receipts')
                <li class="nav-item">
                    <a href="{{ route('sale::receipts') }}"
                        class="nav-link {{ request()->is(['sale/receipts']) ? 'active' : '' }}">Receipts</a>
                </li>
            @endcan
            @can('sales return.create')
                <li class="nav-item">
                    <a href="{{ route('sale_return::create') }}"
                        class="nav-link {{ request()->is(['sale_return/create']) ? 'active' : '' }}">Return Create</a>
                </li>
            @endcan
            @can('sales return.view')
                <li class="nav-item">
                    <a href="{{ route('sale_return::index') }}"
                        class="nav-link {{ request()->is(['sale_return', 'sale_return/edit/*', 'sale_return/view/*']) ? 'active' : '' }}">Return List</a>
                </li>
            @endcan
            @can('sales return.payments')
                <li class="nav-item">
                    <a href="{{ route('sale_return::payments') }}"
                        class="nav-link {{ request()->is(['sale_return/payments']) ? 'active' : '' }}">Return Payments</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
