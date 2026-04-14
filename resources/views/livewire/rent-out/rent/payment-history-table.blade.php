<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            {{-- ═══ Top Bar: Show/Search/Column Visibility ═══ --}}
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    <h6 class="mb-0 fw-semibold text-primary">
                        <i class="fa fa-history me-2"></i> Payment History
                    </h6>
                </div>
                <div class="col-md-6">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0 text-muted small fw-semibold">Show:</label>
                        </div>
                        <div class="col-auto">
                            <select wire:model.live="limit"
                                class="form-select form-select-sm border-secondary-subtle shadow-sm">
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="500">500</option>
                            </select>
                        </div>
                        <div class="col">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-secondary-subtle">
                                    <i class="fa fa-search"></i>
                                </span>
                                <input type="text" wire:model.live.debounce.300ms="search" autofocus
                                    placeholder="Search voucher / cheque / customer..."
                                    class="form-control form-control-sm border-secondary-subtle shadow-sm"
                                    autocomplete="off">
                            </div>
                        </div>
                        <div class="col-auto">
                            {{-- Column Visibility Dropdown --}}
                            <div class="dropdown">
                                <button
                                    class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 shadow-sm"
                                    type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                    aria-expanded="false">
                                    <i class="fa fa-columns"></i>
                                    <span class="d-none d-md-inline">Column visibility</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:220px;">
                                    <li class="dropdown-header fw-semibold text-muted"
                                        style="font-size:.75rem; letter-spacing:.04em;">TOGGLE COLUMNS</li>
                                    <li>
                                        <hr class="dropdown-divider my-1">
                                    </li>
                                    @php
                                        $columnLabels = [
                                            'date' => 'Date',
                                            'voucher' => 'Voucher No',
                                            'customer' => 'Customer',
                                            'group' => 'Group/Project',
                                            'building' => 'Building',
                                            'property' => 'Property No/Unit',
                                            'payment_mode' => 'Payment Mode',
                                            'cheque_no' => 'Cheque No',
                                            'bank' => 'Bank',
                                            'category' => 'Category',
                                            'amount' => 'Amount',
                                            'remark' => 'Remark',
                                        ];
                                    @endphp
                                    @foreach ($columnLabels as $key => $label)
                                        <li>
                                            <label class="dropdown-item d-flex align-items-center gap-2 py-2"
                                                style="cursor:pointer; font-size:.85rem;">
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                        @checked($this->isColumnVisible($key))
                                                        wire:click="toggleColumn('{{ $key }}')"
                                                        style="cursor:pointer;">
                                                </div>
                                                {{ $label }}
                                            </label>
                                        </li>
                                    @endforeach
                                    <li>
                                        <hr class="dropdown-divider my-1">
                                    </li>
                                    <li>
                                        <button class="dropdown-item text-center text-warning fw-semibold"
                                            wire:click="resetColumns" style="font-size:.85rem;">
                                            <i class="fa fa-undo me-1"></i> Reset to Defaults
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-3">

            {{-- ═══ Filter Row 1: Group, Building, Property, Customer, Payment Mode ═══ --}}
            <div class="row g-3">
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-folder-open text-primary me-1 small"></i> Group/Project
                    </label>
                    {{ html()->select('filterGroup', [])->value('')->class('select-property_group_id-list border-secondary-subtle shadow-sm')->id('paymentHistory_filterGroup')->placeholder('All Groups')->attribute('wire:model', 'filterGroup') }}
                </div>
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-building text-primary me-1 small"></i> Building
                    </label>
                    {{ html()->select('filterBuilding', [])->value('')->class('select-property_building_id-list border-secondary-subtle shadow-sm')->id('paymentHistory_filterBuilding')->placeholder('All Buildings')->attribute('wire:model', 'filterBuilding')->attribute('data-group-select', '#paymentHistory_filterGroup') }}
                </div>
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-home text-primary me-1 small"></i> Property/Unit
                    </label>
                    {{ html()->select('filterProperty', [])->value('')->class('select-property_id-list border-secondary-subtle shadow-sm')->id('paymentHistory_filterProperty')->placeholder('All Properties')->attribute('wire:model', 'filterProperty')->attribute('data-building-select', '#paymentHistory_filterBuilding')->attribute('data-group-select', '#paymentHistory_filterGroup') }}
                </div>
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-user text-primary me-1 small"></i> Customer
                    </label>
                    {{ html()->select('filterCustomer', [])->value('')->class('select-customer_id-list border-secondary-subtle shadow-sm')->id('paymentHistory_filterCustomer')->placeholder('All Customers')->attribute('wire:model', 'filterCustomer') }}
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-credit-card text-primary me-1 small"></i> Payment Mode
                    </label>
                    <select wire:model.live="filterPaymentMode"
                        class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Modes</option>
                        @foreach ($paymentModes as $mode)
                            <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- ═══ Filter Row 2: Date Range ═══ --}}
            <div class="row g-3 mt-1">
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-calendar text-primary me-1 small"></i> From Date
                    </label>
                    <input type="date" wire:model.live="dateFrom"
                        class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-calendar-check-o text-primary me-1 small"></i> To Date
                    </label>
                    <input type="date" wire:model.live="dateTo"
                        class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
            </div>

            {{-- ═══ Reset Filters ═══ --}}
            <div class="row mt-3">
                <div class="col-12">
                    <button wire:click="resetFilters"
                        class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 shadow-sm">
                        <i class="fa fa-times"></i>
                        Reset Filters
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══ Statistics Summary ═══ --}}
        @php
            $modeIcons = [
                'cash' => 'fa-money',
                'cheque' => 'fa-university',
                'pos' => 'fa-credit-card',
                'bank_transfer' => 'fa-exchange',
            ];
            $modeColors = [
                'cash' => 'primary',
                'cheque' => 'info',
                'pos' => 'success',
                'bank_transfer' => 'warning',
            ];
            $totalReceived = $statistics['total_received'];
        @endphp

        <div class="card-body border-bottom bg-body-tertiary">
            {{-- ─── Hero Total Received Card ─── --}}
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="card border-0 shadow text-white overflow-hidden position-relative"
                        style="background: linear-gradient(135deg, var(--bs-success) 0%, var(--bs-primary) 100%);">
                        <div class="position-absolute top-0 end-0 opacity-10" style="font-size:14rem; line-height:1; transform:translate(20%,-10%);">
                            <i class="fa fa-history"></i>
                        </div>
                        <div class="card-body p-4 position-relative">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                                        style="width:60px; height:60px; background: rgba(255,255,255,0.25); border:1px solid rgba(255,255,255,0.35);">
                                        <i class="fa fa-money fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-uppercase fw-bold opacity-75" style="font-size:.7rem; letter-spacing:.1em;">
                                            Payment History
                                        </div>
                                        <div class="fw-bold" style="font-size:.95rem;">Total Collection</div>
                                        <div class="fw-bold mt-1" style="font-size:1.85rem; line-height:1;">
                                            {{ number_format($totalReceived, 2) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="rounded-3 px-3 py-2 text-white d-inline-flex align-items-center gap-2"
                                        style="background: rgba(255,255,255,0.18); border:1px solid rgba(255,255,255,0.25);">
                                        <i class="fa fa-list-alt fs-5"></i>
                                        <div>
                                            <div class="text-uppercase opacity-75 fw-semibold" style="font-size:.6rem; letter-spacing:.08em;">
                                                Transactions
                                            </div>
                                            <div class="fw-bold" style="font-size:1.1rem; line-height:1;">
                                                {{ $statistics['total_count'] }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── Payment Mode Cards ─── --}}
            <div class="row g-3">
                @foreach ($paymentModes as $mode)
                    @php
                        $modeAmount = $statistics['by_mode'][$mode->value] ?? 0;
                        $color = $modeColors[$mode->value] ?? 'secondary';
                        $iconClass = $modeIcons[$mode->value] ?? 'fa-credit-card';
                        $sharePct = $totalReceived > 0
                            ? round(($modeAmount / $totalReceived) * 100, 1)
                            : 0;
                    @endphp
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative">
                            <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10 bg-{{ $color }}"></div>
                            <div class="position-absolute top-0 start-0 h-100 bg-{{ $color }}" style="width:4px;"></div>
                            <div class="card-body p-3 position-relative">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-{{ $color }} text-white shadow-sm"
                                            style="width:38px; height:38px;">
                                            <i class="fa {{ $iconClass }} fs-6"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size:.9rem;">{{ $mode->label() }}</div>
                                            <div class="text-muted" style="font-size:.7rem;">Payment Mode</div>
                                        </div>
                                    </div>
                                    <span class="badge bg-{{ $color }}-subtle text-{{ $color }}-emphasis border border-{{ $color }}-subtle">
                                        {{ $sharePct }}%
                                    </span>
                                </div>
                                <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size:.65rem; letter-spacing:.05em;">
                                    Total Received
                                </div>
                                <div class="fw-bold text-dark mb-2" style="font-size:1.15rem;">
                                    {{ number_format($modeAmount, 2) }}
                                </div>
                                <div class="progress" style="height:5px;">
                                    <div class="progress-bar bg-{{ $color }}" role="progressbar"
                                        style="width: {{ $sharePct }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ═══ Table Body ═══ --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                    <thead class="bg-light text-muted">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2" style="width:50px;">#</th>
                            @if ($this->isColumnVisible('date'))
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="Date" />
                                </th>
                            @endif
                            @if ($this->isColumnVisible('voucher'))
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="voucher_no" label="Voucher No" />
                                </th>
                            @endif
                            @if ($this->isColumnVisible('customer'))
                                <th class="fw-semibold">Customer</th>
                            @endif
                            @if ($this->isColumnVisible('group'))
                                <th class="fw-semibold">Group/Project</th>
                            @endif
                            @if ($this->isColumnVisible('building'))
                                <th class="fw-semibold">Building</th>
                            @endif
                            @if ($this->isColumnVisible('property'))
                                <th class="fw-semibold">Property No/Unit</th>
                            @endif
                            @if ($this->isColumnVisible('payment_mode'))
                                <th class="fw-semibold">Payment Mode</th>
                            @endif
                            @if ($this->isColumnVisible('cheque_no'))
                                <th class="fw-semibold">Cheque No</th>
                            @endif
                            @if ($this->isColumnVisible('bank'))
                                <th class="fw-semibold">Bank</th>
                            @endif
                            @if ($this->isColumnVisible('category'))
                                <th class="fw-semibold">Category</th>
                            @endif
                            @if ($this->isColumnVisible('amount'))
                                <th class="fw-semibold text-end">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="credit" label="Amount" />
                                </th>
                            @endif
                            @if ($this->isColumnVisible('remark'))
                                <th class="fw-semibold">Remark</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $index => $item)
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border">#{{ $data->firstItem() + $index }}</span>
                                </td>
                                @if ($this->isColumnVisible('date'))
                                    <td>
                                        <i class="fa fa-calendar me-1 text-muted opacity-75"></i>
                                        {{ $item->date?->format('d-m-Y') }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('voucher'))
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                                            {{ $item->voucher_no ?? '—' }}
                                        </span>
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('customer'))
                                    <td>
                                        @if ($item->rentOut)
                                            <a href="{{ route($config->viewRoute, $item->rent_out_id) }}"
                                                class="text-decoration-none">
                                                <i class="fa fa-user me-1 text-muted opacity-75"></i>
                                                {{ $item->rentOut->customer?->name }}
                                            </a>
                                        @endif
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('group'))
                                    <td>
                                        <i class="fa fa-folder-open me-1 text-muted opacity-75"></i>
                                        {{ $item->rentOut?->group?->name }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('building'))
                                    <td>
                                        <i class="fa fa-building-o me-1 text-muted opacity-75"></i>
                                        {{ $item->rentOut?->building?->name }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('property'))
                                    <td>
                                        @if ($item->rentOut)
                                            <a href="{{ route($config->viewRoute, $item->rent_out_id) }}"
                                                class="text-decoration-none">
                                                <i class="fa fa-home me-1 text-muted opacity-75"></i>
                                                {{ $item->rentOut?->property?->number }}
                                            </a>
                                        @endif
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('payment_mode'))
                                    <td>
                                        @if ($item->payment_type)
                                            @php
                                                $pmColor = match ($item->payment_type) {
                                                    'cash' => 'primary',
                                                    'cheque' => 'info',
                                                    'pos' => 'success',
                                                    'bank_transfer' => 'warning',
                                                    default => 'secondary',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $pmColor }}-subtle text-{{ $pmColor }}-emphasis border border-{{ $pmColor }}-subtle">
                                                {{ ucfirst(str_replace('_', ' ', $item->payment_type)) }}
                                            </span>
                                        @endif
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('cheque_no'))
                                    <td class="text-muted">{{ $item->cheque_no ?? '—' }}</td>
                                @endif
                                @if ($this->isColumnVisible('bank'))
                                    <td class="text-muted">{{ $item->bank_name ?? '—' }}</td>
                                @endif
                                @if ($this->isColumnVisible('category'))
                                    <td class="text-muted small">{{ $item->category ?? $item->group ?? '—' }}</td>
                                @endif
                                @if ($this->isColumnVisible('amount'))
                                    <td class="text-end fw-semibold text-success">
                                        {{ number_format($item->credit, 2) }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('remark'))
                                    <td class="text-muted small">
                                        {{ \Illuminate\Support\Str::limit($item->remark, 40) }}
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="20" class="text-center py-5 text-muted">
                                    <i class="fa fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                    No payment history records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($data->hasPages())
                <div class="p-3 border-top">
                    {{ $data->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                function clearAndReload(id) {
                    var el = document.getElementById(id);
                    if (el && el.tomSelect) {
                        el.tomSelect.clear();
                        el.tomSelect.clearOptions();
                        el.tomSelect.load('');
                    }
                }

                $('#paymentHistory_filterGroup').on('change', function() {
                    @this.set('filterGroup', $(this).val() || '');
                    clearAndReload('paymentHistory_filterBuilding');
                    clearAndReload('paymentHistory_filterProperty');
                    @this.set('filterBuilding', '');
                    @this.set('filterProperty', '');
                });
                $('#paymentHistory_filterBuilding').on('change', function() {
                    @this.set('filterBuilding', $(this).val() || '');
                    clearAndReload('paymentHistory_filterProperty');
                    @this.set('filterProperty', '');
                });
                $('#paymentHistory_filterProperty').on('change', function() {
                    @this.set('filterProperty', $(this).val() || '');
                });
                $('#paymentHistory_filterCustomer').on('change', function() {
                    @this.set('filterCustomer', $(this).val() || '');
                });
            });
        </script>
    @endpush
</div>
