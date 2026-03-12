{{-- Management Sections - Tabbed Navigation --}}
<div class="card shadow-sm mb-4" x-data="{ activeTab: 'PaymentTab' }" wire:ignore.self>
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
                @include('livewire.rent-out.partials.tabs.payment-tab')
            </div>

            <div x-show="activeTab === 'PaymentTermTab'">
                @include('livewire.rent-out.partials.tabs.payment-terms-tab')
            </div>

            @if($isRental)
                <div x-show="activeTab === 'UtilitiesTab'">
                    @include('livewire.rent-out.partials.tabs.utilities-tab')
                </div>
            @endif

            <div x-show="activeTab === 'ServicesTab'">
                @include('livewire.rent-out.partials.tabs.services-tab')
            </div>

            <div x-show="activeTab === 'ChequeTab'">
                @include('livewire.rent-out.partials.tabs.cheques-tab')
            </div>

            <div x-show="activeTab === 'SecurityTab'">
                @include('livewire.rent-out.partials.tabs.security-tab')
            </div>

            <div x-show="activeTab === 'ExtendTab'">
                @include('livewire.rent-out.partials.tabs.extend-tab')
            </div>

            <div x-show="activeTab === 'NotesTab'">
                @include('livewire.rent-out.partials.tabs.notes-tab')
            </div>

            <div x-show="activeTab === 'TransactionTab'">
                @include('livewire.rent-out.partials.tabs.transactions-tab')
            </div>

            <div x-show="activeTab === 'MaintenanceTab'">
                @include('livewire.rent-out.partials.tabs.maintenance-tab')
            </div>

            <div x-show="activeTab === 'DocumentsTab'">
                @include('livewire.rent-out.partials.tabs.documents-tab')
            </div>
        </div>
    </div>
</div>
