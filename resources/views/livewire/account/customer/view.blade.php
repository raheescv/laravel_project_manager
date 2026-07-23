{{--
    Customer View — shell only (hero + KPIs + tab rail).
    Every tab is its own Livewire component under App\Livewire\Account\Customer
    and mounts the first time its tab is opened.
    Design system: <x-account.customer.premium /> (scope .cvx)
--}}
@php
    $name = $accounts['name'] ?? 'Customer';
    $photo = ! empty($accounts['image']) ? asset('storage/' . $accounts['image']) : null;
    $initials = collect(explode(' ', trim($name)))->filter()->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode('');
    $initials = mb_strtoupper($initials !== '' ? $initials : '?');
    $customerType = $accounts['customer_type']['name'] ?? '';
    $confirmed = ! empty($accounts['kyc_confirmed_at']);
    $collectedPercent = $kpi['billed'] > 0 ? min(round(($kpi['paid'] / $kpi['billed']) * 100), 100) : 0;
    $outstandingPercent = $kpi['billed'] > 0 ? min(round(($kpi['balance'] / $kpi['billed']) * 100), 100) : 0;

    $tabs = collect([
        ['key' => 'BasicDetails', 'component' => 'account.customer.basic-details', 'icon' => 'fa-user', 'label' => 'Basic Details', 'count' => null, 'can' => null],
        ['key' => 'Kyc', 'component' => 'account.customer.kyc', 'icon' => 'fa-shield', 'label' => 'KYC', 'count' => $kpi['kyc_percent'] . '%', 'can' => 'customer kyc.view'],
        ['key' => 'RentoutHistory', 'component' => 'account.customer.rentout-history', 'icon' => 'fa-key', 'label' => 'Rentout / Sale', 'count' => $kpi['agreements'] ?: null, 'can' => 'rent out.view'],
        ['key' => 'Sales', 'component' => 'account.customer.sales', 'icon' => 'fa-shopping-cart', 'label' => 'Sales', 'count' => $kpi['invoices'] ?: null, 'can' => 'report.sales overview'],
        ['key' => 'SaleReturn', 'component' => 'account.customer.sale-returns', 'icon' => 'fa-undo', 'label' => 'Returns', 'count' => $kpi['returns'] ?: null, 'can' => 'report.sale return item'],
        ['key' => 'SaleItems', 'component' => 'account.customer.sale-items', 'icon' => 'fa-cube', 'label' => 'Sale Items', 'count' => null, 'can' => 'report.sale item'],
        ['key' => 'SaleProductSummary', 'component' => 'account.customer.sale-item-summary', 'icon' => 'fa-bar-chart', 'label' => 'Item Summary', 'count' => null, 'can' => 'report.sale item'],
        ['key' => 'Notes', 'component' => 'account.customer.notes', 'icon' => 'fa-pencil-square-o', 'label' => 'Notes', 'count' => $kpi['notes'] ?: null, 'can' => 'account note.view'],
    ])->filter(fn ($tab) => ! $tab['can'] || auth()->user()->can($tab['can']))->values();
@endphp

<x-account.customer.premium />

