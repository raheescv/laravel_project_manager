@if (auth()->user()->can('issue.view') ||
        auth()->user()->can('issue.create') ||
        auth()->user()->can('report.issue item') ||
        auth()->user()->can('report.issue aging'))
    <li class="nav-item has-sub">
        @php
            $issueList = [
                'issue',
                'issue/create',
                'issue/create/*',
                'issue/edit/*',
                'issue/view/*',
                'report/issue_item',
                'report/issue_aging',
            ];
        @endphp
        <a href="#" class="mininav-toggle nav-link {{ request()->is($issueList) ? 'active' : '' }}">
            <i class="fa fa-share-square-o fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Issue</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('issue.create')
                <li class="nav-item">
                    <a href="{{ route('issue::create', ['type' => 'issue']) }}"
                        class="nav-link {{ request()->is(['issue/create', 'issue/create/issue']) ? 'active' : '' }}">Create Issue</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('issue::create', ['type' => 'return']) }}"
                        class="nav-link {{ request()->is(['issue/create/return']) ? 'active' : '' }}">Create Return</a>
                </li>
            @endcan
            @can('issue.view')
                <li class="nav-item">
                    <a href="{{ route('issue::index') }}"
                        class="nav-link {{ request()->is(['issue', 'issue/edit/*', 'issue/view/*']) && !request()->is(['issue/create']) ? 'active' : '' }}">List</a>
                </li>
            @endcan
            @can('report.issue item')
                <li class="nav-item">
                    <a href="{{ route('report::issue_item') }}"
                        class="nav-link {{ request()->is(['report/issue_item']) ? 'active' : '' }}">Item Wise Report</a>
                </li>
            @endcan
            @can('report.issue aging')
                <li class="nav-item">
                    <a href="{{ route('report::issue_aging') }}"
                        class="nav-link {{ request()->is(['report/issue_aging']) ? 'active' : '' }}">Aging Report</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
