{{-- Month grid of the bookable range.
     $pickable: open days are buttons that choose the day to book.
     $rangePick: any day from today is a button; two clicks set the range,
     with the stretch between them previewed on hover. --}}
@php
    $rangePick = $rangePick ?? false;
    $rangeAnchor = $rangeAnchor ?? null;
    $canBack = $rangePick ? $calendar['canBackAny'] : $calendar['canBack'];
    $canForward = $rangePick ? $calendar['canForwardAny'] : $calendar['canForward'];
@endphp
<div class="apx-cal-h">
    <button type="button" class="apx-cal-nav" wire:click="shiftMonth(-1)" @disabled(! $canBack)
        title="Previous month"><i class="fa fa-chevron-left"></i></button>
    <b>{{ $calendar['label'] }}</b>
    <button type="button" class="apx-cal-nav" wire:click="shiftMonth(1)" @disabled(! $canForward)
        title="Next month"><i class="fa fa-chevron-right"></i></button>
</div>
<div class="apx-cal @if ($rangePick) ranging @endif"
    @if ($rangePick && $rangeAnchor)
        x-data="{ anchor: '{{ $rangeAnchor }}', hover: null,
            between(day) { if (! this.hover) return false; const a = this.anchor < this.hover ? this.anchor : this.hover;
                const b = this.anchor < this.hover ? this.hover : this.anchor; return day >= a && day <= b; } }"
        x-on:mouseleave="hover = null"
    @endif>
    @foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $weekday)
        <div class="wd">{{ $weekday }}</div>
    @endforeach
    @for ($i = 0; $i < $calendar['blanks']; $i++)
        <div></div>
    @endfor
    @foreach ($calendar['cells'] as $cell)
        @php
            $canPick = $rangePick ? ! $cell['past'] : ($pickable && $cell['free'] > 0);
            $classes = array_filter([
                'd',
                $cell['in'] && ! $rangeAnchor ? 'in' : null,
                $cell['first'] && ! $rangeAnchor ? 'first' : null,
                $cell['last'] && ! $rangeAnchor ? 'last' : null,
                $cell['in'] && ! $cell['free'] ? 'closed' : null,
                $cell['free'] && $cell['free'] < 3 ? 'few' : null,
                $cell['today'] ? 'today' : null,
                $cell['past'] && $rangePick ? 'past' : null,
                $canPick ? 'pick' : null,
                $pickable && $canPick && $activeDay === $cell['date'] ? 'sel' : null,
                $rangePick && $rangeAnchor === $cell['date'] ? 'sel' : null,
            ]);
            $title = $cell['in'] ? ($cell['free'] ? $cell['free'].' free time(s)' : 'Closed') : '';
            if ($rangePick && $canPick) {
                $title = $rangeAnchor ? 'Make this the other end of the range' : 'Start the range here';
            }
        @endphp
        <button type="button" class="{{ implode(' ', $classes) }}"
            @if ($canPick)
                wire:click="{{ $rangePick ? 'pickRangeDay' : 'selectDay' }}('{{ $cell['date'] }}')"
                @if ($rangePick && $rangeAnchor)
                    x-on:mouseenter="hover = '{{ $cell['date'] }}'"
                    :class="{ 'hov': between('{{ $cell['date'] }}') }"
                @endif
            @else
                tabindex="-1"
            @endif
            title="{{ $title }}">
            <span>{{ $cell['day'] }}</span>
            @if ($cell['free'])
                <i class="dot"></i>
            @endif
        </button>
    @endforeach
</div>
<div class="apx-legend">
    @if ($rangePick)
        <span style="color:var(--text-2)">
            <i class="fa fa-hand-o-up" style="width:auto;height:auto;background:none"></i>
            @if ($rangeAnchor)
                From <b>{{ \Carbon\Carbon::parse($rangeAnchor)->format('d M') }}</b> &mdash; now click the other end
                <a href="#" wire:click.prevent="$set('rangeAnchor', null)" style="margin-inline-start:4px;color:var(--text-3)">Cancel</a>
            @else
                Click a start day, then an end day
            @endif
        </span>
    @else
        <span><i style="background:var(--success)"></i>Open</span>
        <span><i style="background:var(--warning)"></i>Few left</span>
    @endif
</div>
