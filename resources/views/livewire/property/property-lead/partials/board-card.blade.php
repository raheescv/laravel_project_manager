@php
    $status = \App\Support\LeadPipeline::canonical($lead->status);
    $open = \App\Support\LeadPipeline::isOpen($status);
    $idleDays = $open && $lead->updated_at && $lead->updated_at->lte($idleBefore) ? (int) $lead->updated_at->diffInDays(now(), true) : null;
    $meetIn = $open && $lead->meeting_date ? (int) today()->diffInDays($lead->meeting_date, false) : null;
    $meetTime = $lead->meeting_time ? \Carbon\Carbon::parse($lead->meeting_time)->format('g:i A') : null;
    $rep = $lead->assignee?->name;
@endphp
<article class="lc tn tn-{{ \App\Support\LeadPipeline::tone($status) }}"
    wire:key="lead-card-{{ $lead->id }}"
    data-id="{{ $lead->id }}"
    data-status="{{ $status }}"
    draggable="{{ $canMove ? 'true' : 'false' }}"
    tabindex="0"
    aria-label="{{ $lead->name }}, {{ $status }}"
    x-bind:class="{ 'sel': selected === {{ $lead->id }} }"
    x-on:click="open({{ $lead->id }})"
    x-on:keydown.enter.prevent="open({{ $lead->id }})">
    <div class="lc-top">
        <span class="lc-id">#{{ $lead->id }}</span>
        <span class="ty {{ $lead->type === 'Rentout' ? 'ty-rent' : 'ty-sales' }}">{{ $lead->type === 'Rentout' ? 'Rent' : 'Sales' }}</span>
        @if ($idleDays !== null)
            <span class="stale" title="No update for {{ $idleDays }} days"><i class="fa fa-clock-o"></i>{{ $idleDays }}d idle</span>
        @endif
    </div>
    <div class="lc-name">{{ $lead->name }}</div>
    @if ($lead->company_name)
        <div class="lc-co"><i class="fa fa-building"></i>{{ $lead->company_name }}</div>
    @endif
    <div class="lc-chips">
        @if ($status === \App\Support\LeadPipeline::UNMAPPED)
            <span class="chip legacy" title="Stored status value"><i class="fa fa-exclamation-triangle"></i>{{ $lead->status !== '' ? $lead->status : '(blank)' }}</span>
        @endif
        @if ($lead->group)
            <span class="chip"><i class="fa fa-home"></i>{{ $lead->group->name }}</span>
        @endif
        @if ($lead->source)
            <span class="chip"><i class="fa {{ \App\Support\LeadPipeline::sourceIcon($lead->source) }}"></i>{{ $lead->source }}</span>
        @endif
    </div>
    <div class="lc-foot">
        @if ($rep)
            <span class="av" style="--h: {{ \App\Support\LeadPipeline::hue($rep) }}" title="{{ $rep }}">{{ \App\Support\LeadPipeline::initials($rep) }}</span>
            <span class="who">{{ $rep }}</span>
        @else
            <span class="av none" title="Unassigned"><i class="fa fa-user"></i></span>
            <span class="who">Unassigned</span>
        @endif
        <span class="sp"></span>
        @if ($meetIn !== null)
            @if ($meetIn < 0)
                <span class="due over" title="Meeting was {{ $lead->meeting_date->format('M j') }}"><i class="fa fa-calendar"></i>{{ -$meetIn }}d overdue</span>
            @elseif ($meetIn === 0)
                <span class="due today" title="Meeting today"><i class="fa fa-clock-o"></i>Today{{ $meetTime ? ' '.$meetTime : '' }}</span>
            @elseif ($meetIn === 1)
                <span class="due soon" title="Meeting tomorrow{{ $meetTime ? ' '.$meetTime : '' }}"><i class="fa fa-calendar"></i>Tomorrow</span>
            @else
                <span class="due soon" title="Meeting{{ $meetTime ? ' '.$meetTime : '' }}"><i class="fa fa-calendar"></i>{{ $lead->meeting_date->format('M j') }}</span>
            @endif
        @endif
        @if ($lead->notes_count)
            <span class="mini" title="{{ $lead->notes_count }} notes"><i class="fa fa-comments-o"></i>{{ $lead->notes_count }}</span>
        @endif
        <span class="mini" title="Updated {{ $lead->updated_at?->format('M j, Y g:i A') }}">{{ $lead->updated_at?->gt(now()->subMinute()) ? 'now' : $lead->updated_at?->shortAbsoluteDiffForHumans() }}</span>
    </div>
</article>
