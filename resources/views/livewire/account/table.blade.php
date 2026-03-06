<div>
    <div class="card-header bg-light py-3">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="d-flex gap-2 align-items-center">
                    @can('account.create')
                        <button class="btn btn-primary d-inline-flex gap-2 align-items-center shadow-sm" id="AccountAdd">
                            <i class="demo-psi-add fs-5"></i>
                            <span class="vr my-1"></span>
                            Add New
                        </button>
                    @endcan
                    <div class="btn-group shadow-sm">
                        @can('account.export')
                            <button class="btn btn-success" title="Export as Excel" wire:click="export()">
                                <i class="demo-pli-file-excel fs-5"></i>
                            </button>
                        @endcan
                        @can('account.delete')
                            <button class="btn btn-danger" title="Delete selected items" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                                <i class="demo-pli-recycling fs-5"></i>
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2 justify-content-md-end">
                    <select wire:model.live="limit" class="form-select w-auto shadow-sm">
                        <option value="10">10 rows</option>
                        <option value="100">100 rows</option>
                        <option value="500">500 rows</option>
                    </select>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="demo-pli-magnifi-glass"></i>
                        </span>
                        <input type="text" wire:model.live="search" class="form-control border-start-0" placeholder="Search..." autofocus autocomplete="off">
                    </div>
                    @can('account.import')
                        <button class="btn btn-light shadow-sm" data-bs-toggle="modal" data-bs-target="#AccountImportModal" title="Import Data">
                            <i class="demo-pli-download-from-cloud fs-5"></i>
                        </button>
                    @endcan
                    <div class="btn-group shadow-sm">
                        <button type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Column Visibility">
                            <i class="demo-pli-view-list fs-5"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="min-width: 200px;" onclick="event.stopPropagation()">
                            <li class="dropdown-header">Show/Hide Columns</li>
                            <li><hr class="dropdown-divider"></li>
                            @foreach($visibleColumnNames as $column => $label)
                                <li>
                                    <div class="form-check px-3 py-2">
                                        <input class="form-check-input" type="checkbox" wire:model.lazy="visibleColumns.{{ $column }}" id="col_{{ $column }}" onclick="event.stopPropagation()">
                                        <label class="form-check-label" for="col_{{ $column }}" onclick="event.stopPropagation()">
                                            {{ $label }}
                                        </label>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <hr class="my-3">
        <div class="row">
            <div class="col-md-3" wire:ignore>
                {{ html()->select('account_type', accountTypes())->value('')->class('tomSelect')->id('account_type')->placeholder('Select Account Type') }}
            </div>
            <div class="col-md-4" wire:ignore>
                {{ html()->select('account_category_id', [])->value('')->class('select-account_category_id')->id('account_category_id')->placeholder('Select account category') }}
            </div>
            <div class="col-md-2">
                <div class="form-check form-switch d-flex align-items-center h-100">
                    <input class="form-check-input" type="checkbox" id="excludeCustomer" wire:model.live="excludeCustomer">
                    <label class="form-check-label ms-2" for="excludeCustomer">
                        Exclude Customer
                    </label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-check form-switch d-flex align-items-center h-100">
                    <input class="form-check-input" type="checkbox" id="excludeVendor" wire:model.live="excludeVendor">
                    <label class="form-check-label ms-2" for="excludeVendor">
                        Exclude Vendor
                    </label>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="row g-0">
            {{-- Tree Structure - 30% width --}}
            <div class="col-3 border-end" style="max-height: 70vh; overflow-y: auto;">
                <div class="p-3">
                    <h6 class="mb-3 fw-semibold">Account Tree</h6>
                    <div class="account-tree">
                        @foreach ($treeData as $typeKey => $typeData)
                            @php
                                $isTypeExpanded = $this->isTypeExpanded($typeKey);
                            @endphp
                            <div class="tree-item mb-2">
                                <div class="tree-type d-flex align-items-center gap-1 py-1 px-2 rounded {{ $account_type === $typeKey ? 'bg-primary text-white' : 'bg-light' }}"
                                    style="cursor: pointer;">
                                    <span wire:click.stop="toggleType('{{ $typeKey }}')" class="tree-toggle me-1">
                                        <i class="demo-psi-arrow-{{ $isTypeExpanded ? 'down' : 'right' }} fs-6"></i>
                                    </span>
                                    <span wire:click="filterByType('{{ $typeKey }}')" class="d-flex align-items-center gap-2 flex-grow-1">
                                        <i class="demo-psi-folder fs-6"></i>
                                        <span class="fw-semibold">{{ $typeData['label'] }}</span>
                                        <span class="badge bg-secondary ms-auto">{{ count($typeData['categories']) + (count($typeData['uncategorized']) > 0 ? 1 : 0) }}</span>
                                    </span>
                                </div>

                                @if ($isTypeExpanded)
                                <div class="tree-categories ms-3 mt-1">
                                    @foreach ($typeData['categories'] as $categoryId => $category)
                                        @php
                                            $isCategoryExpanded = $this->isCategoryExpanded($categoryId);
                                        @endphp
                                        <div class="tree-item mb-1">
                                            <div class="tree-category d-flex align-items-center gap-1 py-1 px-2 rounded {{ $account_category_id == $categoryId ? 'bg-info text-white' : 'bg-light-subtle' }}"
                                                style="cursor: pointer;">
                                                <span wire:click.stop="toggleCategory({{ $categoryId }})" class="tree-toggle me-1">
                                                    <i class="demo-psi-arrow-{{ $isCategoryExpanded ? 'down' : 'right' }} fs-6"></i>
                                                </span>
                                                <span wire:click.stop="filterByCategory({{ $categoryId }})" class="d-flex align-items-center gap-2 flex-grow-1">
                                                    <i class="demo-psi-folder-2 fs-6"></i>
                                                    <span class="small">{{ $category['name'] }}</span>
                                                    <span class="badge bg-secondary ms-auto">{{ count($category['accounts']) }}</span>
                                                </span>
                                            </div>

                                            @if ($isCategoryExpanded)
                                            <div class="tree-accounts ms-3 mt-1">
                                                @foreach ($category['accounts'] as $account)
                                                    <div class="tree-account py-1 px-2 rounded mb-1 cursor-pointer {{ $selectedAccountId == $account['id'] ? 'bg-warning text-dark' : 'hover-bg-light' }}"
                                                        wire:click.stop="filterByAccount({{ $account['id'] }})" style="cursor: pointer;">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="demo-psi-file fs-6"></i>
                                                            <span class="small text-truncate" title="{{ $account['name'] }}">
                                                                {{ $account['name'] }}
                                                            </span>
                                                        </div>
                                                        @if ($account['alias_name'])
                                                            <div class="ms-4 small text-muted text-truncate" title="{{ $account['alias_name'] }}">
                                                                {{ $account['alias_name'] }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                            @endif
                                        </div>
                                    @endforeach

                                    @if (!empty($typeData['uncategorized']))
                                        @php
                                            $isUncategorizedExpanded = $this->isCategoryExpanded(0);
                                        @endphp
                                        <div class="tree-item mb-1">
                                            <div class="tree-category d-flex align-items-center gap-1 py-1 px-2 rounded bg-light-subtle" style="cursor: pointer;">
                                                <span wire:click.stop="toggleCategory(0)" class="tree-toggle me-1">
                                                    <i class="demo-psi-arrow-{{ $isUncategorizedExpanded ? 'down' : 'right' }} fs-6"></i>
                                                </span>
                                                <span class="d-flex align-items-center gap-2 flex-grow-1">
                                                    <i class="demo-psi-folder-2 fs-6"></i>
                                                    <span class="small">Uncategorized</span>
                                                    <span class="badge bg-secondary ms-auto">{{ count($typeData['uncategorized']) }}</span>
                                                </span>
                                            </div>

                                            @if ($isUncategorizedExpanded)
                                            <div class="tree-accounts ms-3 mt-1">
                                                @foreach ($typeData['uncategorized'] as $account)
                                                    <div class="tree-account py-1 px-2 rounded mb-1 cursor-pointer {{ $selectedAccountId == $account['id'] ? 'bg-warning text-dark' : 'hover-bg-light' }}"
                                                        wire:click.stop="filterByAccount({{ $account['id'] }})" style="cursor: pointer;">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="demo-psi-file fs-6"></i>
                                                            <span class="small text-truncate" title="{{ $account['name'] }}">
                                                                {{ $account['name'] }}
                                                            </span>
                                                        </div>
                                                        @if ($account['alias_name'])
                                                            <div class="ms-4 small text-muted text-truncate" title="{{ $account['alias_name'] }}">
                                                                {{ $account['alias_name'] }}
                                                            </div>
                                                        @endif
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
                    </div>
                </div>
            </div>

            {{-- Table - 70% width --}}
            <div class="col-9">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="text-capitalize">
                                <th width="3%" class="text-nowrap">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" wire:model.live="selectAll" title="Select All">
                                    </div>
                                </th>
                                @if($visibleColumns['id'] ?? true)
                                <th width="5%" class="text-nowrap">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" />
                                </th>
                                @endif
                                @if($visibleColumns['account_type'] ?? true)
                                <th width="10%" class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_type" label="account type" /> </th>
                                @endif
                                @if($visibleColumns['account_category'] ?? true)
                                <th width="20%" class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_category_id" label="account category" /> </th>
                                @endif
                                @if($visibleColumns['name'] ?? true)
                                <th width="30%" class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="name" /> </th>
                                @endif
                                @if($visibleColumns['alias_name'] ?? true)
                                <th width="10%" class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="alias_name" label="alias name" /> </th>
                                @endif
                                @if($visibleColumns['description'] ?? true)
                                <th width="30%" class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="description" label="description" /> </th>
                                @endif
                                @if($visibleColumns['model'] ?? true)
                                <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="model" label="model" /> </th>
                                @endif
                                <th class="text-end px-3"> Action </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $item)
                                <tr>
                                    <td class="px-3 text-nowrap">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" value="{{ $item->id }}" wire:model.live="selected">
                                        </div>
                                    </td>
                                    @if($visibleColumns['id'] ?? true)
                                    <td class="px-3 text-nowrap">
                                        <label class="form-check-label">{{ $item->id }}</label>
                                    </td>
                                    @endif
                                    @if($visibleColumns['account_type'] ?? true)
                                    <td>
                                        <span class="badge bg-light text-dark">{{ ucFirst($item->account_type) }}</span>
                                    </td>
                                    @endif
                                    @if($visibleColumns['account_category'] ?? true)
                                    <td> {{ $item->accountCategory?->name }} </td>
                                    @endif
                                    @if($visibleColumns['name'] ?? true)
                                    <td>
                                        <a href="{{ route('account::view', $item->id) }}" class="text-decoration-none">{{ $item->name }}</a>
                                    </td>
                                    @endif
                                    @if($visibleColumns['alias_name'] ?? true)
                                    <td>
                                        <a href="{{ route('account::view', $item->id) }}" class="text-decoration-none">{{ $item->alias_name }}</a>
                                    </td>
                                    @endif
                                    @if($visibleColumns['description'] ?? true)
                                    <td class="text-muted">{{ $item->description }}</td>
                                    @endif
                                    @if($visibleColumns['model'] ?? true)
                                    <td>
                                        <span class="badge bg-light text-dark">{{ ucFirst($item->model) }}</span>
                                    </td>
                                    @endif
                                    <td class="text-end px-3">
                                        @can('account.edit')
                                            <button class="btn btn-light btn-sm edit" title="Edit" table_id="{{ $item->id }}">
                                                <i class="demo-psi-pencil fs-5"></i>
                                            </button>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">
                    {{ $data->links() }}
                </div>
            </div>
        </div>
    </div>

    <style>
        .account-tree {
            font-size: 0.875rem;
        }

        .tree-item {
            user-select: none;
        }

        .tree-type,
        .tree-category,
        .tree-account {
            transition: all 0.2s ease;
        }

        .tree-type:hover,
        .tree-category:hover,
        .tree-account:hover {
            transform: translateX(2px);
        }

        .hover-bg-light:hover {
            background-color: #f8f9fa !important;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .tree-accounts {
            max-height: 300px;
            overflow-y: auto;
        }

        .tree-categories {
            max-height: 500px;
            overflow-y: auto;
        }

        .tree-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .tree-toggle:hover {
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 3px;
        }

        .tree-type > span,
        .tree-category > span {
            display: flex;
            align-items: center;
        }
    </style>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#account_type').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('account_type', value);
                });
                $('#account_category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('account_category_id', value);
                });

                $(document).on('click', '.edit', function() {
                    Livewire.dispatch("Account-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
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
