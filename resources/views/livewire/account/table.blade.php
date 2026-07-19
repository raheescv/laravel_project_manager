<div>
    {{--
        ╔══════════════════════════════════════════════════════════════════════╗
        ║  Chart of Accounts — "Premium Compact" design system (.coax)          ║
        ║  Accent derives from the active SETTINGS THEME (--bs-primary) so it   ║
        ║  tracks the colour scheme AND light / dark mode automatically.        ║
        ║  All Livewire bindings preserved.                                     ║
        ╚══════════════════════════════════════════════════════════════════════╝
    --}}

    {{-- Top loading bar --}}
    <div wire:loading.delay wire:target="search, limit, account_type, account_category_id, excludeCustomer, excludeVendor, filterByType, filterByCategory, filterByAccount, sortBy, delete, gotoPage, nextPage, previousPage, resetColumnVisibility" class="position-fixed top-0 start-0 w-100" style="z-index: 1060; height: 3px;">
        <div class="bg-primary h-100" style="animation: coax-loading 1.5s ease-in-out infinite;"></div>
    </div>

    @php
        $typeTones = ['asset' => 'asset', 'liability' => 'liab', 'income' => 'income', 'expense' => 'expense', 'equity' => 'equity'];
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

    <div class="coax">

        {{-- ══════════════ HERO with integrated KPI rail ══════════════ --}}
        <div class="coax-hero">
            <span class="glow"></span>
            <div class="coax-hero-inner">
                <div class="doc-ic"><i class="fa fa-book"></i></div>
                <div class="h-main">
                    <div class="h-eyebrow">Accounts · Ledger</div>
                    <div class="h-ref">Chart of Accounts</div>
                    <div class="h-sub">All account heads, categories &amp; ledgers in one place</div>
                </div>
                <div class="h-right">
                    @can('account.delete')
                        @if (count($selected) > 0)
                            <button type="button" class="btn-hero-danger" wire:click="delete()" wire:confirm="Delete {{ count($selected) }} selected accounts?" title="Delete selected accounts">
                                <i class="fa fa-trash-o"></i> Delete {{ count($selected) }}
                            </button>
                        @endif
                    @endcan
                    @can('account.export')
                        <button type="button" class="btn-ghost" wire:click="export()" title="Export to Excel">
                            <i class="fa fa-file-excel-o"></i><span class="d-none d-sm-inline"> Export</span>
                        </button>
                    @endcan
                    @can('account.create')
                        <a class="btn-ghost" href="{{ route('account::import') }}" title="Advanced import">
                            <i class="fa fa-cloud-upload"></i><span class="d-none d-sm-inline"> Import</span>
                        </a>
                        <button type="button" class="btn-hero" id="AccountAdd" title="Add new account" aria-label="Add new account">
                            <i class="fa fa-plus"></i> Add Account
                        </button>
                    @endcan
                </div>
            </div>
            <div class="coax-hstats">
                <button type="button" class="coax-hs {{ !$account_type ? 'on' : '' }}"
                    wire:click="$set('search', ''), $set('account_type', ''), $set('account_category_id', ''), $set('selectedAccountId', null)"
                    title="Show all accounts" aria-pressed="{{ !$account_type ? 'true' : 'false' }}">
                    <div class="s-k"><span class="ci"><i class="fa fa-list"></i></span> Total Heads</div>
                    <div class="s-v">{{ number_format($totalAccounts) }}</div>
                </button>
                @foreach ($kpiTypes as $tk)
                    <button type="button" class="coax-hs {{ $account_type === $tk ? 'on' : '' }}" wire:click="filterByType('{{ $tk }}')"
                        title="Filter by {{ $typePlurals[$tk] }}" aria-pressed="{{ $account_type === $tk ? 'true' : 'false' }}">
                        <div class="s-k"><span class="ci"><i class="fa {{ $typeIcons[$tk] }}"></i></span> {{ $typePlurals[$tk] }}</div>
                        <div class="s-v">{{ number_format($typeCounts[$tk] ?? 0) }}</div>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ══════════════ TOOLBAR ══════════════ --}}
        <div class="coax-toolbar">
            <div class="coax-tb-row">
                <div class="coax-search">
                    <i class="fa fa-search"></i>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, alias, mobile, email…" autofocus autocomplete="off" aria-label="Search accounts">
                </div>
                <div class="coax-select-wrap" wire:ignore>
                    {{ html()->select('account_type', accountTypes())->value('')->class('tomSelect')->id('account_type')->placeholder('Account Type') }}
                </div>
                <div class="coax-select-wrap" wire:ignore>
                    {{ html()->select('account_category_id', [])->value('')->class('select-account_category_id')->id('account_category_id')->placeholder('Category') }}
                </div>
                <div class="coax-divider d-none d-md-block"></div>
                <select wire:model.live="limit" class="coax-select" aria-label="Rows per page">
                    <option value="10">10 / page</option>
                    <option value="100">100 / page</option>
                    <option value="500">500 / page</option>
                </select>
                <div class="dropdown">
                    <button class="coax-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Visible columns">
                        <i class="fa fa-columns"></i><span class="d-none d-sm-inline"> Columns</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                        <li class="dropdown-header small text-uppercase" style="font-size: .65rem; letter-spacing: .04em;">Visible Columns</li>
                        @foreach ($visibleColumnNames as $column => $label)
                            <li>
                                <div class="form-check px-3 py-1" onclick="event.stopPropagation()">
                                    <input class="form-check-input" type="checkbox" wire:model.live="visibleColumns.{{ $column }}" id="col_{{ $column }}">
                                    <label class="form-check-label small" for="col_{{ $column }}">{{ $label }}</label>
                                </div>
                            </li>
                        @endforeach
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button class="dropdown-item small text-muted d-inline-flex align-items-center gap-2" wire:click="resetColumnVisibility">
                                <i class="fa fa-undo"></i> Reset columns
                            </button>
                        </li>
                    </ul>
                </div>
                {{-- Mobile-only tree toggle --}}
                <button class="coax-btn d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#coaTreeCollapse" aria-expanded="false" aria-controls="coaTreeCollapse" title="Show account tree">
                    <i class="fa fa-sitemap"></i>
                </button>
            </div>
            <div class="coax-tb-row">
                <span class="coax-chip-lbl"><i class="fa fa-filter"></i> Quick</span>
                <label class="coax-chip {{ $excludeCustomer ? 'on' : '' }}" title="Hide customer accounts from the list">
                    <input type="checkbox" wire:model.live="excludeCustomer" class="d-none">
                    <i class="fa {{ $excludeCustomer ? 'fa-eye-slash' : 'fa-eye' }}"></i> Exclude Customers
                </label>
                <label class="coax-chip {{ $excludeVendor ? 'on' : '' }}" title="Hide vendor accounts from the list">
                    <input type="checkbox" wire:model.live="excludeVendor" class="d-none">
                    <i class="fa {{ $excludeVendor ? 'fa-eye-slash' : 'fa-eye' }}"></i> Exclude Vendors
                </label>
                @if ($hasActiveFilter)
                    <span class="coax-tb-meta ms-auto">
                        <i class="fa fa-info-circle"></i> Showing filtered results ·
                        <a href="#" wire:click.prevent="$set('search', ''), $set('account_type', ''), $set('account_category_id', ''), $set('selectedAccountId', null)">clear all</a>
                    </span>
                @endif
            </div>
        </div>

        {{-- ══════════════ MAIN SPLIT ══════════════ --}}
        <div class="coax-main">

            {{-- Tree Sidebar --}}
            <div class="collapse d-lg-block coax-tree-wrap" id="coaTreeCollapse">
                <div class="coax-card coax-tree-card">
                    <div class="coax-tree-head">
                        <span class="tt"><i class="fa fa-sitemap"></i> Account Tree</span>
                        <span class="tc">{{ number_format($totalAccounts) }} total</span>
                    </div>
                    <div class="coax-tree-search">
                        <div class="coax-search">
                            <i class="fa fa-search"></i>
                            <input type="search" id="coaTreeFilter" placeholder="Filter tree…" aria-label="Filter account tree">
                        </div>
                    </div>
                    <div class="coax-tree-body">
                        @foreach ($treeData as $typeKey => $typeData)
                            @php
                                $isTypeExpanded = $this->isTypeExpanded($typeKey);
                                $tone = $typeTones[$typeKey] ?? 'asset';
                            @endphp
                            <div class="coa-tree-section" data-type="{{ $typeKey }}" data-label="{{ strtolower($typeData['label']) }}">
                                <div class="coax-tnode {{ $account_type === $typeKey ? 'on' : '' }}">
                                    <button type="button" wire:click.stop="toggleType('{{ $typeKey }}')" class="chev-btn" aria-label="Toggle {{ $typeData['label'] }} section">
                                        <i class="fa fa-chevron-{{ $isTypeExpanded ? 'down' : 'right' }}"></i>
                                    </button>
                                    <button type="button" wire:click="filterByType('{{ $typeKey }}')" class="tnode-btn" aria-label="Filter by {{ $typeData['label'] }}">
                                        <span class="tic t-{{ $tone }}"><i class="fa {{ $typeIcons[$typeKey] ?? 'fa-folder' }}"></i></span>
                                        <span class="tlbl">{{ $typeData['label'] }}</span>
                                        <span class="tbadge">{{ $typeCounts[$typeKey] ?? 0 }}</span>
                                    </button>
                                </div>

                                @if ($isTypeExpanded)
                                    <div class="coax-tchild">
                                        @foreach ($typeData['categories'] as $categoryId => $category)
                                            @php $isCatExpanded = $this->isCategoryExpanded($categoryId); @endphp
                                            <div class="coa-tree-cat" data-label="{{ strtolower($category['name']) }}">
                                                <div class="coax-tcat {{ $account_category_id == $categoryId ? 'on' : '' }}">
                                                    <button type="button" wire:click.stop="toggleCategory({{ $categoryId }})" class="chev-btn" aria-label="Toggle {{ $category['name'] }} category">
                                                        <i class="fa fa-chevron-{{ $isCatExpanded ? 'down' : 'right' }}"></i>
                                                    </button>
                                                    <button type="button" wire:click.stop="filterByCategory({{ $categoryId }})" class="tnode-btn" aria-label="Filter by {{ $category['name'] }}">
                                                        <i class="fa fa-{{ $isCatExpanded ? 'folder-open-o' : 'folder-o' }} fld"></i>
                                                        <span class="tlbl">{{ $category['name'] }}</span>
                                                        <span class="tbadge">{{ count($category['accounts']) }}</span>
                                                    </button>
                                                </div>

                                                @if ($isCatExpanded)
                                                    <div class="coax-leaf-scroll">
                                                        @foreach ($category['accounts'] as $account)
                                                            <button type="button" class="coa-tree-leaf coax-leaf {{ $selectedAccountId == $account['id'] ? 'on' : '' }}"
                                                                wire:click.stop="filterByAccount({{ $account['id'] }})" data-label="{{ strtolower($account['name'].' '.($account['alias_name'] ?? '')) }}"
                                                                title="{{ $account['name'] }}" aria-label="Filter by account {{ $account['name'] }}">
                                                                <span class="ln">{{ $account['name'] }}</span>
                                                                @if ($account['alias_name'])
                                                                    <span class="la">{{ $account['alias_name'] }}</span>
                                                                @endif
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach

                                        @if (!empty($typeData['uncategorized']))
                                            @php $isUncatExpanded = $this->isCategoryExpanded(0); @endphp
                                            <div class="coa-tree-cat" data-label="uncategorized">
                                                <div class="coax-tcat">
                                                    <button type="button" wire:click.stop="toggleCategory(0)" class="chev-btn" aria-label="Toggle uncategorized">
                                                        <i class="fa fa-chevron-{{ $isUncatExpanded ? 'down' : 'right' }}"></i>
                                                    </button>
                                                    <span class="tnode-btn">
                                                        <i class="fa fa-question-circle-o fld"></i>
                                                        <span class="tlbl fst-italic text-muted">Uncategorized</span>
                                                        <span class="tbadge">{{ count($typeData['uncategorized']) }}</span>
                                                    </span>
                                                </div>

                                                @if ($isUncatExpanded)
                                                    <div class="coax-leaf-scroll">
                                                        @foreach ($typeData['uncategorized'] as $account)
                                                            <button type="button" class="coa-tree-leaf coax-leaf {{ $selectedAccountId == $account['id'] ? 'on' : '' }}"
                                                                wire:click.stop="filterByAccount({{ $account['id'] }})" data-label="{{ strtolower($account['name'] ?? '') }}"
                                                                title="{{ $account['name'] }}" aria-label="Filter by account {{ $account['name'] }}">
                                                                <span class="ln">{{ $account['name'] }}</span>
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
                            <div class="coax-tree-empty">
                                <i class="fa fa-folder-open-o"></i>
                                No accounts yet
                            </div>
                        @endif

                        <div id="coaTreeEmpty" class="coax-tree-empty d-none">
                            <i class="fa fa-search"></i> No matches in tree
                        </div>
                    </div>
                </div>
            </div>

            {{-- Data Table --}}
            <div class="coax-card">
                {{-- Skeleton shimmer while Livewire is busy --}}
                <div wire:loading.delay wire:target="search, limit, account_type, account_category_id, excludeCustomer, excludeVendor, filterByType, filterByCategory, filterByAccount, sortBy, delete, gotoPage, nextPage, previousPage, resetColumnVisibility" class="coax-skeleton-wrap">
                    @for ($s = 0; $s < 5; $s++)
                        <div class="coax-skeleton-row"></div>
                    @endfor
                </div>

                <div wire:loading.remove wire:target="search, limit, account_type, account_category_id, excludeCustomer, excludeVendor, filterByType, filterByCategory, filterByAccount, sortBy, delete, gotoPage, nextPage, previousPage, resetColumnVisibility">
                    <div class="coax-tbl-wrap">
                        <table class="coax-tbl">
                            <thead>
                                <tr>
                                    <th style="width: 34px;">
                                        <input type="checkbox" class="coax-chk" wire:model.live="selectAll" aria-label="Select all">
                                    </th>
                                    @if ($visibleColumns['id'] ?? true)
                                        <th class="d-none d-md-table-cell" style="width: 52px;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['account_type'] ?? true)
                                        <th style="width: 118px;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_type" label="Type" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['account_category'] ?? true)
                                        <th class="d-none d-md-table-cell" style="width: 22%; min-width: 150px;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_category_id" label="Category" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['name'] ?? true)
                                        <th style="min-width: 180px;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Name" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['alias_name'] ?? true)
                                        <th class="d-none d-lg-table-cell" style="width: 120px;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="alias_name" label="Alias" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['description'] ?? true)
                                        <th class="d-none d-xl-table-cell" style="width: 26%; min-width: 180px;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="description" label="Description" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['model'] ?? true)
                                        <th class="d-none d-md-table-cell" style="width: 110px;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="model" label="Model" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['created_at'] ?? false)
                                        <th class="d-none d-lg-table-cell" style="width: 110px;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_at" label="Created" />
                                        </th>
                                    @endif
                                    @if ($visibleColumns['updated_at'] ?? false)
                                        <th class="d-none d-lg-table-cell" style="width: 110px;">
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="updated_at" label="Updated" />
                                        </th>
                                    @endif
                                    <th style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data as $item)
                                    @php
                                        $rowTone = $typeTones[$item->account_type] ?? 'asset';
                                        $aliasHidden = !($visibleColumns['alias_name'] ?? true);
                                    @endphp
                                    <tr class="coax-row {{ in_array($item->id, $selected) ? 'sel' : '' }}">
                                        <td>
                                            <input type="checkbox" class="coax-chk" value="{{ $item->id }}" wire:model.live="selected" aria-label="Select {{ $item->name }}">
                                        </td>
                                        @if ($visibleColumns['id'] ?? true)
                                            <td class="coax-id d-none d-md-table-cell">#{{ $item->id }}</td>
                                        @endif
                                        @if ($visibleColumns['account_type'] ?? true)
                                            <td>
                                                @if ($item->account_type)
                                                    <span class="tag tag-{{ $rowTone }}">
                                                        <i class="fa {{ $typeIcons[$item->account_type] ?? 'fa-circle-o' }}"></i>
                                                        {{ ucfirst($item->account_type) }}
                                                    </span>
                                                @else
                                                    <span class="asub">—</span>
                                                @endif
                                            </td>
                                        @endif
                                        @if ($visibleColumns['account_category'] ?? true)
                                            <td class="d-none d-md-table-cell">
                                                @if ($item->accountCategory?->name)
                                                    <span class="cat-cell"><i class="fa fa-folder-o"></i> {{ $item->accountCategory->name }}</span>
                                                @else
                                                    <span class="asub fst-italic">Uncategorized</span>
                                                @endif
                                            </td>
                                        @endif
                                        @if ($visibleColumns['name'] ?? true)
                                            <td>
                                                <a href="{{ route('account::view', $item->id) }}" class="aname">{{ $item->name }}</a>
                                                @if ($aliasHidden && $item->alias_name)
                                                    <div class="asub">{{ $item->alias_name }}</div>
                                                @endif
                                            </td>
                                        @endif
                                        @if ($visibleColumns['alias_name'] ?? true)
                                            <td class="asub d-none d-lg-table-cell">{{ $item->alias_name ?: '—' }}</td>
                                        @endif
                                        @if ($visibleColumns['description'] ?? true)
                                            <td class="desc-cell d-none d-xl-table-cell" title="{{ $item->description }}">{{ $item->description ?: '—' }}</td>
                                        @endif
                                        @if ($visibleColumns['model'] ?? true)
                                            <td class="d-none d-md-table-cell">
                                                @if ($item->model)
                                                    <span class="tag tag-model">
                                                        <i class="fa {{ $item->model === 'Customer' ? 'fa-user-o' : 'fa-truck' }}"></i>{{ $item->model }}
                                                    </span>
                                                @else
                                                    <span class="asub">—</span>
                                                @endif
                                            </td>
                                        @endif
                                        @if ($visibleColumns['created_at'] ?? false)
                                            <td class="asub d-none d-lg-table-cell" title="{{ $item->created_at?->format('Y-m-d H:i:s') }}">
                                                {{ $item->created_at ? $item->created_at->diffForHumans() : '—' }}
                                            </td>
                                        @endif
                                        @if ($visibleColumns['updated_at'] ?? false)
                                            <td class="asub d-none d-lg-table-cell" title="{{ $item->updated_at?->format('Y-m-d H:i:s') }}">
                                                {{ $item->updated_at ? $item->updated_at->diffForHumans() : '—' }}
                                            </td>
                                        @endif
                                        <td class="text-end">
                                            <div class="row-act">
                                                @can('account.edit')
                                                    <button class="icon-btn edit" table_id="{{ $item->id }}" title="Edit {{ $item->name }}" aria-label="Edit {{ $item->name }}">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="20" style="border: 0;">
                                            <div class="coax-empty">
                                                <i class="fa {{ $hasActiveFilter ? 'fa-search' : 'fa-folder-open-o' }}"></i>
                                                <h6>{{ $hasActiveFilter ? 'No accounts match your filters' : 'No accounts yet' }}</h6>
                                                <p>{{ $hasActiveFilter ? 'Try adjusting or clearing your search and filters.' : 'Get started by adding your first account head or bulk-importing from a spreadsheet.' }}</p>
                                                <div class="coax-empty-actions">
                                                    @if ($hasActiveFilter)
                                                        <button class="coax-btn" wire:click="$set('search', ''), $set('account_type', ''), $set('account_category_id', ''), $set('selectedAccountId', null)">
                                                            <i class="fa fa-times"></i> Clear filters
                                                        </button>
                                                    @else
                                                        @can('account.create')
                                                            <button class="coax-btn coax-btn-primary" onclick="document.getElementById('AccountAdd')?.click()">
                                                                <i class="fa fa-plus"></i> Add your first account
                                                            </button>
                                                            <a href="{{ route('account::import') }}" class="coax-btn">
                                                                <i class="fa fa-cloud-upload"></i> Import from file
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
                        <div class="coax-foot">
                            <span class="fm">
                                Showing <b>{{ $data->firstItem() }}</b>–<b>{{ $data->lastItem() }}</b> of <b>{{ number_format($data->total()) }}</b>
                            </span>
                            <div>{{ $data->links() }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes coax-loading {
            0% { width: 0; margin-left: 0; }
            50% { width: 60%; margin-left: 20%; }
            100% { width: 0; margin-left: 100%; }
        }
        @keyframes coax-shimmer {
            0% { background-position: -400px 0; }
            100% { background-position: 400px 0; }
        }

        /* ═══ Design tokens — accent tracks the settings theme ═══ */
        .coax {
            --acc: var(--bs-primary);
            --acc-rgb: var(--bs-primary-rgb);
            --acc-d: color-mix(in srgb, var(--bs-primary), #000 14%);
            --acc-deep: color-mix(in srgb, var(--bs-primary), #000 42%);
            --acc-tint: color-mix(in srgb, var(--bs-primary), transparent 90%);
            --acc-tint-2: color-mix(in srgb, var(--bs-primary), transparent 95%);
            --surface: #ffffff; --surface-2: #f5f7fa;
            --ink: var(--bs-emphasis-color);
            --ink-2: var(--bs-body-color);
            --muted: var(--bs-secondary-color);
            --faint: var(--bs-tertiary-color);
            --line: #e7ebf1; --line-soft: #eff2f6;
            --ok: var(--bs-success);   --ok-rgb: var(--bs-success-rgb);
            --info: var(--bs-info);    --info-rgb: var(--bs-info-rgb);
            --warn: var(--bs-warning); --warn-rgb: var(--bs-warning-rgb);
            --bad: var(--bs-danger);   --bad-rgb: var(--bs-danger-rgb);
            --shadow: 0 1px 2px rgba(16,24,40,.05), 0 8px 24px -10px rgba(16,24,40,.12);
            --shadow-lg: 0 18px 42px -18px rgba(var(--acc-rgb),.40), 0 8px 18px -12px rgba(16,24,40,.20);
            color: var(--ink);
            font-size: 12.5px; line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        .coax *, .coax *::before, .coax *::after { box-sizing: border-box; }

        [data-bs-theme="dark"] .coax {
            --surface: #272d34; --surface-2: #2e353d;
            --line: #3a424c; --line-soft: #343c45;
            --acc-tint: color-mix(in srgb, var(--bs-primary), transparent 84%);
            --acc-tint-2: color-mix(in srgb, var(--bs-primary), transparent 90%);
            --shadow: 0 1px 2px rgba(0,0,0,.4), 0 10px 28px -10px rgba(0,0,0,.5);
            --shadow-lg: 0 18px 44px -18px rgba(0,0,0,.6), 0 8px 18px -12px rgba(0,0,0,.5);
        }

        .coax .coax-card { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; box-shadow: var(--shadow); overflow: hidden; }

        /* ═══ HERO ═══ */
        .coax-hero { position: relative; border-radius: 16px; overflow: hidden; margin-bottom: 12px; box-shadow: var(--shadow-lg);
            background:
                radial-gradient(120% 165% at 100% 0, color-mix(in srgb, var(--acc) 28%, transparent), transparent 55%),
                linear-gradient(125deg, var(--acc-deep), var(--acc-d)); }
        .coax-hero .glow { position: absolute; right: -60px; top: -90px; width: 300px; height: 300px; border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,.16), transparent 65%); pointer-events: none; }
        .coax-hero-inner { position: relative; display: flex; align-items: center; gap: 15px; padding: 16px 18px; flex-wrap: wrap; }
        .coax-hero .doc-ic { width: 46px; height: 46px; border-radius: 13px; flex: 0 0 auto; background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.22); display: flex; align-items: center; justify-content: center; font-size: 20px; color: #fff;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.25); }
        .coax-hero .h-main { flex: 1; min-width: 200px; color: #fff; }
        .coax-hero .h-eyebrow { font-size: 9.5px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,.72); }
        .coax-hero .h-ref { font-size: 19px; font-weight: 800; letter-spacing: .3px; line-height: 1.15; margin-top: 2px; }
        .coax-hero .h-sub { font-size: 11.5px; color: rgba(255,255,255,.82); margin-top: 4px; }
        .coax-hero .h-right { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .coax-hero .btn-hero { background: #fff; color: var(--acc-deep); border: 0; padding: 7px 14px; border-radius: 9px; font-size: 12px; font-weight: 700;
            cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(0,0,0,.18); transition: transform .12s; text-decoration: none; }
        .coax-hero .btn-hero:hover { transform: translateY(-1px); color: var(--acc-deep); }
        .coax-hero .btn-ghost { background: rgba(255,255,255,.14); color: #fff; border: 1px solid rgba(255,255,255,.28); padding: 7px 12px; border-radius: 9px;
            font-size: 12px; font-weight: 650; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; transition: background .15s; }
        .coax-hero .btn-ghost:hover { background: rgba(255,255,255,.24); color: #fff; }
        .coax-hero .btn-hero-danger { background: rgba(220,38,38,.85); color: #fff; border: 1px solid rgba(255,255,255,.25); padding: 7px 13px; border-radius: 9px;
            font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .coax-hero .btn-hero-danger:hover { background: rgba(220,38,38,1); }

        .coax-hstats { position: relative; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1px;
            background: rgba(255,255,255,.14); border-top: 1px solid rgba(255,255,255,.14); }
        .coax-hs { background: linear-gradient(180deg, rgba(255,255,255,.05), transparent); padding: 11px 16px; cursor: pointer; border: 0; text-align: left; transition: background .15s; position: relative; }
        .coax-hs:hover { background: rgba(255,255,255,.12); }
        .coax-hs.on { background: rgba(255,255,255,.18); }
        .coax-hs.on::after { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: #fff; }
        .coax-hs:focus-visible { outline: 2px solid #fff; outline-offset: -2px; }
        .coax-hs .s-k { font-size: 9px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: rgba(255,255,255,.72); display: flex; align-items: center; gap: 6px; }
        .coax-hs .s-k .ci { width: 18px; height: 18px; border-radius: 5px; background: rgba(255,255,255,.18); display: inline-flex; align-items: center; justify-content: center; font-size: 9px; }
        .coax-hs .s-v { font-size: 19px; font-weight: 800; color: #fff; margin-top: 5px; letter-spacing: .2px; }

        /* ═══ TOOLBAR ═══ */
        .coax-toolbar { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; box-shadow: var(--shadow); padding: 11px 13px; margin-bottom: 12px; }
        .coax-tb-row { display: flex; flex-wrap: wrap; align-items: center; gap: 9px; }
        .coax-tb-row + .coax-tb-row { margin-top: 9px; }
        .coax-search { position: relative; flex: 1 1 220px; min-width: 180px; }
        .coax-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--faint); font-size: 13px; pointer-events: none; z-index: 2; }
        .coax-search input { width: 100%; border: 1px solid var(--line); background: var(--surface-2); border-radius: 9px; padding: 8px 12px 8px 34px; font-size: 12.5px; color: var(--ink); outline: 0; transition: border-color .15s, box-shadow .15s; }
        .coax-search input:focus { border-color: var(--acc); box-shadow: 0 0 0 3px rgba(var(--acc-rgb),.14); background: var(--surface); }
        .coax-search input::placeholder { color: var(--faint); }
        .coax select.coax-select { border: 1px solid var(--line); background-color: var(--surface-2); border-radius: 9px; padding: 8px 30px 8px 12px; font-size: 12px; color: var(--ink-2); font-weight: 600; cursor: pointer; outline: 0;
            appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%2394a3b8' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 11px center; }
        .coax .coax-btn { border: 1px solid var(--line); background: var(--surface); color: var(--ink-2); padding: 8px 13px; border-radius: 9px; font-size: 12px; font-weight: 650; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; transition: all .15s; white-space: nowrap; text-decoration: none; }
        .coax .coax-btn:hover { border-color: var(--acc); color: var(--acc-d); }
        .coax .coax-btn-primary { background: linear-gradient(180deg, var(--acc), var(--acc-d)); border-color: var(--acc-d); color: #fff; box-shadow: 0 4px 12px -4px rgba(var(--acc-rgb),.55); }
        .coax .coax-btn-primary:hover { color: #fff; transform: translateY(-1px); }
        .coax-divider { width: 1px; height: 24px; background: var(--line); }
        .coax-tb-meta { font-size: 11px; color: var(--muted); font-weight: 600; }
        .coax-tb-meta a { color: var(--acc-d); text-decoration: none; }
        .coax-select-wrap { min-width: 150px; flex: 0 1 190px; }
        @media (max-width: 575.98px) { .coax-select-wrap { flex: 1 1 100%; } }

        .coax-chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; border: 1px solid var(--line); background: var(--surface-2); color: var(--muted); font-size: 11.5px; font-weight: 650; cursor: pointer; transition: all .15s; user-select: none; margin: 0; }
        .coax-chip:hover { border-color: var(--acc); color: var(--acc-d); }
        .coax-chip.on { background: var(--acc); border-color: var(--acc); color: #fff; }
        .coax-chip:focus-within { outline: 2px solid var(--acc); outline-offset: 2px; }
        .coax-chip-lbl { font-size: 10px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: var(--faint); align-self: center; margin-right: 2px; }

        /* ═══ MAIN SPLIT ═══ */
        .coax-main { display: grid; grid-template-columns: 266px 1fr; gap: 12px; align-items: start; }
        @media (max-width: 991.98px) {
            .coax-main { grid-template-columns: 1fr; }
        }

        /* ═══ TREE ═══ */
        .coax-tree-head { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-bottom: 1px solid var(--line-soft); background: var(--surface-2); }
        .coax-tree-head .tt { font-size: 10.5px; font-weight: 750; letter-spacing: .7px; text-transform: uppercase; color: var(--muted); display: flex; align-items: center; gap: 7px; }
        .coax-tree-head .tc { font-size: 10.5px; color: var(--faint); font-weight: 600; }
        .coax-tree-search { padding: 10px 12px 4px; }
        .coax-tree-search input { padding-top: 6px; padding-bottom: 6px; }
        .coax-tree-body { padding: 8px; max-height: 62vh; overflow-y: auto; }
        @media (max-width: 991.98px) { .coax-tree-body { max-height: 45vh; } }

        .coax-tnode, .coax-tcat { display: flex; align-items: center; gap: 4px; padding: 4px 6px; border-radius: 9px; border-left: 3px solid transparent; transition: background .12s; }
        .coax-tnode:hover, .coax-tcat:hover { background: var(--acc-tint-2); }
        .coax-tnode.on { background: var(--acc-tint); border-left-color: var(--acc); }
        .coax-tcat.on { background: var(--acc-tint); border-left-color: var(--acc); }
        .coax .chev-btn { border: 0; background: transparent; color: var(--faint); width: 18px; height: 18px; padding: 0; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; }
        .coax .chev-btn i { font-size: 8.5px; }
        .coax .chev-btn:focus-visible { outline: 2px solid var(--acc); outline-offset: 1px; border-radius: 4px; }
        .coax .tnode-btn { border: 0; background: transparent; padding: 3px 4px; cursor: pointer; display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; text-align: left; color: inherit; }
        .coax .tnode-btn:focus-visible { outline: 2px solid var(--acc); outline-offset: 1px; border-radius: 6px; }
        .coax .tnode-btn .fld { color: var(--faint); font-size: 12px; flex: 0 0 auto; }
        .coax .tic { width: 22px; height: 22px; border-radius: 7px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; flex: 0 0 auto; }
        .coax .tic.t-asset { background: rgba(var(--acc-rgb),.13); color: var(--acc-d); }
        .coax .tic.t-liab { background: rgba(var(--warn-rgb),.14); color: var(--warn); }
        .coax .tic.t-income { background: rgba(var(--ok-rgb),.13); color: var(--ok); }
        .coax .tic.t-expense { background: rgba(var(--bad-rgb),.13); color: var(--bad); }
        .coax .tic.t-equity { background: rgba(var(--info-rgb),.13); color: var(--info); }
        .coax .coax-tnode .tlbl { font-size: 12.5px; font-weight: 700; color: var(--ink); flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .coax .coax-tcat .tlbl { font-size: 12px; font-weight: 600; color: var(--ink-2); flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .coax .tbadge { font-size: 9.5px; font-weight: 700; padding: 1px 7px; border-radius: 999px; background: var(--surface-2); color: var(--muted); flex: 0 0 auto; }
        .coax-tchild { padding-left: 16px; }
        .coax-leaf-scroll { max-height: 260px; overflow-y: auto; padding-left: 22px; }
        .coax-leaf { display: block; width: 100%; text-align: left; border: 0; background: transparent; border-left: 2px solid transparent; border-radius: 7px; padding: 4px 8px; cursor: pointer; transition: background .12s, padding-left .15s; margin-bottom: 2px; }
        .coax-leaf:hover { background: var(--acc-tint-2); padding-left: 11px; }
        .coax-leaf:focus-visible { outline: 2px solid var(--acc); outline-offset: 1px; }
        .coax-leaf.on { background: rgba(var(--warn-rgb),.1); border-left-color: var(--warn); }
        .coax-leaf .ln { display: block; font-size: 11.5px; font-weight: 600; color: var(--ink-2); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .coax-leaf .la { display: block; font-size: 10px; color: var(--faint); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .coax-tree-empty { text-align: center; color: var(--faint); padding: 24px 10px; font-size: 11.5px; font-style: italic; }
        .coax-tree-empty i { display: block; font-size: 26px; opacity: .35; margin-bottom: 8px; }

        /* ═══ TABLE ═══ */
        .coax-tbl-wrap { overflow-x: auto; }
        table.coax-tbl { width: 100%; border-collapse: collapse; font-size: 12.5px; }
        table.coax-tbl thead th { background: var(--surface-2); color: var(--muted); font-size: 10px; font-weight: 750; letter-spacing: .6px; text-transform: uppercase; padding: 11px 12px; text-align: left; border-bottom: 1px solid var(--line); white-space: nowrap; }
        table.coax-tbl thead th a { color: var(--muted) !important; text-decoration: none; }
        table.coax-tbl thead th a:hover { color: var(--acc-d) !important; }
        table.coax-tbl tbody td { padding: 10px 12px; border-bottom: 1px solid var(--line-soft); vertical-align: middle; }
        table.coax-tbl tbody tr.coax-row { transition: background .12s; }
        table.coax-tbl tbody tr.coax-row:hover td { background: var(--acc-tint-2); }
        table.coax-tbl tbody tr.coax-row.sel td { background: var(--acc-tint); }
        .coax-chk { width: 15px; height: 15px; accent-color: var(--acc); cursor: pointer; vertical-align: middle; }
        .coax-id { color: var(--faint); font-weight: 600; font-size: 11.5px; }
        .coax .aname { font-weight: 700; color: var(--ink); text-decoration: none; transition: color .12s; }
        .coax .aname:hover { color: var(--acc-d); }
        .coax .asub { color: var(--muted); font-size: 11px; }
        .coax .tag { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 999px; font-size: 10.5px; font-weight: 700; letter-spacing: .2px; white-space: nowrap; }
        .coax .tag i { font-size: 9.5px; }
        .coax .tag-asset { background: rgba(var(--acc-rgb),.12); color: var(--acc-d); }
        .coax .tag-liab { background: rgba(var(--warn-rgb),.14); color: var(--warn); }
        .coax .tag-income { background: rgba(var(--ok-rgb),.13); color: var(--ok); }
        .coax .tag-expense { background: rgba(var(--bad-rgb),.13); color: var(--bad); }
        .coax .tag-equity { background: rgba(var(--info-rgb),.13); color: var(--info); }
        .coax .tag-model { background: var(--surface-2); color: var(--ink-2); border: 1px solid var(--line); }
        .coax .cat-cell { color: var(--ink-2); font-weight: 600; display: inline-flex; align-items: center; gap: 6px; font-size: 12px; }
        .coax .cat-cell i { color: var(--faint); }
        .coax .desc-cell { color: var(--muted); max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .coax .row-act { opacity: 1; display: inline-flex; gap: 5px; justify-content: flex-end; transition: opacity .15s; }
        @media (min-width: 992px) {
            .coax .row-act { opacity: 0; }
            .coax tr.coax-row:hover .row-act, .coax tr.coax-row:focus-within .row-act { opacity: 1; }
        }
        .coax .icon-btn { width: 30px; height: 30px; border-radius: 8px; border: 1px solid var(--line); background: var(--surface); color: var(--muted); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; transition: all .15s; }
        .coax .icon-btn:hover { border-color: var(--acc); color: var(--acc-d); background: var(--acc-tint); }

        /* Empty state */
        .coax-empty { text-align: center; padding: 48px 20px; }
        .coax-empty > i { font-size: 44px; color: var(--faint); opacity: .4; display: block; margin-bottom: 14px; }
        .coax-empty h6 { font-size: 14px; font-weight: 750; color: var(--ink-2); margin: 0 0 6px; }
        .coax-empty p { font-size: 12px; color: var(--muted); margin: 0 0 16px; }
        .coax-empty-actions { display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; }

        /* Footer / pagination */
        .coax-foot { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding: 12px 14px; border-top: 1px solid var(--line-soft); background: var(--surface-2); }
        .coax-foot .fm { font-size: 11.5px; color: var(--muted); }
        .coax-foot .fm b { color: var(--ink); }

        /* Skeleton shimmer */
        .coax-skeleton-wrap { padding: 14px; }
        .coax-skeleton-row { height: 42px; margin-bottom: 8px; border-radius: 8px;
            background: linear-gradient(90deg, rgba(0,0,0,.04) 0%, rgba(0,0,0,.08) 50%, rgba(0,0,0,.04) 100%);
            background-size: 400px 100%; animation: coax-shimmer 1.2s ease-in-out infinite; }
        [data-bs-theme="dark"] .coax-skeleton-row { background: linear-gradient(90deg, rgba(255,255,255,.04) 0%, rgba(255,255,255,.08) 50%, rgba(255,255,255,.04) 100%); background-size: 400px 100%; }

        /* TomSelect harmonising inside toolbar */
        .coax .coax-select-wrap .ts-control { border: 1px solid var(--line); background: var(--surface-2); border-radius: 9px; font-size: 12px; min-height: 34px; padding: 5px 10px; }
        .coax .coax-select-wrap .ts-wrapper.focus .ts-control { border-color: var(--acc); box-shadow: 0 0 0 3px rgba(var(--acc-rgb),.14); }
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
