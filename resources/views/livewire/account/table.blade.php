<div>
    {{-- Top loading bar --}}
    <div wire:loading.delay wire:target="search, limit, account_type, account_category_id, excludeCustomer, excludeVendor, filterByType, filterByCategory, filterByAccount, sortBy, delete, gotoPage, nextPage, previousPage, resetColumnVisibility" class="position-fixed top-0 start-0 w-100" style="z-index: 1060; height: 3px;">
        <div class="bg-primary h-100" style="animation: coa-loading 1.5s ease-in-out infinite;"></div>
    </div>

    @php
        $typeColors = ['asset' => 'primary', 'liability' => 'warning', 'income' => 'success', 'expense' => 'danger', 'equity' => 'info'];
        $typeIcons = ['asset' => 'fa-cube', 'liability' => 'fa-credit-card', 'income' => 'fa-arrow-down', 'expense' => 'fa-arrow-up', 'equity' => 'fa-pie-chart'];
        $typePlurals = ['asset' => 'Assets', 'liability' => 'Liabilities', 'income' => 'Income', 'expense' => 'Expenses', 'equity' => 'Equity'];
        $kpiTypes = ['asset', 'liability', 'income', 'expense'];

        $typeCounts = [];
        $totalAccounts = 0;
        foreach ($treeData as $typeKey => $typeData) {
            $count = 0;
            foreach ($typeData['categories'] as $cat) {
                $count += count($cat['accounts']);
            }
            $count += count($typeData['uncategorized']);
            $typeCounts[$typeKey] = $count;
            $totalAccounts += $count;
        }

        $hasActiveFilter = $search || $account_type || $account_category_id || $selectedAccountId;
    @endphp

    {{-- ══════════════ KPI Strip ══════════════ --}}
    <div class="row g-2 g-md-3 mb-3 coa-kpi-strip">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 coa-kpi-total">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="coa-kpi-icon coa-kpi-icon-lg bg-light text-primary">
                        <i class="fa fa-book"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing: .04em; font-size: .65rem;">Total Accounts</div>
                        <div class="d-flex align-items-baseline gap-2">
                            <span class="coa-kpi-number">{{ number_format($totalAccounts) }}</span>
                            <span class="text-muted small">heads</span>
                        </div>
                    </div>
                    @if ($hasActiveFilter)
                        <button type="button" class="btn btn-sm btn-light border rounded-pill flex-shrink-0" wire:click="$set('search', ''), $set('account_type', ''), $set('account_category_id', ''), $set('selectedAccountId', null)" title="Clear all filters" aria-label="Clear all filters">
                            <i class="fa fa-times me-1"></i> Clear
                        </button>
                    @endif
                </div>
            </div>
        </div>
        @foreach ($kpiTypes as $tk)
            @php
                $tc = $typeColors[$tk];
                $ti = $typeIcons[$tk];
                $tcount = $typeCounts[$tk] ?? 0;
                $active = $account_type === $tk;
            @endphp
            <div class="col-6 col-md-3 col-xl-2">
                <button type="button" wire:click="filterByType('{{ $tk }}')"
                    class="card border-0 shadow-sm rounded-3 w-100 h-100 text-start coa-kpi-tile coa-kpi-tile-{{ $tc }} {{ $active ? 'coa-kpi-active' : '' }}"
                    title="Filter by {{ $typePlurals[$tk] }}" aria-label="Filter by {{ $typePlurals[$tk] }}" aria-pressed="{{ $active ? 'true' : 'false' }}">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="coa-kpi-icon bg-{{ $tc }} bg-opacity-10 text-{{ $tc }}">
                                <i class="fa {{ $ti }}"></i>
                            </span>
                            @if ($active)
                                <i class="fa fa-check-circle text-{{ $tc }}" title="Active filter"></i>
                            @endif
                        </div>
                        <div class="text-muted small text-uppercase fw-semibold mb-0" style="letter-spacing: .04em; font-size: .65rem;">{{ $typePlurals[$tk] }}</div>
                        <div class="coa-kpi-number coa-kpi-number-sm text-{{ $tc }}">{{ number_format($tcount) }}</div>
                    </div>
                </button>
            </div>
        @endforeach
    </div>

    {{-- ══════════════ Toolbar ══════════════ --}}
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body py-3">
            {{-- Row 1: actions + global search (desktop), or actions only (mobile) --}}
            <div class="d-flex flex-wrap gap-2 align-items-center">
                {{-- Primary actions --}}
                <div class="d-flex gap-2 align-items-center flex-shrink-0">
                    @can('account.create')
                        <button class="btn btn-primary d-inline-flex gap-2 align-items-center shadow-sm rounded-pill px-3" id="AccountAdd" title="Add new account" aria-label="Add new account">
                            <i class="demo-psi-add fs-6"></i>
                            <span class="fw-semibold">Add Account</span>
                        </button>
                    @endcan

                    @can('account.delete')
                        @if (count($selected) > 0)
                            <button class="btn btn-danger d-inline-flex gap-2 align-items-center rounded-pill px-3"
                                wire:click="delete()"
                                wire:confirm="Delete {{ count($selected) }} selected accounts?"
                                title="Delete selected">
                                <i class="demo-pli-recycling"></i>
                                <span class="small fw-semibold">Delete {{ count($selected) }}</span>
                            </button>
                        @endif
                    @endcan

                    {{-- Secondary actions dropdown --}}
                    <div class="dropdown">
                        <button class="btn btn-light border rounded-pill d-inline-flex gap-1 align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More actions">
                            <i class="fa fa-ellipsis-h"></i>
                            <span class="d-none d-sm-inline small">More</span>
                        </button>
                        <ul class="dropdown-menu shadow-sm border-0 rounded-3">
                            @can('account.export')
                                <li>
                                    <button class="dropdown-item d-inline-flex align-items-center gap-2" wire:click="export()">
                                        <i class="fa fa-file-excel-o text-success"></i> Export to Excel
                                    </button>
                                </li>
                            @endcan
                            @can('account.create')
                                <li>
                                    <a class="dropdown-item d-inline-flex align-items-center gap-2" href="{{ route('account::import') }}">
                                        <i class="fa fa-cloud-upload text-primary"></i> Advanced Import
                                    </a>
                                </li>
                            @endcan
                            <li><hr class="dropdown-divider"></li>
                            <li class="dropdown-header small text-uppercase" style="font-size: .65rem; letter-spacing: .04em;">Visible Columns</li>
                            @foreach ($visibleColumnNames as $column => $label)
                                <li>
                                    <div class="form-check px-3 py-1" onclick="event.stopPropagation()">
                                        <input class="form-check-input" type="checkbox" wire:model.live="visibleColumns.{{ $column }}" id="col_{{ $column }}">
                                        <label class="form-check-label small" for="col_{{ $column }}">{{ $label }}</label>
                                    </div>
                                </li>
                            @endforeach
                            <li>
                                <button class="dropdown-item small text-muted d-inline-flex align-items-center gap-2" wire:click="resetColumnVisibility">
                                    <i class="fa fa-undo"></i> Reset columns
                                </button>
                            </li>
                        </ul>
                    </div>

                    {{-- Mobile-only sidebar toggle --}}
                    <button class="btn btn-light border rounded-pill d-lg-none d-inline-flex gap-1 align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#coaTreeCollapse" aria-expanded="false" aria-controls="coaTreeCollapse" title="Show account tree">
                        <i class="fa fa-sitemap"></i>
                        <span class="d-none d-sm-inline small">Tree</span>
                    </button>
                </div>

                {{-- Search grows to fill remaining space --}}
                <div class="coa-search flex-grow-1" style="min-width: 200px;">
                    <i class="fa fa-search coa-search-icon"></i>
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control rounded-pill ps-5"
                        placeholder="Search name, alias, mobile, email..." autofocus autocomplete="off" aria-label="Search accounts">
                </div>
            </div>

            {{-- Row 2: filter dropdowns --}}
            <div class="d-flex flex-wrap gap-2 mt-2 align-items-center">
                <div class="coa-select-wrap" wire:ignore>
                    {{ html()->select('account_type', accountTypes())->value('')->class('tomSelect')->id('account_type')->placeholder('Account Type') }}
                </div>
                <div class="coa-select-wrap" wire:ignore>
                    {{ html()->select('account_category_id', [])->value('')->class('select-account_category_id')->id('account_category_id')->placeholder('Category') }}
                </div>

                <select wire:model.live="limit" class="form-select form-select-sm rounded-pill ms-md-auto" style="width: auto;" aria-label="Rows per page">
                    <option value="10">10 / page</option>
                    <option value="100">100 / page</option>
                    <option value="500">500 / page</option>
                </select>
            </div>

            {{-- Chip toggle filters --}}
            <div class="d-flex flex-wrap gap-2 mt-3 pt-2 border-top">
                <span class="text-muted small text-uppercase fw-semibold align-self-center me-1" style="letter-spacing: .04em; font-size: .65rem;">
                    <i class="fa fa-filter me-1"></i> Quick filters:
                </span>

                <label class="coa-chip {{ $excludeCustomer ? 'coa-chip-active' : '' }}" title="Hide customer accounts from the list">
                    <input type="checkbox" wire:model.live="excludeCustomer" class="d-none">
                    <i class="fa {{ $excludeCustomer ? 'fa-eye-slash' : 'fa-eye' }} me-1"></i>
                    Exclude Customers
                </label>

                <label class="coa-chip {{ $excludeVendor ? 'coa-chip-active' : '' }}" title="Hide vendor accounts from the list">
                    <input type="checkbox" wire:model.live="excludeVendor" class="d-none">
                    <i class="fa {{ $excludeVendor ? 'fa-eye-slash' : 'fa-eye' }} me-1"></i>
                    Exclude Vendors
                </label>

                @if ($hasActiveFilter)
                    <span class="ms-auto text-muted small align-self-center">
                        <i class="fa fa-info-circle me-1"></i>
                        Showing filtered results · <a href="#" wire:click.prevent="$set('search', ''), $set('account_type', ''), $set('account_category_id', ''), $set('selectedAccountId', null)" class="text-decoration-none">clear all</a>
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════ Main Content ══════════════ --}}
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-0">
            <div class="row g-0">
                {{-- Tree Sidebar --}}
                <div class="col-lg-3 border-end coa-sidebar-col collapse d-lg-block" id="coaTreeCollapse">
                    <div class="coa-sidebar-sticky">
                        <div class="px-3 pt-3 pb-2 border-bottom bg-light bg-opacity-50">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: 0.05em;">
                                    <i class="fa fa-sitemap me-1"></i> Account Tree
                                </span>
                                <span class="text-muted small">{{ number_format($totalAccounts) }} total</span>
                            </div>
                            <div class="position-relative">
                                <i class="fa fa-search position-absolute text-muted" style="left: 10px; top: 50%; transform: translateY(-50%); font-size: .75rem;"></i>
                                <input type="search" id="coaTreeFilter" class="form-control form-control-sm rounded-pill ps-4" placeholder="Filter tree..." aria-label="Filter account tree">
                            </div>
                        </div>
                    </div>

                    <div class="coa-sidebar-scroll px-3 py-2">
                        <div class="coa-tree">
                            @foreach ($treeData as $typeKey => $typeData)
                                @php
                                    $isTypeExpanded = $this->isTypeExpanded($typeKey);
                                    $tc = $typeColors[$typeKey] ?? 'secondary';
                                @endphp
                                <div class="mb-2 coa-tree-section" data-type="{{ $typeKey }}" data-label="{{ strtolower($typeData['label']) }}">
                                    <div class="d-flex align-items-center gap-1 py-2 px-2 rounded-2 coa-tree-node {{ $account_type === $typeKey ? 'coa-active-type coa-active-'.$tc : '' }}">
                                        <button type="button" wire:click.stop="toggleType('{{ $typeKey }}')" class="btn btn-link btn-sm p-0 border-0 d-inline-flex align-items-center justify-content-center text-muted" style="width: 18px; height: 18px;" aria-label="Toggle {{ $typeData['label'] }} section">
                                            <i class="fa fa-chevron-{{ $isTypeExpanded ? 'down' : 'right' }}" style="font-size: 0.65rem;"></i>
                                        </button>
                                        <button type="button" wire:click="filterByType('{{ $typeKey }}')" class="btn btn-link text-decoration-none p-0 d-flex align-items-center gap-2 flex-grow-1 text-start text-reset" aria-label="Filter by {{ $typeData['label'] }}">
                                            <i class="fa {{ $typeIcons[$typeKey] ?? 'fa-folder' }} text-{{ $tc }}" style="font-size: .85rem;"></i>
                                            <span class="fw-semibold small">{{ $typeData['label'] }}</span>
                                            <span class="badge bg-{{ $tc }} bg-opacity-10 text-{{ $tc }} ms-auto rounded-pill" style="font-size: 0.65rem;">
                                                {{ $typeCounts[$typeKey] ?? 0 }}
                                            </span>
                                        </button>
                                    </div>

                                    @if ($isTypeExpanded)
                                        <div class="ms-3 mt-1 coa-tree-children">
                                            @foreach ($typeData['categories'] as $categoryId => $category)
                                                @php $isCatExpanded = $this->isCategoryExpanded($categoryId); @endphp
                                                <div class="mb-1 coa-tree-cat" data-label="{{ strtolower($category['name']) }}">
                                                    <div class="d-flex align-items-center gap-1 py-1 px-2 rounded-2 coa-tree-node {{ $account_category_id == $categoryId ? 'coa-active-cat' : '' }}">
                                                        <button type="button" wire:click.stop="toggleCategory({{ $categoryId }})" class="btn btn-link btn-sm p-0 border-0 d-inline-flex align-items-center justify-content-center text-muted" style="width: 16px; height: 16px;" aria-label="Toggle {{ $category['name'] }} category">
                                                            <i class="fa fa-chevron-{{ $isCatExpanded ? 'down' : 'right' }}" style="font-size: 0.6rem;"></i>
                                                        </button>
                                                        <button type="button" wire:click.stop="filterByCategory({{ $categoryId }})" class="btn btn-link text-decoration-none p-0 d-flex align-items-center gap-2 flex-grow-1 text-start text-reset" aria-label="Filter by {{ $category['name'] }}">
                                                            <i class="fa fa-{{ $isCatExpanded ? 'folder-open-o' : 'folder-o' }} text-muted" style="font-size: .75rem;"></i>
                                                            <span class="small">{{ $category['name'] }}</span>
                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary ms-auto rounded-pill" style="font-size: 0.6rem;">{{ count($category['accounts']) }}</span>
                                                        </button>
                                                    </div>

                                                    @if ($isCatExpanded)
                                                        <div class="ms-3 mt-1 coa-leaf-scroll">
                                                            @foreach ($category['accounts'] as $account)
                                                                <button type="button" class="coa-tree-leaf d-block w-100 text-start border-0 bg-transparent rounded-1 mb-1 {{ $selectedAccountId == $account['id'] ? 'coa-active-leaf' : '' }}"
                                                                    wire:click.stop="filterByAccount({{ $account['id'] }})" data-label="{{ strtolower($account['name'].' '.($account['alias_name'] ?? '')) }}"
                                                                    title="{{ $account['name'] }}" aria-label="Filter by account {{ $account['name'] }}">
                                                                    <span class="small text-truncate d-block">{{ $account['name'] }}</span>
                                                                    @if ($account['alias_name'])
                                                                        <span class="d-block text-muted text-truncate" style="font-size: 0.7rem;">{{ $account['alias_name'] }}</span>
                                                                    @endif
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach

                                            @if (!empty($typeData['uncategorized']))
                                                @php $isUncatExpanded = $this->isCategoryExpanded(0); @endphp
                                                <div class="mb-1 coa-tree-cat" data-label="uncategorized">
                                                    <div class="d-flex align-items-center gap-1 py-1 px-2 rounded-2 coa-tree-node">
                                                        <button type="button" wire:click.stop="toggleCategory(0)" class="btn btn-link btn-sm p-0 border-0 d-inline-flex align-items-center justify-content-center text-muted" style="width: 16px; height: 16px;" aria-label="Toggle uncategorized">
                                                            <i class="fa fa-chevron-{{ $isUncatExpanded ? 'down' : 'right' }}" style="font-size: 0.6rem;"></i>
                                                        </button>
                                                        <span class="d-flex align-items-center gap-2 flex-grow-1">
                                                            <i class="fa fa-question-circle-o text-muted" style="font-size: .75rem;"></i>
                                                            <span class="small text-muted fst-italic">Uncategorized</span>
                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary ms-auto rounded-pill" style="font-size: 0.6rem;">{{ count($typeData['uncategorized']) }}</span>
                                                        </span>
                                                    </div>

                                                    @if ($isUncatExpanded)
                                                        <div class="ms-3 mt-1 coa-leaf-scroll">
                                                            @foreach ($typeData['uncategorized'] as $account)
                                                                <button type="button" class="coa-tree-leaf d-block w-100 text-start border-0 bg-transparent rounded-1 mb-1 {{ $selectedAccountId == $account['id'] ? 'coa-active-leaf' : '' }}"
                                                                    wire:click.stop="filterByAccount({{ $account['id'] }})" data-label="{{ strtolower($account['name'] ?? '') }}"
                                                                    title="{{ $account['name'] }}" aria-label="Filter by account {{ $account['name'] }}">
                                                                    <span class="small text-truncate d-block">{{ $account['name'] }}</span>
                                                                </button>
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
                                <div class="text-center text-muted py-5 small fst-italic">
                                    <i class="fa fa-folder-open-o d-block mb-2" style="font-size: 2rem; opacity: .3;"></i>
                                    No accounts yet
                                </div>
                            @endif

                            <div id="coaTreeEmpty" class="text-center text-muted py-3 small d-none">
                                <i class="fa fa-search me-1"></i> No matches in tree
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Data Table --}}
                <div class="col-lg-9">
                    {{-- Skeleton shimmer while Livewire is busy --}}
                    <div wire:loading.delay wire:target="search, limit, account_type, account_category_id, excludeCustomer, excludeVendor, filterByType, filterByCategory, filterByAccount, sortBy, delete, gotoPage, nextPage, previousPage, resetColumnVisibility" class="coa-skeleton-wrap">
                        @for ($s = 0; $s < 5; $s++)
                            <div class="coa-skeleton-row"></div>
                        @endfor
                    </div>

                    <div wire:loading.remove wire:target="search, limit, account_type, account_category_id, excludeCustomer, excludeVendor, filterByType, filterByCategory, filterByAccount, sortBy, delete, gotoPage, nextPage, previousPage, resetColumnVisibility" class="table-responsive">
                        <table class="table table-hover align-middle mb-0 coa-table">
                            <thead>
                                <tr>
                                    <th class="border-0 ps-3" style="width: 3%;">
                                        <div class="form-check mb-0">
                                            <input type="checkbox" class="form-check-input" wire:model.live="selectAll" aria-label="Select all">
                                        </div>
                                    </th>
                                    @if ($visibleColumns['id'] ?? true)
                                        <th class="border-0 coa-col-id d-none d-md-table-cell" style="width: 5%;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['account_type'] ?? true)
                                        <th class="border-0" style="width: 10%;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_type" label="Type" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['account_category'] ?? true)
                                        <th class="border-0 d-none d-md-table-cell" style="width: 15%;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_category_id" label="Category" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['name'] ?? true)
                                        <th class="border-0">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Name" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['alias_name'] ?? true)
                                        <th class="border-0 d-none d-lg-table-cell" style="width: 12%;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="alias_name" label="Alias" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['description'] ?? true)
                                        <th class="border-0 d-none d-xl-table-cell" style="width: 18%;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="description" label="Description" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['model'] ?? true)
                                        <th class="border-0 d-none d-md-table-cell" style="width: 8%;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="model" label="Model" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['created_at'] ?? false)
                                        <th class="border-0 d-none d-lg-table-cell" style="width: 10%;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_at" label="Created" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['updated_at'] ?? false)
                                        <th class="border-0 d-none d-lg-table-cell" style="width: 10%;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="updated_at" label="Updated" />
                                        </th>
                                    @endif
                                    <th class="border-0 text-end pe-3" style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data as $item)
                                    @php
                                        $rowTc = $typeColors[$item->account_type] ?? 'secondary';
                                        $aliasHidden = !($visibleColumns['alias_name'] ?? true);
                                    @endphp
                                    <tr class="coa-row {{ in_array($item->id, $selected) ? 'coa-row-selected' : '' }}">
                                        <td class="ps-3">
                                            <div class="form-check mb-0">
                                                <input type="checkbox" class="form-check-input" value="{{ $item->id }}" wire:model.live="selected" aria-label="Select {{ $item->name }}">
                                            </div>
                                        </td>
                                        @if ($visibleColumns['id'] ?? true)
                                            <td class="text-muted small d-none d-md-table-cell">#{{ $item->id }}</td>
                                        @endif
                                        @if ($visibleColumns['account_type'] ?? true)
                                            <td>
                                                @if ($item->account_type)
                                                    <span class="badge rounded-pill bg-{{ $rowTc }} bg-opacity-10 text-{{ $rowTc }} px-3 py-1">
                                                        <i class="fa {{ $typeIcons[$item->account_type] ?? 'fa-circle-o' }} me-1" style="font-size: .65rem;"></i>
                                                        {{ ucfirst($item->account_type) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        @endif
                                        @if ($visibleColumns['account_category'] ?? true)
                                            <td class="text-muted small d-none d-md-table-cell">
                                                @if ($item->accountCategory?->name)
                                                    <i class="fa fa-folder-o me-1 text-muted" style="font-size: .7rem;"></i>{{ $item->accountCategory->name }}
                                                @else
                                                    <span class="text-muted fst-italic">Uncategorized</span>
                                                @endif
                                            </td>
                                        @endif
                                        @if ($visibleColumns['name'] ?? true)
                                            <td>
                                                <a href="{{ route('account::view', $item->id) }}" class="text-decoration-none fw-semibold coa-name-link">{{ $item->name }}</a>
                                                @if ($aliasHidden && $item->alias_name)
                                                    <div class="text-muted small">{{ $item->alias_name }}</div>
                                                @endif
                                            </td>
                                        @endif
                                        @if ($visibleColumns['alias_name'] ?? true)
                                            <td class="text-muted small d-none d-lg-table-cell">{{ $item->alias_name ?: '—' }}</td>
                                        @endif
                                        @if ($visibleColumns['description'] ?? true)
                                            <td class="text-muted small text-truncate d-none d-xl-table-cell" style="max-width: 220px;" title="{{ $item->description }}">{{ $item->description ?: '—' }}</td>
                                        @endif
                                        @if ($visibleColumns['model'] ?? true)
                                            <td class="d-none d-md-table-cell">
                                                @if ($item->model)
                                                    @php $mc = $item->model === 'Customer' ? 'info' : 'warning'; @endphp
                                                    <span class="badge rounded-pill bg-{{ $mc }} bg-opacity-10 text-{{ $mc }} px-2">
                                                        <i class="fa {{ $item->model === 'Customer' ? 'fa-user-o' : 'fa-truck' }} me-1" style="font-size: .65rem;"></i>{{ $item->model }}
                                                    </span>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                        @endif
                                        @if ($visibleColumns['created_at'] ?? false)
                                            <td class="text-muted small d-none d-lg-table-cell" title="{{ $item->created_at?->format('Y-m-d H:i:s') }}">
                                                @if ($item->created_at)
                                                    <i class="fa fa-clock-o me-1" style="font-size: .7rem;"></i>{{ $item->created_at->diffForHumans() }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        @endif
                                        @if ($visibleColumns['updated_at'] ?? false)
                                            <td class="text-muted small d-none d-lg-table-cell" title="{{ $item->updated_at?->format('Y-m-d H:i:s') }}">
                                                @if ($item->updated_at)
                                                    <i class="fa fa-pencil-square-o me-1" style="font-size: .7rem;"></i>{{ $item->updated_at->diffForHumans() }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        @endif
                                        <td class="text-end pe-3">
                                            <div class="coa-row-actions">
                                                @can('account.edit')
                                                    <button class="btn btn-light edit border-0 rounded-circle d-inline-flex align-items-center justify-content-center"
                                                        table_id="{{ $item->id }}" title="Edit {{ $item->name }}" aria-label="Edit {{ $item->name }}" style="width: 30px; height: 30px;">
                                                        <i class="demo-psi-pencil" style="font-size: .8rem;"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="20" class="border-0">
                                            <div class="coa-empty text-center py-5">
                                                <i class="fa {{ $hasActiveFilter ? 'fa-search' : 'fa-folder-open-o' }} d-block mb-3 text-muted" style="font-size: 3rem; opacity: .25;"></i>
                                                <h6 class="fw-bold text-muted mb-2">
                                                    {{ $hasActiveFilter ? 'No accounts match your filters' : 'No accounts yet' }}
                                                </h6>
                                                <p class="text-muted small mb-3">
                                                    {{ $hasActiveFilter ? 'Try adjusting or clearing your search and filters.' : 'Get started by adding your first account head or bulk-importing from a spreadsheet.' }}
                                                </p>
                                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                                    @if ($hasActiveFilter)
                                                        <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" wire:click="$set('search', ''), $set('account_type', ''), $set('account_category_id', ''), $set('selectedAccountId', null)">
                                                            <i class="fa fa-times me-1"></i> Clear filters
                                                        </button>
                                                    @else
                                                        @can('account.create')
                                                            <button class="btn btn-primary btn-sm rounded-pill px-3" id="AccountAddEmpty" onclick="document.getElementById('AccountAdd')?.click()">
                                                                <i class="demo-psi-add me-1"></i> Add your first account
                                                            </button>
                                                            <a href="{{ route('account::import') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                                                <i class="fa fa-cloud-upload me-1"></i> Import from file
                                                            </a>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($data->hasPages())
                        <div class="p-3 border-top bg-light bg-opacity-50 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span class="text-muted small">
                                Showing <strong>{{ $data->firstItem() }}</strong>–<strong>{{ $data->lastItem() }}</strong> of <strong>{{ number_format($data->total()) }}</strong>
                            </span>
                            <div>{{ $data->links() }}</div>
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

        @keyframes coa-shimmer {
            0% { background-position: -400px 0; }
            100% { background-position: 400px 0; }
        }

        /* ─── KPI cards ─── */
        .coa-kpi-strip { scroll-snap-type: x mandatory; }
        @media (max-width: 575.98px) {
            .coa-kpi-strip { flex-wrap: nowrap !important; overflow-x: auto; padding-bottom: .25rem; }
            .coa-kpi-strip > [class*='col-'] { flex: 0 0 78%; max-width: 78%; scroll-snap-align: start; }
        }

        .coa-kpi-total .card-body { background: linear-gradient(135deg, rgba(13, 110, 253, 0.04) 0%, rgba(13, 110, 253, 0) 50%); }

        .coa-kpi-tile { cursor: pointer; transition: transform .15s ease, box-shadow .15s ease; background: var(--bs-card-bg, #fff); }
        .coa-kpi-tile:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.06) !important; }
        .coa-kpi-tile:focus-visible { outline: 2px solid var(--bs-primary); outline-offset: 2px; }
        .coa-kpi-tile.coa-kpi-active { box-shadow: 0 0 0 2px currentColor inset, 0 .25rem .75rem rgba(0,0,0,.05) !important; }
        .coa-kpi-tile-primary.coa-kpi-active { color: var(--bs-primary); }
        .coa-kpi-tile-warning.coa-kpi-active { color: var(--bs-warning); }
        .coa-kpi-tile-success.coa-kpi-active { color: var(--bs-success); }
        .coa-kpi-tile-danger.coa-kpi-active { color: var(--bs-danger); }
        .coa-kpi-tile-info.coa-kpi-active { color: var(--bs-info); }

        .coa-kpi-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            font-size: 1.15rem;
            line-height: 1;
            flex-shrink: 0;
        }
        .coa-kpi-icon-lg { width: 52px; height: 52px; font-size: 1.4rem; border-radius: 12px; }
        .coa-kpi-icon .fa { line-height: 1; }

        .coa-kpi-number { font-size: 1.5rem; font-weight: 700; line-height: 1.1; color: var(--bs-body-color); }
        .coa-kpi-number-sm { font-size: 1.25rem; font-weight: 700; line-height: 1.1; margin-top: .15rem; }

        /* ─── Search input ─── */
        .coa-search { position: relative; min-width: 0; }
        .coa-search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--bs-secondary-color, #6c757d); pointer-events: none; font-size: .85rem; z-index: 2; }
        .coa-search input { box-shadow: 0 1px 2px rgba(0,0,0,.04); border: 1px solid var(--bs-border-color, #dee2e6); transition: border-color .15s, box-shadow .15s; }
        .coa-search input:focus { border-color: var(--bs-primary); box-shadow: 0 0 0 .15rem rgba(13, 110, 253, .15); }

        /* ─── Filter select wrappers ─── */
        .coa-select-wrap { min-width: 160px; flex: 1 1 160px; max-width: 220px; }
        @media (max-width: 575.98px) {
            .coa-select-wrap { max-width: none; }
        }

        /* ─── Chip toggles ─── */
        .coa-chip {
            display: inline-flex;
            align-items: center;
            padding: .35rem .85rem;
            border: 1px solid var(--bs-border-color, #dee2e6);
            border-radius: 999px;
            font-size: .75rem;
            cursor: pointer;
            background: var(--bs-body-bg, #fff);
            color: var(--bs-secondary-color, #6c757d);
            transition: all .15s ease;
            user-select: none;
            margin: 0;
        }
        .coa-chip:hover { border-color: var(--bs-primary); color: var(--bs-primary); }
        .coa-chip-active { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
        .coa-chip-active:hover { color: #fff; }
        .coa-chip:focus-within { outline: 2px solid var(--bs-primary); outline-offset: 2px; }

        /* ─── Sidebar ─── */
        .coa-sidebar-col { background: var(--bs-body-bg, #fff); }
        .coa-sidebar-sticky { position: sticky; top: 0; z-index: 3; }
        .coa-sidebar-scroll { max-height: 70vh; overflow-y: auto; }
        @media (max-width: 991.98px) {
            .coa-sidebar-col { border-right: 0 !important; border-bottom: 1px solid var(--bs-border-color, #dee2e6); }
            .coa-sidebar-scroll { max-height: 45vh; }
        }

        .coa-tree { font-size: 0.78rem; }
        .coa-tree-node {
            transition: background-color .15s ease, transform .1s ease;
            padding: 0.3rem 0.5rem !important;
            border-left: 3px solid transparent;
        }
        .coa-tree-node:hover { background-color: rgba(0,0,0,.03); }
        .coa-tree-node button { color: inherit; font-size: inherit; }
        .coa-tree-node button:focus-visible { outline: 2px solid var(--bs-primary); outline-offset: 2px; border-radius: 4px; }

        .coa-tree-leaf {
            transition: background-color .15s ease, transform .1s ease, padding-left .15s ease;
            padding: 0.3rem 0.6rem !important;
            cursor: pointer;
            border-left: 2px solid transparent;
        }
        .coa-tree-leaf:hover { background-color: rgba(0,0,0,.03); padding-left: 0.85rem !important; }
        .coa-tree-leaf:focus-visible { outline: 2px solid var(--bs-primary); outline-offset: 1px; border-radius: 4px; }

        .coa-leaf-scroll { max-height: 280px; overflow-y: auto; }

        /* Active states */
        .coa-active-type { background-color: rgba(0,0,0,.025) !important; }
        .coa-active-primary { border-left-color: var(--bs-primary) !important; }
        .coa-active-warning { border-left-color: var(--bs-warning) !important; }
        .coa-active-success { border-left-color: var(--bs-success) !important; }
        .coa-active-danger  { border-left-color: var(--bs-danger)  !important; }
        .coa-active-info    { border-left-color: var(--bs-info)    !important; }
        .coa-active-cat   { background-color: rgba(13, 110, 253, .06) !important; border-left-color: var(--bs-primary) !important; }
        .coa-active-leaf  { background-color: rgba(217, 119, 6, .08) !important; border-left-color: #d97706 !important; font-weight: 500; }

        /* ─── Table ─── */
        .coa-table thead th {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6c757d;
            padding: .85rem .75rem !important;
            background: rgba(0,0,0,.015);
            border-top: none;
            border-bottom: 1px solid rgba(0,0,0,.06);
            font-weight: 600;
        }
        .coa-table tbody td {
            padding: .65rem .75rem !important;
            vertical-align: middle;
            border-color: rgba(0,0,0,.04);
        }
        .coa-table tbody tr.coa-row { transition: background-color .12s ease; }
        .coa-table tbody tr.coa-row:hover { background-color: rgba(13, 110, 253, .035); }
        .coa-table tbody tr.coa-row-selected { background-color: rgba(13, 110, 253, .06) !important; }

        .coa-table .badge { font-size: .68rem; font-weight: 500; }
        .coa-table .form-check-input { width: 1em; height: 1em; }
        .coa-name-link { color: var(--bs-body-color); transition: color .12s ease; }
        .coa-name-link:hover { color: var(--bs-primary); }

        /* Edit reveal-on-hover (desktop only) */
        @media (min-width: 992px) {
            .coa-row-actions { opacity: 0; transition: opacity .15s ease; }
            .coa-row:hover .coa-row-actions,
            .coa-row:focus-within .coa-row-actions { opacity: 1; }
        }

        /* Empty state */
        .coa-empty h6 { font-size: 1rem; }

        /* Skeleton shimmer */
        .coa-skeleton-wrap { padding: 1rem; }
        .coa-skeleton-row {
            height: 42px;
            margin-bottom: .5rem;
            border-radius: 6px;
            background: linear-gradient(90deg, rgba(0,0,0,.04) 0%, rgba(0,0,0,.08) 50%, rgba(0,0,0,.04) 100%);
            background-size: 400px 100%;
            animation: coa-shimmer 1.2s ease-in-out infinite;
        }

        /* Dark mode adjustments */
        [data-bs-theme="dark"] .coa-table thead th { background: rgba(255,255,255,.025); border-bottom-color: rgba(255,255,255,.08); }
        [data-bs-theme="dark"] .coa-table tbody td { border-color: rgba(255,255,255,.06); }
        [data-bs-theme="dark"] .coa-table tbody tr.coa-row:hover { background-color: rgba(255,255,255,.03); }
        [data-bs-theme="dark"] .coa-tree-node:hover,
        [data-bs-theme="dark"] .coa-tree-leaf:hover { background-color: rgba(255,255,255,.04); }
        [data-bs-theme="dark"] .coa-active-type { background-color: rgba(255,255,255,.04) !important; }
        [data-bs-theme="dark"] .coa-skeleton-row { background: linear-gradient(90deg, rgba(255,255,255,.04) 0%, rgba(255,255,255,.08) 50%, rgba(255,255,255,.04) 100%); background-size: 400px 100%; }
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

                // Client-side tree filter — does NOT change Livewire state
                $(document).on('input', '#coaTreeFilter', function() {
                    const q = $(this).val().trim().toLowerCase();
                    const $sections = $('.coa-tree-section');
                    let anyVisible = false;

                    if (!q) {
                        $sections.show();
                        $('.coa-tree-cat, .coa-tree-leaf').show();
                        $('#coaTreeEmpty').addClass('d-none');
                        return;
                    }

                    $sections.each(function() {
                        const $section = $(this);
                        const sectionLabel = $section.data('label') || '';
                        let sectionMatch = sectionLabel.includes(q);
                        let childMatch = false;

                        $section.find('.coa-tree-cat').each(function() {
                            const $cat = $(this);
                            const catLabel = ($cat.data('label') || '').toString();
                            let catMatch = catLabel.includes(q);
                            let leafMatch = false;

                            $cat.find('.coa-tree-leaf').each(function() {
                                const leafLabel = ($(this).data('label') || '').toString();
                                if (leafLabel.includes(q)) {
                                    $(this).show();
                                    leafMatch = true;
                                } else if (catMatch) {
                                    $(this).show();
                                } else {
                                    $(this).hide();
                                }
                            });

                            if (catMatch || leafMatch) {
                                $cat.show();
                                childMatch = true;
                            } else {
                                $cat.hide();
                            }
                        });

                        $section.find('.coa-tree-leaf').not('.coa-tree-cat .coa-tree-leaf').each(function() {
                            const leafLabel = ($(this).data('label') || '').toString();
                            if (leafLabel.includes(q)) {
                                $(this).show();
                                childMatch = true;
                            } else if (!sectionMatch) {
                                $(this).hide();
                            }
                        });

                        if (sectionMatch || childMatch) {
                            $section.show();
                            anyVisible = true;
                        } else {
                            $section.hide();
                        }
                    });

                    $('#coaTreeEmpty').toggleClass('d-none', anyVisible);
                });
            });
        </script>
    @endpush
</div>
