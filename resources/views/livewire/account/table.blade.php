<div>
    {{-- Loading Bar --}}
    <div wire:loading.delay class="position-fixed top-0 start-0 w-100" style="z-index: 1060; height: 3px;">
        <div class="bg-primary h-100" style="animation: coa-loading 1.5s ease-in-out infinite;"></div>
    </div>

    {{-- Toolbar --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                {{-- Actions --}}
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex gap-2 align-items-center">
                        @can('account.create')
                            <button class="btn btn-primary btn-sm d-inline-flex gap-1 align-items-center" id="AccountAdd">
                                <i class="demo-psi-add fs-6"></i> Add New
                            </button>
                        @endcan
                        @can('account.export')
                            <button class="btn btn-sm btn-success" title="Export Excel" wire:click="export()">
                                <i class="demo-pli-file-excel"></i>
                            </button>
                        @endcan
                        @can('account.delete')
                            @if (count($selected) > 0)
                                <button class="btn btn-sm btn-danger" wire:click="delete()" wire:confirm="Delete {{ count($selected) }} selected accounts?">
                                    <i class="demo-pli-recycling"></i> <span class="small">{{ count($selected) }}</span>
                                </button>
                            @endif
                        @endcan
                        @can('account.import')
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#AccountImportModal" title="Import">
                                <i class="demo-pli-download-from-cloud"></i>
                            </button>
                        @endcan
                    </div>
                </div>

                {{-- Filters --}}
                <div class="col-lg-8 col-md-6">
                    <div class="d-flex gap-2 justify-content-md-end flex-wrap">
                        <div style="min-width: 140px;" wire:ignore>
                            {{ html()->select('account_type', accountTypes())->value('')->class('tomSelect')->id('account_type')->placeholder('Account Type') }}
                        </div>
                        <div style="min-width: 180px;" wire:ignore>
                            {{ html()->select('account_category_id', [])->value('')->class('select-account_category_id')->id('account_category_id')->placeholder('Category') }}
                        </div>
                        <div class="input-group" style="max-width: 220px;">
                            <span class="input-group-text bg-white border-end-0 px-2"><i class="demo-pli-magnifi-glass"></i></span>
                            <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm border-start-0" placeholder="Search..." autofocus autocomplete="off">
                        </div>
                        <select wire:model.live="limit" class="form-select form-select-sm" style="width: auto;">
                            <option value="10">10</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" title="Columns">
                                <i class="demo-pli-view-list"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" style="min-width: 180px;" onclick="event.stopPropagation()">
                                <li class="dropdown-header small">Columns</li>
                                @foreach ($visibleColumnNames as $column => $label)
                                    <li>
                                        <div class="form-check px-3 py-1" onclick="event.stopPropagation()">
                                            <input class="form-check-input" type="checkbox" wire:model.lazy="visibleColumns.{{ $column }}" id="col_{{ $column }}" onclick="event.stopPropagation()">
                                            <label class="form-check-label small" for="col_{{ $column }}" onclick="event.stopPropagation()">{{ $label }}</label>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Toggle Filters --}}
            <div class="d-flex gap-3 mt-2 pt-2 border-top">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="excludeCustomer" wire:model.live="excludeCustomer">
                    <label class="form-check-label small" for="excludeCustomer">Exclude Customers</label>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="excludeVendor" wire:model.live="excludeVendor">
                    <label class="form-check-label small" for="excludeVendor">Exclude Vendors</label>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="row g-0">
                {{-- Tree Sidebar --}}
                <div class="col-lg-3 border-end" style="max-height: 75vh; overflow-y: auto;">
                    <div class="px-3 py-2">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: 0.05em;">Account Tree</span>
                        </div>
                        <div class="coa-tree">
                            @foreach ($treeData as $typeKey => $typeData)
                                @php $isTypeExpanded = $this->isTypeExpanded($typeKey); @endphp
                                <div class="mb-2">
                                    {{-- Type Header --}}
                                    <div class="d-flex align-items-center gap-1 py-2 px-2 rounded-2 coa-tree-node {{ $account_type === $typeKey ? 'coa-active-type' : '' }}" style="cursor: pointer;">
                                        <span wire:click.stop="toggleType('{{ $typeKey }}')" class="d-inline-flex align-items-center justify-content-center" style="width: 18px; height: 18px;">
                                            <i class="demo-psi-arrow-{{ $isTypeExpanded ? 'down' : 'right' }}" style="font-size: 0.65rem;"></i>
                                        </span>
                                        <span wire:click="filterByType('{{ $typeKey }}')" class="d-flex align-items-center gap-2 flex-grow-1">
                                            <i class="pli-folder fs-6"></i>
                                            <span class="fw-semibold small">{{ $typeData['label'] }}</span>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary ms-auto" style="font-size: 0.65rem;">
                                                {{ count($typeData['categories']) + (count($typeData['uncategorized']) > 0 ? 1 : 0) }}
                                            </span>
                                        </span>
                                    </div>

                                    {{-- Categories --}}
                                    @if ($isTypeExpanded)
                                        <div class="ms-3 mt-1">
                                            @foreach ($typeData['categories'] as $categoryId => $category)
                                                @php $isCatExpanded = $this->isCategoryExpanded($categoryId); @endphp
                                                <div class="mb-1">
                                                    <div class="d-flex align-items-center gap-1 py-1 px-2 rounded-2 coa-tree-node {{ $account_category_id == $categoryId ? 'coa-active-cat' : '' }}" style="cursor: pointer;">
                                                        <span wire:click.stop="toggleCategory({{ $categoryId }})" class="d-inline-flex align-items-center justify-content-center" style="width: 16px; height: 16px;">
                                                            <i class="demo-psi-arrow-{{ $isCatExpanded ? 'down' : 'right' }}" style="font-size: 0.6rem;"></i>
                                                        </span>
                                                        <span wire:click.stop="filterByCategory({{ $categoryId }})" class="d-flex align-items-center gap-2 flex-grow-1">
                                                            <span class="small">{{ $category['name'] }}</span>
                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary ms-auto" style="font-size: 0.6rem;">{{ count($category['accounts']) }}</span>
                                                        </span>
                                                    </div>

                                                    @if ($isCatExpanded)
                                                        <div class="ms-3 mt-1" style="max-height: 250px; overflow-y: auto;">
                                                            @foreach ($category['accounts'] as $account)
                                                                <div class="py-1 px-2 rounded-1 mb-1 coa-tree-leaf {{ $selectedAccountId == $account['id'] ? 'coa-active-leaf' : '' }}"
                                                                    wire:click.stop="filterByAccount({{ $account['id'] }})" style="cursor: pointer;">
                                                                    <span class="small text-truncate d-block" title="{{ $account['name'] }}">{{ $account['name'] }}</span>
                                                                    @if ($account['alias_name'])
                                                                        <span class="d-block text-muted text-truncate" style="font-size: 0.7rem;">{{ $account['alias_name'] }}</span>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach

                                            {{-- Uncategorized --}}
                                            @if (!empty($typeData['uncategorized']))
                                                @php $isUncatExpanded = $this->isCategoryExpanded(0); @endphp
                                                <div class="mb-1">
                                                    <div class="d-flex align-items-center gap-1 py-1 px-2 rounded-2 coa-tree-node" style="cursor: pointer;">
                                                        <span wire:click.stop="toggleCategory(0)" class="d-inline-flex align-items-center justify-content-center" style="width: 16px; height: 16px;">
                                                            <i class="demo-psi-arrow-{{ $isUncatExpanded ? 'down' : 'right' }}" style="font-size: 0.6rem;"></i>
                                                        </span>
                                                        <span class="d-flex align-items-center gap-2 flex-grow-1">
                                                            <span class="small text-muted fst-italic">Uncategorized</span>
                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary ms-auto" style="font-size: 0.6rem;">{{ count($typeData['uncategorized']) }}</span>
                                                        </span>
                                                    </div>

                                                    @if ($isUncatExpanded)
                                                        <div class="ms-3 mt-1" style="max-height: 250px; overflow-y: auto;">
                                                            @foreach ($typeData['uncategorized'] as $account)
                                                                <div class="py-1 px-2 rounded-1 mb-1 coa-tree-leaf {{ $selectedAccountId == $account['id'] ? 'coa-active-leaf' : '' }}"
                                                                    wire:click.stop="filterByAccount({{ $account['id'] }})" style="cursor: pointer;">
                                                                    <span class="small text-truncate d-block" title="{{ $account['name'] }}">{{ $account['name'] }}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            @if (empty($treeData))
                                <div class="text-center text-muted py-4 small fst-italic">No accounts found</div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Data Table --}}
                <div class="col-lg-9">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 coa-table">
                            <thead>
                                <tr class="bg-light">
                                    <th class="border-0 py-2 ps-3" style="width: 3%;">
                                        <div class="form-check mb-0">
                                            <input type="checkbox" class="form-check-input" wire:model.live="selectAll">
                                        </div>
                                    </th>
                                    @if ($visibleColumns['id'] ?? true)
                                        <th class="border-0 py-2" style="width: 5%;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['account_type'] ?? true)
                                        <th class="border-0 py-2" style="width: 10%;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_type" label="Type" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['account_category'] ?? true)
                                        <th class="border-0 py-2" style="width: 15%;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_category_id" label="Category" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['name'] ?? true)
                                        <th class="border-0 py-2">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Name" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['alias_name'] ?? true)
                                        <th class="border-0 py-2" style="width: 12%;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="alias_name" label="Alias" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['description'] ?? true)
                                        <th class="border-0 py-2" style="width: 18%;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="description" label="Description" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['model'] ?? true)
                                        <th class="border-0 py-2" style="width: 8%;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="model" label="Model" />
                                        </th>
                                    @endif
                                    <th class="border-0 py-2 text-end pe-3" style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data as $item)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="form-check mb-0">
                                                <input type="checkbox" class="form-check-input" value="{{ $item->id }}" wire:model.live="selected">
                                            </div>
                                        </td>
                                        @if ($visibleColumns['id'] ?? true)
                                            <td class="text-muted">{{ $item->id }}</td>
                                        @endif
                                        @if ($visibleColumns['account_type'] ?? true)
                                            <td>
                                                @if ($item->account_type)
                                                    @php
                                                        $typeColors = ['asset' => 'primary', 'liability' => 'warning', 'income' => 'success', 'expense' => 'danger', 'equity' => 'info'];
                                                        $tc = $typeColors[$item->account_type] ?? 'secondary';
                                                    @endphp
                                                    <span class="badge bg-{{ $tc }} bg-opacity-10 text-{{ $tc }}">{{ ucfirst($item->account_type) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        @endif
                                        @if ($visibleColumns['account_category'] ?? true)
                                            <td class="text-muted small">{{ $item->accountCategory?->name }}</td>
                                        @endif
                                        @if ($visibleColumns['name'] ?? true)
                                            <td>
                                                <a href="{{ route('account::view', $item->id) }}" class="text-decoration-none fw-medium">{{ $item->name }}</a>
                                            </td>
                                        @endif
                                        @if ($visibleColumns['alias_name'] ?? true)
                                            <td class="text-muted small">{{ $item->alias_name }}</td>
                                        @endif
                                        @if ($visibleColumns['description'] ?? true)
                                            <td class="text-muted small text-truncate" style="max-width: 200px;" title="{{ $item->description }}">{{ $item->description }}</td>
                                        @endif
                                        @if ($visibleColumns['model'] ?? true)
                                            <td>
                                                @if ($item->model)
                                                    <span class="badge bg-{{ $item->model === 'Customer' ? 'info' : 'warning' }} bg-opacity-10 text-{{ $item->model === 'Customer' ? 'info' : 'warning' }}">{{ $item->model }}</span>
                                                @endif
                                            </td>
                                        @endif
                                        <td class="text-end pe-3">
                                            @can('account.edit')
                                                <button class="btn btn-light edit px-2 py-0" table_id="{{ $item->id }}" title="Edit" style="font-size: 0.75rem;">
                                                    <i class="demo-psi-pencil"></i>
                                                </button>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="20" class="text-center text-muted py-4 fst-italic small">No accounts found</td>
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
        </div>
    </div>

    <style>
        @keyframes coa-loading {
            0% { width: 0; margin-left: 0; }
            50% { width: 60%; margin-left: 20%; }
            100% { width: 0; margin-left: 100%; }
        }

        /* Compact table */
        .coa-table { font-size: 0.75rem; }
        .coa-table thead th { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.03em; color: #6c757d; padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }
        .coa-table tbody td { padding-top: 0.35rem !important; padding-bottom: 0.35rem !important; vertical-align: middle; }
        .coa-table tbody tr { border-bottom: 1px solid rgba(0,0,0,0.04); }
        .coa-table tbody tr:nth-child(even) { background-color: rgba(0,0,0,0.015); }
        .coa-table .badge { font-size: 0.65rem; padding: 0.2em 0.5em; }
        .coa-table .form-check-input { width: 0.85em; height: 0.85em; }

        /* Compact tree */
        .coa-tree { font-size: 0.75rem; }
        .coa-tree-node { transition: background-color 0.15s; padding: 0.25rem 0.5rem !important; }
        .coa-tree-node:hover { background-color: #f0f0f0 !important; }
        .coa-tree-leaf { transition: background-color 0.15s; padding: 0.2rem 0.5rem !important; }
        .coa-tree-leaf:hover { background-color: #f0f0f0 !important; }

        /* Active states - soft pastel with dark readable text */
        .coa-active-type { background-color: #e8f0fe !important; color: #1a56db !important; border-left: 3px solid #1a56db; }
        .coa-active-type .badge { background-color: #1a56db !important; color: #fff !important; opacity: 1 !important; }
        .coa-active-cat { background-color: #e6f7ed !important; color: #15803d !important; border-left: 3px solid #15803d; }
        .coa-active-leaf { background-color: #fef9e7 !important; color: #92400e !important; border-left: 2px solid #d97706; }
    </style>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#account_type').on('change', function(e) {
                    @this.set('account_type', $(this).val() || null);
                });
                $('#account_category_id').on('change', function(e) {
                    @this.set('account_category_id', $(this).val() || null);
                });
                $(document).on('click', '.edit', function() {
                    Livewire.dispatch("Account-Page-Update-Component", { id: $(this).attr('table_id') });
                });
                $('#AccountAdd').click(function() {
                    Livewire.dispatch("Account-Page-Create-Component");
                });
                window.addEventListener('RefreshAccountTable', event => {
                    Livewire.dispatch("Account-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>
