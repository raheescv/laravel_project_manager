{{--
    The student's canteen card drawn as a physical card: school, chip, balance,
    name, class and card number. Blocked cards turn grey with a BLOCKED stamp;
    with no card linked it becomes a dashed placeholder.

    Styled by the .svx system (components/student/view-premium), so render it
    inside a .svx wrapper. The empty state renders $slot (e.g. a "Link a card" button).
--}}
@props(['account', 'detail', 'balance' => 0, 'large' => false])

@php
    $uid = (string) $detail?->card_uid;
    $school = tenant_cache('company_name', '') ?: config('app.name');
@endphp

@if ($uid === '')
    <div {{ $attributes->class(['idc', 'is-empty', 'lg' => $large]) }}>
        <div>
            <i class="fa fa-credit-card big"></i>
            <b>No card linked</b>
            <div class="small mb-2">The student cannot pay with a card yet.</div>
            {{ $slot }}
        </div>
    </div>
@else
    <div {{ $attributes->class(['idc', 'is-blocked' => $detail->isCardBlocked(), 'lg' => $large]) }} role="img"
        aria-label="Student card {{ $uid }}, balance {{ currency($balance) }}{{ $detail->isCardBlocked() ? ', blocked' : '' }}">
        <div class="idc-face">
            <div class="idc-top"><span>{{ $school }} · Student</span><i class="fa fa-wifi"></i></div>
            <div class="idc-chip"></div>
            <div class="idc-bal">
                <small>Card balance</small>
                <strong>{{ currency($balance) }}</strong>
            </div>
            <div class="idc-foot">
                <div class="who">
                    {{ $account->name }}
                    <span>{{ collect([$detail->classLabel(), $detail->admission_no])->filter()->implode(' · ') }}</span>
                </div>
                <div class="uid">{{ trim(chunk_split($uid, 4, ' ')) }}</div>
            </div>
        </div>
        @if ($detail->isCardBlocked())
            <div class="idc-stamp"><i class="fa fa-lock me-2"></i>BLOCKED</div>
        @endif
    </div>
@endif
