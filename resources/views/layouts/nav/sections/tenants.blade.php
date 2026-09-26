@if (auth()->user()->is_super_admin)
    @php($amcDueCount = \App\Models\Tenant::where('is_active', true)->whereDate('renews_on', '<=', today()->addDays(\App\Models\Tenant::RENEWAL_WARNING_DAYS))->count())
    <li class="nav-item has-sub">
        <a href="#"
            class="mininav-toggle nav-link {{ request()->is(['tenants', 'tenants/view/*', 'tenants/amc-reminders']) ? 'active' : '' }}">
            <i class="fa fa-building fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Tenants</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            <li class="nav-item">
                <a href="{{ route('tenants::index') }}"
                    class="nav-link {{ request()->is(['tenants', 'tenants/view/*']) ? 'active' : '' }}">List</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('tenants::amc-reminders') }}"
                    class="nav-link {{ request()->is('tenants/amc-reminders') ? 'active' : '' }}">
                    AMC Reminders
                    @if ($amcDueCount)
                        <span class="badge bg-danger ms-1">{{ $amcDueCount }}</span>
                    @endif
                </a>
            </li>
        </ul>
    </li>
@endif
