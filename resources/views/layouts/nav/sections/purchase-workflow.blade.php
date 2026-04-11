@if (auth()->user()->can('purchase request.view') || auth()->user()->can('local purchase order.view') || auth()->user()->can('grn.view') || auth()->user()->can('lpo-purchase.view'))
    <li class="nav-item has-sub">
        <a href="#"
            class="mininav-toggle nav-link {{ request()->routeIs(['purchase-request::*', 'lpo::*', 'grn::*', 'lpo-purchase::*', 'purchase-vendor::*']) ? 'active' : '' }}">
            <i class="fa fa-user fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Purchase Workflow</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            <li class="nav-item">
                @php
                    $purchaseRequest = [
                        'purchase-requests',
                        'purchase-requests/create',
                        'purchase-requests/*/edit',
                        'purchase-requests/*/decision',
                        'purchase-requests/*',
                    ];
                @endphp
                <a href="{{ route('purchase-request::index') }}"
                    class="nav-link {{ request()->is($purchaseRequest) ? 'active' : '' }}">
                    Purchase Requests
                </a>
            </li>
            <li class="nav-item">
                @php
                    $lpo = ['local-purchase-orders', 'local-purchase-orders/create', 'local-purchase-orders/*'];
                @endphp
                <a href="{{ route('lpo::index') }}" class="nav-link {{ request()->is($lpo) ? 'active' : '' }}">
                    LPO
                </a>
            </li>
            <li class="nav-item">
                @php
                    $grn = ['grns', 'grns/create', 'grns/*'];
                @endphp
                <a href="{{ route('grn::index') }}" class="nav-link {{ request()->is($grn) ? 'active' : '' }}">
                    GRN
                </a>
            </li>
            <li class="nav-item">
                @php
                    $lpoPurchase = ['lpo-purchases', 'lpo-purchases/create', 'lpo-purchases/*'];
                @endphp
                <a href="{{ route('lpo-purchase::index') }}" class="nav-link {{ request()->is($lpoPurchase) ? 'active' : '' }}">
                    LPO Invoice
                </a>
            </li>
            <li class="nav-item">
                @php
                    $purchaseVendor = ['purchase-vendors', 'purchase-vendors/*'];
                @endphp
                <a href="{{ route('purchase-vendor::index') }}" class="nav-link {{ request()->is($purchaseVendor) ? 'active' : '' }}">
                    Vendors
                </a>
            </li>
        </ul>
    </li>
@endif
