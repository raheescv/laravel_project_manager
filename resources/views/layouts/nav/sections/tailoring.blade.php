@if (auth()->user()->can('tailoring order.view') || auth()->user()->can('tailoring job completion.view'))
    <li class="nav-item has-sub">
        @php
            $list = [
                'tailoring/order',
                'tailoring/order/create',
                'tailoring/order/edit/*',
                'tailoring/order/*',
                'tailoring/order-management',
                'tailoring/receipts',
                'tailoring/job-completion',
                'tailoring/job-completion/create',
                'report/tailoring_order_item',
                'report/tailoring_order_item_tailor',
                'report/tailoring_non_delivery',
            ];
        @endphp
        <a href="#" class="mininav-toggle nav-link {{ request()->is($list) ? 'active' : '' }}">
            <i class="fa fa-cut fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Tailoring</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('tailoring order.create')
                <li class="nav-item">
                    <a href="{{ route('tailoring::order::create') }}"
                        class="nav-link {{ request()->is(['tailoring/order/create']) ? 'active' : '' }}">Create Order</a>
                </li>
            @endcan
            @can('tailoring order.view')
                <li class="nav-item">
                    <a href="{{ route('tailoring::order::index') }}"
                        class="nav-link {{ request()->is(['tailoring/order', 'tailoring/order/edit/*', 'tailoring/order/*']) && !request()->is(['tailoring/order/create', 'tailoring/order-management']) ? 'active' : '' }}">Orders</a>
                </li>
            @endcan
            @can('tailoring order.view')
                <li class="nav-item">
                    <a href="{{ route('tailoring::order-management::index') }}"
                        class="nav-link {{ request()->is(['tailoring/order-management']) ? 'active' : '' }}">Order Management</a>
                </li>
            @endcan
            @can('tailoring job completion.view')
                <li class="nav-item">
                    <a href="{{ route('tailoring::job-completion::index') }}"
                        class="nav-link {{ request()->is(['tailoring/job-completion', 'tailoring/job-completion/create']) ? 'active' : '' }}">Job Completion</a>
                </li>
            @endcan
            @can('report.tailoring order item')
                <li class="nav-item">
                    <a href="{{ route('report::tailoring_order_item') }}"
                        class="nav-link {{ request()->is(['report/tailoring_order_item']) ? 'active' : '' }}">Item Wise Report</a>
                </li>
            @endcan
            @can('report.tailoring order item tailor')
                <li class="nav-item">
                    <a href="{{ route('report::tailoring_order_item_tailor') }}"
                        class="nav-link {{ request()->is(['report/tailoring_order_item_tailor']) ? 'active' : '' }}">Tailor Wise Report</a>
                </li>
            @endcan
            @can('report.tailoring non delivery')
                <li class="nav-item">
                    <a href="{{ route('report::tailoring_non_delivery') }}"
                        class="nav-link {{ request()->is(['report/tailoring_non_delivery']) ? 'active' : '' }}">Non-Delivery Report</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
