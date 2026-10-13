@if (auth()->user()->can('project contract.view') || auth()->user()->can('project contract.report'))
    <li class="nav-item has-sub">
        @php
            $list = ['project-contract', 'project-contract/*'];
        @endphp
        <a href="#" class="mininav-toggle nav-link {{ request()->is($list) ? 'active' : '' }}">
            <i class="fa fa-file-text-o fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Agreements</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('project contract.create')
                <li class="nav-item">
                    <a href="{{ route('project-contract::create') }}" class="nav-link {{ request()->is(['project-contract/create']) ? 'active' : '' }}">New Contract</a>
                </li>
            @endcan
            @can('project contract.view')
                <li class="nav-item">
                    <a href="{{ route('project-contract::index') }}" class="nav-link {{ request()->is(['project-contract', 'project-contract/view/*', 'project-contract/edit/*']) ? 'active' : '' }}">Contracts</a>
                </li>
            @endcan
            @can('project contract.report')
                <li class="nav-item">
                    <a href="{{ route('project-contract::report::pending') }}" class="nav-link {{ request()->is(['project-contract/reports/pending']) ? 'active' : '' }}">Pending Payments</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('project-contract::report::paid') }}" class="nav-link {{ request()->is(['project-contract/reports/paid']) ? 'active' : '' }}">Paid Payments</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
