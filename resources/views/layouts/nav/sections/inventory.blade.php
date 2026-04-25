@if (auth()->user()->can('inventory.view') ||
        auth()->user()->can('inventory.product search') ||
        auth()->user()->can('inventory.barcode cart') ||
        auth()->user()->can('inventory transfer.create') ||
        auth()->user()->can('report.product'))
    <li class="nav-item has-sub">
        @php
            $list = [
                'inventory',
                'inventory/stock-adjustment',
                'inventory/product/*',
                'inventory/search',
                'inventory/transfer',
                'inventory/barcode/cart',
                'inventory/transfer/edit/*',
                'inventory/transfer/create',
                'inventory/transfer/view/*',
                'report/product',
                'inventory/stock-check',
                'inventory/stock-check/*',
            ];
        @endphp
        <a href="#" class="mininav-toggle nav-link {{ request()->is($list) ? 'active' : '' }}">
            <i class="fa fa-cubes fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Inventory</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('inventory.view')
                <li class="nav-item">
                    <a href="{{ route('inventory::index') }}"
                        class="nav-link {{ request()->is(['inventory', 'inventory/product/*', 'inventory/stock-adjustment']) ? 'active' : '' }}">List</a>
                </li>
            @endcan
            @can('inventory.product search')
                <li class="nav-item">
                    <a href="{{ route('inventory::search') }}"
                        class="nav-link {{ request()->is(['inventory/search']) ? 'active' : '' }}">Product Search</a>
                </li>
            @endcan
            @can('inventory.barcode cart')
                <li class="nav-item">
                    <a href="{{ route('inventory::barcode::cart::index') }}"
                        class="nav-link {{ request()->is(['inventory/barcode/cart']) ? 'active' : '' }}">Barcode Cart</a>
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
                    <a href="{{ route('report::product') }}"
                        class="nav-link {{ request()->is(['report/product']) ? 'active' : '' }}">Product Check</a>
                </li>
            @endcan
            @can('inventory.stock check')
                <li class="nav-item">
                    <a href="{{ route('inventory::stock-check::index') }}"
                        class="nav-link {{ request()->is(['inventory/stock-check', 'inventory/stock-check/*']) ? 'active' : '' }}">Stock Check</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
