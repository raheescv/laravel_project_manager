@if (auth()->user()->can('package.view') || auth()->user()->can('package.create'))
    <li class="nav-item has-sub">
        <a href="#"
            class="mininav-toggle nav-link {{ request()->is(['package', 'package/create', 'package/edit/*']) ? 'active' : '' }}">
            <i class="fa fa-gift fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Package</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('package.create')
                <li class="nav-item">
                    <a href="{{ route('package::create') }}"
                        class="nav-link {{ request()->is(['package/create']) ? 'active' : '' }}">Create</a>
                </li>
            @endcan
            @can('package.view')
                <li class="nav-item">
                    <a href="{{ route('package::index') }}"
                        class="nav-link {{ request()->is(['package', 'package/edit/*']) ? 'active' : '' }}">List</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
