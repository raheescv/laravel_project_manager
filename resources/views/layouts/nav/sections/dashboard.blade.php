<li class="nav-item">
    <a href="{{ route('home') }}" class="nav-link mininav-toggle {{ request()->is(['/', 'dashboard']) ? 'active' : '' }}">
        <i class="fa fa-tachometer fs-5 me-2"></i>
        <span class="nav-label mininav-content ms-1 collapse show">
            Dashboard
        </span>
    </a>
</li>
