@if (auth()->user()->can('account.view'))
    <li class="nav-item has-sub">
        <a href="#"
            class="mininav-toggle nav-link {{ request()->is(['account', 'account/expense', 'account/income', 'account/general-voucher', 'account/cheque*', 'account/view/*', 'report/day_book', 'account/bank-reconciliation']) ? 'active' : '' }}">
            <i class="fa fa-bank fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">Account</span>
        </a>
        <ul class="mininav-content nav collapse">
            <li data-popper-arrow class="arrow"></li>
            @can('account.view')
                <li class="nav-item">
                    <a href="{{ route('account::index') }}"
                        class="nav-link {{ request()->is(['account', 'account/view/*']) ? 'active' : '' }}">Chart Of Account</a>
                </li>
            @endcan
            @can('expense.view')
                <li class="nav-item">
                    <a href="{{ route('account::expense::index') }}"
                        class="nav-link {{ request()->is(['account/expense']) ? 'active' : '' }}">Expense</a>
                </li>
            @endcan
            @can('income.view')
                <li class="nav-item">
                    <a href="{{ route('account::income::index') }}"
                        class="nav-link {{ request()->is(['account/income']) ? 'active' : '' }}">Income</a>
                </li>
            @endcan
            @can('general voucher.view')
                <li class="nav-item">
                    <a href="{{ route('account::general-voucher::index') }}"
                        class="nav-link {{ request()->is(['account/general-voucher']) ? 'active' : '' }}">General Voucher</a>
                </li>
            @endcan
            @can('report.day book')
                <li class="nav-item">
                    <a href="{{ route('report::day_book') }}"
                        class="nav-link {{ request()->is(['report/day_book']) ? 'active' : '' }}">Day Book</a>
                </li>
            @endcan
            @can('report.bank reconciliation report')
                <li class="nav-item">
                    <a href="{{ route('account::bank-reconciliation::index') }}"
                        class="nav-link {{ request()->is(['account/bank-reconciliation']) ? 'active' : '' }}">Bank Reconciliation</a>
                </li>
            @endcan
        </ul>
    </li>
@endif