<div class="cvx">

    {{-- ══════════════════════════════  HERO  ══════════════════════════════ --}}
    <header class="cv-hero">
        <span class="glow a"></span><span class="glow b"></span>

        <div class="row g-3 align-items-start">
            <div class="col-auto ava">
                <div class="disc">
                    @if ($photo)
                        <img src="{{ $photo }}" alt="{{ $name }}">
                    @else
                        {{ $initials }}
                    @endif
                </div>
                @if ($confirmed)
                    <span class="badge-dot" data-bs-toggle="tooltip" title="Details confirmed"><i class="fa fa-check"></i></span>
                @endif
            </div>

            <div class="col-12 col-md">
                <h1 class="hname">{{ $name }} <span class="code">#{{ $accounts['id'] ?? '—' }}</span></h1>
                <div class="d-flex flex-wrap gap-1">
                    @if ($customerType)
                        <span class="chip solid"><i class="fa fa-star"></i> {{ $customerType }}</span>
                    @endif
                    <span class="chip"><span class="dot"></span> Active</span>
                    @can('customer kyc.view')
                        <span class="chip"><i class="fa fa-shield"></i> KYC {{ $kpi['kyc_percent'] }}%</span>
                    @endcan
                    @if (! empty($accounts['credit_period_days']))
                        <span class="chip"><i class="fa fa-clock-o"></i>
                            {{ $accounts['credit_period_days'] }} {{ $accounts['credit_period_days'] == 1 ? 'Day' : 'Days' }} Credit
                        </span>
                    @endif
                </div>
                <div class="hmeta d-flex flex-wrap column-gap-4 row-gap-1 mt-2">
                    @if (! empty($accounts['mobile']))
                        <span><i class="fa fa-mobile"></i> <a href="tel:{{ $accounts['mobile'] }}">{{ $accounts['mobile'] }}</a></span>
                    @endif
                    @if (! empty($accounts['email']))
                        <span><i class="fa fa-envelope-o"></i> <a href="mailto:{{ $accounts['email'] }}">{{ $accounts['email'] }}</a></span>
                    @endif
                    @if (! empty($accounts['place']))
                        <span><i class="fa fa-map-marker"></i> {{ $accounts['place'] }}</span>
                    @endif
                    @if (! empty($kpi['since']))
                        <span><i class="fa fa-calendar-o"></i> Customer since {{ systemDate($kpi['since']) }}</span>
                    @endif
                </div>
            </div>

            <div class="col-12 col-md-auto hacts d-flex flex-wrap gap-2 justify-content-md-end">
                @can('customer.view')
                    @if ($accounts)
                        <a href="{{ route('account::customer::statement', $accounts['id']) }}" target="_blank" class="b"
                            data-bs-toggle="tooltip" title="Generate Customer Statement PDF">
                            <i class="fa fa-file-pdf-o"></i> <span class="d-none d-sm-inline">Statement</span>
                        </a>
                    @endif
                @endcan
                @can('customer kyc.print')
                    @if ($accounts)
                        <a href="{{ route('account::customer::kyc', $accounts['id']) }}" target="_blank" class="b"
                            data-bs-toggle="tooltip" title="Print KYC Form PDF">
                            <i class="fa fa-print"></i> <span class="d-none d-sm-inline">KYC Form</span>
                        </a>
                    @endif
                @endcan
                <button type="button" id="CustomerEdit" class="b pri" data-bs-toggle="tooltip" title="Edit Customer Details">
                    <i class="fa fa-pencil"></i> <span class="d-none d-sm-inline">Edit</span>
                </button>
            </div>
        </div>
    </header>

    {{-- ══════════════════════════════  KPIs  ══════════════════════════════ --}}
    <div class="kpi-row row g-2 g-md-3">
        <div class="col-6 col-lg-3">
            <div class="kpi k1">
                <div class="d-flex align-items-center gap-2">
                    <span class="ic"><i class="fa fa-line-chart"></i></span>
                    <div class="min-w-0">
                        <div class="lab">Lifetime Value</div>
                        <div class="val">{{ currency($kpi['billed']) }}</div>
                    </div>
                </div>
                <div class="sub">
                    {{ $kpi['invoices'] }} {{ $kpi['invoices'] == 1 ? 'invoice' : 'invoices' }}
                    @if ($kpi['returned'] > 0)
                        · {{ currency($kpi['returned']) }} returned
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi k2">
                <div class="d-flex align-items-center gap-2">
                    <span class="ic"><i class="fa fa-check-circle-o"></i></span>
                    <div class="min-w-0">
                        <div class="lab">Collected</div>
                        <div class="val">{{ currency($kpi['paid']) }}</div>
                    </div>
                </div>
                <div class="track"><i style="width: {{ $collectedPercent }}%"></i></div>
                <div class="sub">{{ $collectedPercent }}% of billed value</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi k3">
                <div class="d-flex align-items-center gap-2">
                    <span class="ic"><i class="fa fa-exclamation-circle"></i></span>
                    <div class="min-w-0">
                        <div class="lab">Outstanding</div>
                        <div class="val">{{ currency($kpi['balance']) }}</div>
                    </div>
                </div>
                <div class="track"><i style="width: {{ $outstandingPercent }}%"></i></div>
                <div class="sub">
                    @if (! empty($accounts['credit_period_days']))
                        {{ $accounts['credit_period_days'] }} day credit period
                    @else
                        No credit period set
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi k4">
                <div class="d-flex align-items-center gap-2">
                    <span class="ic"><i class="fa fa-key"></i></span>
                    <div class="min-w-0">
                        <div class="lab">Agreements</div>
                        <div class="val">{{ $kpi['agreements'] }}</div>
                    </div>
                </div>
                <div class="sub">{{ $kpi['rentals'] }} rental · {{ $kpi['agreement_sales'] }} sale</div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════  TABS  ══════════════════════════════ --}}
    <div class="rail-pill" role="tablist">
        @foreach ($tabs as $tab)
            <button class="@if ($selected_tab === $tab['key']) active @endif" data-bs-toggle="tab" data-bs-target="#tab-{{ $tab['key'] }}"
                type="button" role="tab" wire:click="selectTab('{{ $tab['key'] }}')">
                <i class="fa {{ $tab['icon'] }}"></i>
                {{ $tab['label'] }}
                @if ($tab['count'])
                    <span class="cnt">{{ $tab['count'] }}</span>
                @endif
            </button>
        @endforeach
    </div>

    <div class="tab-content">
        @foreach ($tabs as $tab)
            <div id="tab-{{ $tab['key'] }}" class="tab-pane fade @if ($selected_tab === $tab['key']) active show @endif" role="tabpanel">
                @if (isset($loaded_tabs[$tab['key']]))
                    @livewire($tab['component'], ['account_id' => $account_id], key('cv-' . $tab['key'] . '-' . $account_id))
                @else
                    <div class="loading-tab"><i class="fa fa-circle-o-notch fa-spin"></i> Loading {{ $tab['label'] }}…</div>
                @endif
            </div>
        @endforeach
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $(document).on('click', '#CustomerEdit', function() {
                    Livewire.dispatch("Customer-Page-Update-Component", {
                        id: $(this).data('customer-id') || "{{ $accounts['id'] ?? '' }}"
                    });
                });
            });
        </script>
    @endpush
</div>
