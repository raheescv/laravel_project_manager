@if (auth()->user()->is_super_admin)
    <li class="nav-item">
        <a href="{{ route('tenants::index') }}"
            class="nav-link mininav-toggle {{ request()->is(['tenants']) ? 'active' : '' }}">
            <i class="fa fa-building fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">
                Tenants
            </span>
        </a>
    </li>
@endif
