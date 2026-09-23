@can('student.view')
    <li class="nav-item has-sub">
        @php
            $list = ['student', 'student/*'];
        @endphp
        <a href="#" class="mininav-toggle nav-link {{ request()->is($list) ? 'active' : '' }}">
            <i class="fa fa-graduation-cap fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Students</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('student.create')
                <li class="nav-item">
                    <a href="{{ route('student::create') }}" class="nav-link {{ request()->is(['student/create']) ? 'active' : '' }}">Add Student</a>
                </li>
            @endcan
            <li class="nav-item">
                <a href="{{ route('student::index') }}" class="nav-link {{ request()->is(['student', 'student/view/*', 'student/edit/*']) ? 'active' : '' }}">Students</a>
            </li>
            @can('student guardian.view')
                <li class="nav-item">
                    <a href="{{ route('student::guardians') }}" class="nav-link {{ request()->is(['student/guardians']) ? 'active' : '' }}">Parents</a>
                </li>
            @endcan
            @can('student menu.view')
                <li class="nav-item">
                    <a href="{{ route('student::canteen-menu') }}" class="nav-link {{ request()->is(['student/canteen-menu']) ? 'active' : '' }}">Canteen Menu</a>
                </li>
            @endcan
            @can('report.student wallet')
                <li class="nav-item">
                    <a href="{{ route('student::report::wallet') }}" class="nav-link {{ request()->is(['student/report/wallet']) ? 'active' : '' }}">Wallet Report</a>
                </li>
            @endcan
            @can('report.student recharge')
                <li class="nav-item">
                    <a href="{{ route('student::report::recharges') }}" class="nav-link {{ request()->is(['student/report/recharges']) ? 'active' : '' }}">Online Recharges</a>
                </li>
            @endcan
        </ul>
    </li>
@endcan
