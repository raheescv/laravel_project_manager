{{-- List · Board · Calendar switch shown in the header of each lead view. Pass $active. --}}
<div class="btn-group shadow-sm" role="group" aria-label="Lead views">
    <a href="{{ route('property::lead::list') }}" class="btn btn-light {{ $active === 'list' ? 'active' : '' }}" @if ($active === 'list') aria-current="page" @endif>
        <i class="fa fa-list me-1"></i> List
    </a>
    <a href="{{ route('property::lead::board') }}" class="btn btn-light {{ $active === 'board' ? 'active' : '' }}" @if ($active === 'board') aria-current="page" @endif>
        <i class="fa fa-columns me-1"></i> Board
    </a>
    <a href="{{ route('property::lead::calendar') }}" class="btn btn-light {{ $active === 'calendar' ? 'active' : '' }}" @if ($active === 'calendar') aria-current="page" @endif>
        <i class="fa fa-calendar me-1"></i> Calendar
    </a>
</div>
