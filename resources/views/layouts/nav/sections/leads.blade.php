@can('property lead.view')
    <li class="nav-item has-sub">
        @php
            $leadList = [
                'property/lead',
                'property/lead/*',
            ];
        @endphp
        <a href="#" class="mininav-toggle nav-link {{ request()->is($leadList) ? 'active' : '' }}">
            <i class="fa fa-filter fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Leads</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            <li class="nav-item">
                <a href="{{ route('property::lead::list') }}"
                    class="nav-link {{ request()->is(['property/lead', 'property/lead/list', 'property/lead/create', 'property/lead/edit/*']) ? 'active' : '' }}">
                    <i class="fa fa-list fs-6 me-2"></i>All Leads
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('property::lead::calendar') }}"
                    class="nav-link {{ request()->is(['property/lead/calendar']) ? 'active' : '' }}">
                    <i class="fa fa-calendar fs-6 me-2"></i>Lead Calendar
                </a>
            </li>
        </ul>
    </li>
@endcan
