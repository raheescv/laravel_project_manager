{{-- Management Sections --}}
@php
    $defaultLabel = $isRental ? 'rent payment' : 'installment';
@endphp
<style>
    .mgmt-tab-btn { font-size: .72rem; border-radius: .375rem; transition: all .15s ease; }
    .mgmt-tab-btn.active { background-color: var(--bs-primary); color: #fff !important; box-shadow: 0 2px 6px rgba(var(--bs-primary-rgb), .25); }
    .mgmt-tab-btn:not(.active):hover { background-color: var(--bs-primary-bg-subtle); color: var(--bs-primary-emphasis) !important; }
</style>
<div class="card border-0 shadow-sm mb-3 rounded-3 overflow-hidden" x-data="{ activeTab: 'PaymentTab' }">
    <div class="card-header border-bottom bg-body py-2 px-2">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center" style="width:26px;height:26px;">
                <i class="fa fa-folder-open text-primary-emphasis" style="font-size:.72rem;"></i>
            </div>
            <span class="fw-semibold text-emphasis" style="font-size:.78rem;">Management Sections</span>
        </div>
    </div>
    <div class="card-body p-0">
        {{-- Tab Navigation --}}
        <div class="border-bottom bg-body-tertiary">
            <div class="d-flex flex-wrap gap-1 px-2 py-2" role="tablist">
                @php
                    $tabs = [
                        ['key' => 'PaymentTab', 'icon' => 'fa-credit-card', 'label' => 'Payment'],
                        ['key' => 'PaymentTermTab', 'icon' => 'fa-calendar', 'label' => 'Terms'],
                    ];
                    if ($isRental) {
                        $tabs[] = ['key' => 'UtilitiesTab', 'icon' => 'fa-bolt', 'label' => 'Utilities'];
                    }
                    $tabs = array_merge($tabs, [
                        ['key' => 'ServicesTab', 'icon' => 'fa-cogs', 'label' => 'Services'],
                        ['key' => 'ChequeTab', 'icon' => 'fa-check-square-o', 'label' => 'Cheques'],
                        ['key' => 'SecurityTab', 'icon' => 'fa-shield', 'label' => 'Security'],
                        ['key' => 'ExtendTab', 'icon' => 'fa-plus-circle', 'label' => 'Extend'],
                    ]);
                    if ($rentOut->status === \App\Enums\RentOut\RentOutStatus::Booked) {
                        $tabs[] = ['key' => 'AgreementPointsTab', 'icon' => 'fa-list-ol', 'label' => 'Agmt Points'];
                    }
                    $tabs = array_merge($tabs, [
                        ['key' => 'NotesTab', 'icon' => 'fa-file-text-o', 'label' => 'Notes'],
                        ['key' => 'TransactionTab', 'icon' => 'fa-exchange', 'label' => 'Transactions'],
                        ['key' => 'MaintenanceTab', 'icon' => 'fa-wrench', 'label' => 'Maintenance'],
                        ['key' => 'DocumentsTab', 'icon' => 'fa-file-o', 'label' => 'Documents'],
                    ]);
                @endphp

                @foreach ($tabs as $tab)
                    <button class="btn btn-sm border-0 fw-medium px-2 py-1 mgmt-tab-btn"
                        :class="activeTab === '{{ $tab['key'] }}' ? 'active' : 'text-secondary'"
                        @click="activeTab = '{{ $tab['key'] }}'"
                        type="button">
                        <i class="fa {{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Tab Content --}}
        <div class="p-2">
            <div x-show="activeTab === 'PaymentTab'" x-transition:enter.duration.150ms>
                @livewire('rent-out.tabs.payment-tab', ['rentOutId' => $rentOut->id], key('payment-tab-' . $rentOut->id))
            </div>

            <div x-show="activeTab === 'PaymentTermTab'" x-transition:enter.duration.150ms>
                @livewire('rent-out.tabs.payment-terms-tab', ['rentOutId' => $rentOut->id, 'isRental' => $isRental, 'defaultLabel' => $defaultLabel], key('payment-terms-tab-' . $rentOut->id))
            </div>

            @if ($isRental)
                <div x-show="activeTab === 'UtilitiesTab'" x-transition:enter.duration.150ms>
                    @livewire('rent-out.tabs.utilities-tab', ['rentOutId' => $rentOut->id], key('utilities-tab-' . $rentOut->id))
                </div>
            @endif

            <div x-show="activeTab === 'ServicesTab'" x-transition:enter.duration.150ms>
                @livewire('rent-out.tabs.services-tab', ['rentOutId' => $rentOut->id], key('services-tab-' . $rentOut->id))
            </div>

            <div x-show="activeTab === 'ChequeTab'" x-transition:enter.duration.150ms>
                @livewire('rent-out.tabs.cheques-tab', ['rentOutId' => $rentOut->id], key('cheques-tab-' . $rentOut->id))
            </div>

            <div x-show="activeTab === 'SecurityTab'" x-transition:enter.duration.150ms>
                @livewire('rent-out.tabs.security-tab', ['rentOutId' => $rentOut->id], key('security-tab-' . $rentOut->id))
            </div>

            <div x-show="activeTab === 'ExtendTab'" x-transition:enter.duration.150ms>
                @livewire('rent-out.tabs.extend-tab', ['rentOutId' => $rentOut->id], key('extend-tab-' . $rentOut->id))
            </div>

            @if ($rentOut->status === \App\Enums\RentOut\RentOutStatus::Booked)
                <div x-show="activeTab === 'AgreementPointsTab'" x-transition:enter.duration.150ms>
                    @livewire('rent-out.tabs.agreement-points-tab', ['rentOutId' => $rentOut->id], key('agreement-points-tab-' . $rentOut->id))
                </div>
            @endif

            <div x-show="activeTab === 'NotesTab'" x-transition:enter.duration.150ms>
                @livewire('rent-out.tabs.notes-tab', ['rentOutId' => $rentOut->id], key('notes-tab-' . $rentOut->id))
            </div>

            <div x-show="activeTab === 'TransactionTab'" x-transition:enter.duration.150ms>
                @livewire('rent-out.tabs.transactions-tab', ['rentOutId' => $rentOut->id], key('transactions-tab-' . $rentOut->id))
            </div>

            <div x-show="activeTab === 'MaintenanceTab'" x-transition:enter.duration.150ms>
                @livewire('rent-out.tabs.maintenance-tab', ['rentOutId' => $rentOut->id], key('maintenance-tab-' . $rentOut->id))
            </div>

            <div x-show="activeTab === 'DocumentsTab'" x-transition:enter.duration.150ms>
                @livewire('rent-out.tabs.documents-tab', ['rentOutId' => $rentOut->id], key('documents-tab-' . $rentOut->id))
            </div>
        </div>
    </div>
</div>

<x-rent-out.pay-selected-modal :rentOutId="$rentOut->id" />
<x-rent-out.utility-pay-selected-modal :rentOutId="$rentOut->id" />
