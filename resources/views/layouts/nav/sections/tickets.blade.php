@if (auth()->user()->can('ticket.view') || auth()->user()->can('ticket.create'))
    <li class="nav-item">
        <a href="{{ route('ticket::index') }}"
            class="nav-link mininav-toggle {{ request()->is(['ticket']) ? 'active' : '' }}">
            <i class="fa fa-ticket fs-5 me-2"></i>
            <span class="nav-label mininav-content ms-1 collapse show">
                Tickets
            </span>
        </a>
    </li>
@endif
