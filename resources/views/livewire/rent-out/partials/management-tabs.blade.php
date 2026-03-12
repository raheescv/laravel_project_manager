{{-- Management Sections - Tabbed Navigation --}}
@php
    $defaultLabel = $isRental ? 'rent payment' : 'installment';
@endphp
<div class="card shadow-sm mb-4" x-data="{ activeTab: 'PaymentTab' }">
    <div class="card-header bg-light py-3 border-bottom">
        <h6 class="mb-0 fw-semibold"><i class="fa fa-folder-open me-2 text-primary"></i>Management Sections</h6>
    </div>
    <div class="card-body p-0">
        {{-- Tab Navigation --}}
        <ul class="nav nav-tabs border-bottom px-2 pt-2" id="managementTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link" :class="activeTab === 'PaymentTab' ? 'active' : ''" @click="activeTab = 'PaymentTab'" type="button">
                    <i class="fa fa-credit-card me-1"></i>
                    <span class="small">Payment</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" :class="activeTab === 'PaymentTermTab' ? 'active' : ''" @click="activeTab = 'PaymentTermTab'" type="button">
                    <i class="fa fa-calendar me-1"></i>
                    <span class="small">Payment Terms</span>
                </button>
            </li>
            @if($isRental)
                <li class="nav-item" role="presentation">
                    <button class="nav-link" :class="activeTab === 'UtilitiesTab' ? 'active' : ''" @click="activeTab = 'UtilitiesTab'" type="button">
                        <i class="fa fa-bolt me-1"></i>
                        <span class="small">Utilities</span>
                    </button>
                </li>
            @endif
            <li class="nav-item" role="presentation">
                <button class="nav-link" :class="activeTab === 'ServicesTab' ? 'active' : ''" @click="activeTab = 'ServicesTab'" type="button">
                    <i class="fa fa-cogs me-1"></i>
                    <span class="small">Services</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" :class="activeTab === 'ChequeTab' ? 'active' : ''" @click="activeTab = 'ChequeTab'" type="button">
                    <i class="fa fa-check-square-o me-1"></i>
                    <span class="small">Cheques</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" :class="activeTab === 'SecurityTab' ? 'active' : ''" @click="activeTab = 'SecurityTab'" type="button">
                    <i class="fa fa-shield me-1"></i>
                    <span class="small">Security</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" :class="activeTab === 'ExtendTab' ? 'active' : ''" @click="activeTab = 'ExtendTab'" type="button">
                    <i class="fa fa-plus-circle me-1"></i>
                    <span class="small">Extend</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" :class="activeTab === 'NotesTab' ? 'active' : ''" @click="activeTab = 'NotesTab'" type="button">
                    <i class="fa fa-file-text-o me-1"></i>
                    <span class="small">Notes</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" :class="activeTab === 'TransactionTab' ? 'active' : ''" @click="activeTab = 'TransactionTab'" type="button">
                    <i class="fa fa-exchange me-1"></i>
                    <span class="small">Transactions</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" :class="activeTab === 'MaintenanceTab' ? 'active' : ''" @click="activeTab = 'MaintenanceTab'" type="button">
                    <i class="fa fa-wrench me-1"></i>
                    <span class="small">Maintenance</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" :class="activeTab === 'DocumentsTab' ? 'active' : ''" @click="activeTab = 'DocumentsTab'" type="button">
                    <i class="fa fa-file-o me-1"></i>
                    <span class="small">Documents</span>
                </button>
            </li>
        </ul>

        {{-- Tab Content --}}
        <div class="p-3">
            <div x-show="activeTab === 'PaymentTab'">
                @livewire('rent-out.tabs.payment-tab', ['rentOutId' => $rentOut->id], key('payment-tab-'.$rentOut->id))
            </div>

            <div x-show="activeTab === 'PaymentTermTab'">
                @livewire('rent-out.tabs.payment-terms-tab', ['rentOutId' => $rentOut->id, 'isRental' => $isRental, 'defaultLabel' => $defaultLabel], key('payment-terms-tab-'.$rentOut->id))
            </div>

            @if($isRental)
                <div x-show="activeTab === 'UtilitiesTab'">
                    @livewire('rent-out.tabs.utilities-tab', ['rentOutId' => $rentOut->id], key('utilities-tab-'.$rentOut->id))
                </div>
            @endif

            <div x-show="activeTab === 'ServicesTab'">
                @livewire('rent-out.tabs.services-tab', ['rentOutId' => $rentOut->id], key('services-tab-'.$rentOut->id))
            </div>

            <div x-show="activeTab === 'ChequeTab'">
                @livewire('rent-out.tabs.cheques-tab', ['rentOutId' => $rentOut->id], key('cheques-tab-'.$rentOut->id))
            </div>

            <div x-show="activeTab === 'SecurityTab'">
                @livewire('rent-out.tabs.security-tab', ['rentOutId' => $rentOut->id], key('security-tab-'.$rentOut->id))
            </div>

            <div x-show="activeTab === 'ExtendTab'">
                @livewire('rent-out.tabs.extend-tab', ['rentOutId' => $rentOut->id], key('extend-tab-'.$rentOut->id))
            </div>

            <div x-show="activeTab === 'NotesTab'">
                @livewire('rent-out.tabs.notes-tab', ['rentOutId' => $rentOut->id], key('notes-tab-'.$rentOut->id))
            </div>

            <div x-show="activeTab === 'TransactionTab'">
                @livewire('rent-out.tabs.transactions-tab', ['rentOutId' => $rentOut->id], key('transactions-tab-'.$rentOut->id))
            </div>

            <div x-show="activeTab === 'MaintenanceTab'">
                @livewire('rent-out.tabs.maintenance-tab', ['rentOutId' => $rentOut->id], key('maintenance-tab-'.$rentOut->id))
            </div>

            <div x-show="activeTab === 'DocumentsTab'">
                @livewire('rent-out.tabs.documents-tab', ['rentOutId' => $rentOut->id], key('documents-tab-'.$rentOut->id))
            </div>
        </div>
    </div>
</div>
