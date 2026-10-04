{{--
    Settings → Student Cards: one panel, four panes. The rail lives here, outside
    the three Livewire forms, so their re-renders never reset it; each form marks
    its panes with x-show="pane === '…'" and reports its status back through the
    `student-status` browser event after a save.
--}}
@php
    $studentCardPanes = [
        ['key' => 'card', 'icon' => 'fa-sliders', 'label' => 'Card & Portal'],
        ['key' => 'canteen', 'icon' => 'fa-cutlery', 'label' => 'Canteen'],
        ['key' => 'qpay', 'icon' => 'fa-globe', 'label' => 'Debit · QPay'],
        ['key' => 'mpgs', 'icon' => 'fa-credit-card', 'label' => 'Credit · MPGS'],
    ];
    $studentCardStatus = \App\Livewire\Settings\StudentConfiguration::railStatus()
        + \App\Livewire\Settings\QPayPayments::railStatus()
        + \App\Livewire\Settings\MpgsPayments::railStatus();
@endphp

<div class="sct" x-data="{ pane: 'card', status: @js($studentCardStatus) }"
    x-on:student-status.window="Object.assign(status, $event.detail.statuses)">
    <style>
        .sct [x-cloak] {
            display: none !important;
        }

        .sct-shell {
            --sct-acc: var(--bs-primary);
            --sct-surface: var(--bs-component-bg, var(--bs-body-bg));
            --sct-line-soft: color-mix(in srgb, var(--bs-border-color) 60%, transparent);
            border: 1px solid var(--sct-line-soft);
            border-radius: 18px;
            background: var(--sct-surface);
        }

        .sct-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .75rem 1rem;
            padding: .9rem 1rem;
            border-bottom: 1px solid var(--sct-line-soft);
            background: color-mix(in srgb, var(--sct-acc) 3%, var(--sct-surface));
            border-radius: 18px 18px 0 0;
        }

        .sct-title {
            display: flex;
            align-items: center;
            gap: .65rem;
            min-width: 0;
        }

        .sct-title-ic {
            display: grid;
            place-items: center;
            flex: none;
            width: 38px;
            height: 38px;
            border-radius: 11px;
            background: linear-gradient(135deg, color-mix(in srgb, var(--sct-acc) 80%, #fff), var(--sct-acc) 45%, color-mix(in srgb, var(--sct-acc) 70%, #000));
            color: #fff;
            box-shadow: 0 8px 18px -10px color-mix(in srgb, var(--sct-acc) 80%, transparent);
        }

        .sct-title h6 {
            margin: 0;
            font-size: .95rem;
            font-weight: 700;
            color: var(--bs-emphasis-color);
        }

        .sct-title p {
            margin: 0;
            font-size: .74rem;
            color: var(--bs-secondary-color);
        }

        .sct-rail {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 4px;
            width: 100%;
            padding: 4px;
            border: 1px solid var(--sct-line-soft);
            border-radius: 14px;
            background: color-mix(in srgb, var(--bs-emphasis-color) 4%, var(--sct-surface));
        }

        .sct-tab {
            display: flex;
            align-items: center;
            gap: .55rem;
            min-width: 0;
            padding: .5rem .6rem;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: var(--bs-secondary-color);
            text-align: start;
            transition: background .15s, color .15s, box-shadow .15s;
        }

        .sct-tab:hover {
            color: var(--bs-emphasis-color);
        }

        .sct-tab:focus-visible {
            outline: 2px solid var(--sct-acc);
            outline-offset: 1px;
        }

        .sct-tab.is-active {
            background: var(--sct-surface);
            color: var(--bs-emphasis-color);
            box-shadow: 0 1px 2px rgba(16, 24, 40, .08), 0 6px 16px -8px rgba(16, 24, 40, .3);
        }

        .sct-tab-ic {
            display: grid;
            place-items: center;
            flex: none;
            width: 30px;
            height: 30px;
            border-radius: 9px;
            background: color-mix(in srgb, var(--bs-emphasis-color) 6%, transparent);
            font-size: .85rem;
            transition: background .15s, color .15s;
        }

        .sct-tab.is-active .sct-tab-ic {
            background: var(--sct-acc);
            color: #fff;
        }

        .sct-tab-text {
            min-width: 0;
            line-height: 1.2;
        }

        .sct-tab-label {
            display: block;
            overflow: hidden;
            font-size: .8rem;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sct-tab-status {
            --dot: #8a94a6;
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            margin-top: .15rem;
            font-size: .68rem;
            color: var(--bs-secondary-color);
            white-space: nowrap;
        }

        .sct-tab-status::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--dot);
        }

        .sct-tab-status.is-live { --dot: #2f9e62; }
        .sct-tab-status.is-test { --dot: #d4931c; }
        .sct-tab-status.is-info { --dot: var(--sct-acc); }

        /* The forms inside sit flat in the shell */
        .sct .scx {
            gap: 0;
        }

        .sct-body > div + div {
            border-top: 0;
        }

        .sct-pane-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem 1rem;
            margin-bottom: 1rem;
        }

        .sct-pane-head h6 {
            margin: 0;
            font-size: .9rem;
            font-weight: 700;
            color: var(--bs-emphasis-color);
        }

        .sct-pane-head p {
            margin: .1rem 0 0;
            font-size: .76rem;
            color: var(--bs-secondary-color);
        }

        .sct-pane-head > div:first-child {
            flex: 1 1 260px;
            min-width: 0;
        }

        .sct .scx-foot {
            border-radius: 0 0 18px 18px;
        }

        .sct .form-label {
            margin-bottom: .25rem;
        }

        @media (max-width: 767.98px) {
            .sct-rail {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

    <div class="sct-shell">
        <div class="sct-top">
            <div class="sct-title">
                <span class="sct-title-ic"><i class="fa fa-graduation-cap"></i></span>
                <div>
                    <h6>Student Cards</h6>
                    <p>A card's balance is the student's ledger: parents top it up online, QLOUD POS spends it on tap.</p>
                </div>
            </div>
            <div class="sct-rail" role="tablist" aria-label="Student card settings">
                @foreach ($studentCardPanes as $studentCardPane)
                    <button type="button" class="sct-tab" role="tab" x-on:click="pane = @js($studentCardPane['key'])"
                        :class="{ 'is-active': pane === @js($studentCardPane['key']) }" :aria-selected="pane === @js($studentCardPane['key'])">
                        <span class="sct-tab-ic"><i class="fa {{ $studentCardPane['icon'] }}"></i></span>
                        <span class="sct-tab-text">
                            <span class="sct-tab-label">{{ $studentCardPane['label'] }}</span>
                            <span class="sct-tab-status" :class="'is-' + status[@js($studentCardPane['key'])].tone" x-text="status[@js($studentCardPane['key'])].text"></span>
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="sct-body">
            @livewire('settings.student-configuration')
            @livewire('settings.q-pay-payments')
            @livewire('settings.mpgs-payments')
        </div>
    </div>
</div>
