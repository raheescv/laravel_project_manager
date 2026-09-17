{{--
    Category Wise Sale Report — built from Bootstrap 5.3 / Nifty theme classes only, no custom stylesheet.
    Every top-level block is a .card because the page header band overlaps the content.
    Rows click open to the category's top products.
--}}
<div>
    @php
        $net = (float) $summary['net_total'];
        $share = fn ($value) => $net > 0 ? max(0, min(100, ((float) $value / $net) * 100)) : 0;
        $qty = fn ($value) => preg_replace('/\.?0+$/', '', number_format((float) $value, 3));
        $tones = ['primary', 'info', 'success', 'warning', 'danger'];
        $mixShown = $mix->sum(fn ($row) => max(0, (float) $row->net_total));
        $others = max(0, $net - $mixShown);
        $period = $from_date === $to_date ? systemDate($from_date) : systemDate($from_date).' – '.systemDate($to_date);
        $sortLink = function (string $field, string $label) use ($sortField, $sortDirection) {
            $active = $sortField === $field;
            $icon = $active ? ($sortDirection === 'asc' ? 'fa-sort-asc text-primary' : 'fa-sort-desc text-primary') : 'fa-sort opacity-50';

            return '<a href="#" class="link-body-emphasis text-decoration-none text-nowrap" wire:click.prevent="sortBy(\''.$field.'\')">'.e($label).' <i class="fa '.$icon.' ms-1"></i></a>';
        };
    @endphp

    <!-- TITLE + FILTERS -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary-emphasis fs-4 flex-shrink-0" style="width: 48px; height: 48px"><i class="fa fa-th-large"></i></span>
                    <div class="lh-sm">
                        <h4 class="mb-1">Category Wise Sales</h4>
                        <div class="text-body-secondary small">Completed sales less returns, grouped by each product's main category</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div wire:loading class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading…</span></div>
                    <a href="{{ route('report::sale_item') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-list me-1"></i> Item Wise</a>
                    @can('report.sale category')
                        <button type="button" class="btn btn-sm btn-success" wire:click="export" wire:loading.attr="disabled" wire:target="export">
                            <i class="fa fa-file-excel-o me-1"></i> Export
                        </button>
                    @endcan
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-end gap-3 mt-3 pt-3 border-top">
                <div>
                    <div class="small fw-semibold text-body-secondary mb-1">Period</div>
                    <div class="d-flex flex-wrap gap-1" role="group" aria-label="Quick period">
                        @foreach (\App\Livewire\Report\Sale\CategoryWiseReport::PRESETS as $key => $label)
                            <button type="button" class="btn btn-sm rounded-pill px-3 {{ $preset === $key ? 'btn-primary' : 'btn-outline-secondary' }}" wire:click="setRange('{{ $key }}')" aria-pressed="{{ $preset === $key ? 'true' : 'false' }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="col-12 col-sm-auto">
                    <div class="small fw-semibold text-body-secondary mb-1">
                        Dates @if ($preset === 'custom')<span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis fw-normal ms-1">Custom</span>@endif
                    </div>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                        <input type="date" class="form-control" wire:model.live="from_date" aria-label="From date">
                        <span class="input-group-text">to</span>
                        <input type="date" class="form-control" wire:model.live="to_date" aria-label="To date">
                    </div>
                </div>
                <div class="col-12 col-sm-auto" style="min-width: 200px" wire:ignore>
                    <label class="small fw-semibold text-body-secondary mb-1" for="category_report_branch_id"><i class="fa fa-building me-1"></i>Branch</label>
                    {{ html()->select('category_report_branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list')->id('category_report_branch_id')->placeholder('All Branches') }}
                </div>
                <div class="col-12 col-sm-auto" style="min-width: 200px" wire:ignore>
                    <label class="small fw-semibold text-body-secondary mb-1" for="category_report_employee_id"><i class="fa fa-user me-1"></i>Staff</label>
                    {{ html()->select('category_report_employee_id', [])->value('')->class('select-employee_id-list')->id('category_report_employee_id')->placeholder('All Staff') }}
                </div>
                <div>
                    <div class="small fw-semibold text-body-secondary mb-1">Type</div>
                    <div class="d-flex flex-wrap gap-1" role="group" aria-label="Item type">
                        @foreach (\App\Livewire\Report\Sale\CategoryWiseReport::PRODUCT_TYPES as $key => $label)
                            <button type="button" class="btn btn-sm rounded-pill px-3 {{ $product_type === $key ? 'btn-primary' : 'btn-outline-secondary' }}" wire:click="setProductType('{{ $key }}')" aria-pressed="{{ $product_type === $key ? 'true' : 'false' }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SUMMARY + MIX -->
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <span class="small text-uppercase fw-semibold text-body-secondary">Net sales</span>
                        <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis fw-normal text-truncate"><i class="fa fa-calendar me-1"></i>{{ $period }}</span>
                    </div>
                    <div class="fs-2 fw-bold my-2 text-break">{{ currency($net) }}</div>
                    <div class="small text-body-secondary mb-3">
                        {{ number_format($summary['bills_count']) }} {{ Str::plural('bill', $summary['bills_count']) }} ·
                        {{ $qty($summary['quantity']) }} qty ·
                        {{ number_format($summary['categories']) }} {{ Str::plural('category', $summary['categories']) }}
                    </div>
                    <div class="row g-2 mt-auto">
                        <div class="col-6">
                            <div class="rounded-3 border p-2 h-100">
                                <div class="small text-body-secondary"><i class="fa fa-shopping-cart me-1"></i>Gross</div>
                                <div class="fw-semibold text-nowrap">{{ currency($summary['gross_amount']) }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded-3 border p-2 h-100">
                                <div class="small text-body-secondary"><i class="fa fa-tag me-1"></i>Discount</div>
                                <div class="fw-semibold text-danger text-nowrap">{{ $summary['discount'] != 0 ? '−'.currency($summary['discount']) : currency(0) }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded-3 border p-2 h-100">
                                <div class="small text-body-secondary"><i class="fa fa-file-text-o me-1"></i>Tax</div>
                                <div class="fw-semibold text-info-emphasis text-nowrap">{{ currency($summary['tax_amount']) }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded-3 border p-2 h-100">
                                <div class="small text-body-secondary"><i class="fa fa-undo me-1"></i>Returns <span class="text-body-tertiary">· {{ $summary['returns_count'] }}</span></div>
                                <div class="fw-semibold text-warning-emphasis text-nowrap">{{ $summary['return_total'] != 0 ? '−'.currency($summary['return_total']) : currency(0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fa fa-pie-chart text-primary"></i>
                    <div class="lh-sm">
                        <h5 class="card-title mb-0">Category mix</h5>
                        <small class="text-body-secondary">Top {{ min(5, $mix->count()) ?: 5 }} categories by share of net sales</small>
                    </div>
                </div>
                <div class="card-body">
                    @if ($net > 0 && $mix->isNotEmpty())
                        <div class="progress-stacked mb-3" style="height: 14px">
                            @foreach ($mix as $index => $row)
                                @if ($share($row->net_total) > 0)
                                    <div class="progress" role="progressbar" aria-label="{{ $row->group_name ?? 'Uncategorised' }}" aria-valuenow="{{ round($share($row->net_total)) }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $share($row->net_total) }}%">
                                        <div class="progress-bar bg-{{ $tones[$index] }}"></div>
                                    </div>
                                @endif
                            @endforeach
                            @if ($others > 0.004)
                                <div class="progress" role="progressbar" aria-label="Other categories" aria-valuenow="{{ round($share($others)) }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $share($others) }}%">
                                    <div class="progress-bar bg-secondary"></div>
                                </div>
                            @endif
                        </div>
                        <div class="row g-2">
                            @foreach ($mix as $index => $row)
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-2 px-2 py-2 rounded-3 border h-100">
                                        <span class="rounded-circle bg-{{ $tones[$index] }} flex-shrink-0" style="width: 10px; height: 10px"></span>
                                        <span class="fw-semibold text-truncate">{{ $row->group_name ?? 'Uncategorised' }}</span>
                                        <span class="ms-auto text-nowrap small">{{ currency($row->net_total) }}</span>
                                        <span class="badge rounded-pill bg-body text-body border fw-semibold" style="min-width: 52px">{{ number_format($share($row->net_total), 1) }}%</span>
                                    </div>
                                </div>
                            @endforeach
                            @if ($others > 0.004)
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-2 px-2 py-2 rounded-3 border h-100">
                                        <span class="rounded-circle bg-secondary flex-shrink-0" style="width: 10px; height: 10px"></span>
                                        <span class="fw-semibold text-truncate">Other categories <span class="fw-normal text-body-secondary">· {{ $summary['categories'] - $mix->count() }}</span></span>
                                        <span class="ms-auto text-nowrap small">{{ currency($others) }}</span>
                                        <span class="badge rounded-pill bg-body text-body border fw-semibold" style="min-width: 52px">{{ number_format($share($others), 1) }}%</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="h-100 d-flex flex-column align-items-center justify-content-center text-center text-body-secondary py-4">
                            <i class="fa fa-pie-chart fa-2x mb-2 opacity-50"></i>
                            <div class="fw-semibold">No net sales to split</div>
                            <small>Pick a period with completed sales to see the category mix.</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- CATEGORY TABLE -->
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <div class="lh-sm me-auto">
                <h5 class="card-title mb-0">Categories</h5>
                <small class="text-body-secondary">
                    @if ($data->total())
                        Showing {{ $data->firstItem() }}–{{ $data->lastItem() }} of {{ $data->total() }} · click a row for its top products
                    @else
                        Nothing to show
                    @endif
                </small>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="input-group input-group-sm" style="width: 220px">
                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                    <input type="search" class="form-control" placeholder="Find a category" wire:model.live.debounce.400ms="search" aria-label="Find a category">
                </div>
                <select class="form-select form-select-sm w-auto" wire:model.live="limit" aria-label="Rows per page">
                    @foreach (\App\Livewire\Report\Sale\CategoryWiseReport::PER_PAGE_OPTIONS as $option)
                        <option value="{{ $option }}">{{ $option }} rows</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" wire:loading.class="opacity-50">
                <table class="table table-hover align-middle mb-0">
                    <thead class="small text-uppercase">
                        <tr>
                            <th class="ps-3">{!! $sortLink('group_name', 'Category') !!}</th>
                            <th class="text-end d-none d-md-table-cell">{!! $sortLink('bills_count', 'Bills') !!}</th>
                            <th class="text-end">{!! $sortLink('quantity', 'Qty') !!}</th>
                            <th class="text-end d-none d-md-table-cell">{!! $sortLink('gross_amount', 'Gross') !!}</th>
                            <th class="text-end d-none d-xl-table-cell">{!! $sortLink('discount', 'Discount') !!}</th>
                            <th class="text-end d-none d-xl-table-cell">{!! $sortLink('tax_amount', 'Tax') !!}</th>
                            <th class="text-end d-none d-lg-table-cell">{!! $sortLink('return_total', 'Returns') !!}</th>
                            <th class="text-end">{!! $sortLink('net_total', 'Net sales') !!}</th>
                            <th class="d-none d-sm-table-cell" style="min-width: 150px">Share</th>
                            <th class="pe-3"><span class="visually-hidden">Expand</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $row)
                            @php
                                $key = $row->group_id === null ? \App\Livewire\Report\Sale\CategoryWiseReport::UNCATEGORISED : (string) $row->group_id;
                                $isOpen = $expanded === $key;
                                $name = $row->group_name ?? 'Uncategorised';
                                $rowShare = $share($row->net_total);
                            @endphp
                            <tr wire:key="category-{{ $key }}" role="button" wire:click="toggle('{{ $key }}')" class="{{ $isOpen ? 'table-active' : '' }}" aria-expanded="{{ $isOpen ? 'true' : 'false' }}">
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-3">
                                        @if ($row->group_meta)
                                            <img src="{{ asset('storage/'.$row->group_meta) }}" alt="" class="rounded-3 border object-fit-cover flex-shrink-0" width="38" height="38" loading="lazy">
                                        @elseif ($row->group_id === null)
                                            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-body-tertiary text-body-secondary border flex-shrink-0" style="width: 38px; height: 38px"><i class="fa fa-question"></i></span>
                                        @else
                                            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary-emphasis fw-bold flex-shrink-0" style="width: 38px; height: 38px">{{ Str::upper(Str::substr($name, 0, 1)) }}</span>
                                        @endif
                                        <div class="lh-sm">
                                            <div class="fw-semibold text-nowrap">{{ $name }}</div>
                                            <small class="text-body-secondary text-nowrap">{{ $row->products_count }} {{ Str::plural('product', $row->products_count) }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end d-none d-md-table-cell">{{ number_format($row->bills_count) }}</td>
                                <td class="text-end text-nowrap">
                                    <div class="fw-medium">{{ $qty($row->quantity) }}</div>
                                    @if ((float) $row->return_qty > 0)
                                        <small class="text-warning-emphasis">−{{ $qty($row->return_qty) }} returned</small>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap d-none d-md-table-cell">{{ currency($row->gross_amount) }}</td>
                                <td class="text-end text-nowrap d-none d-xl-table-cell {{ (float) $row->discount != 0 ? 'text-danger' : 'text-body-tertiary' }}">{{ (float) $row->discount != 0 ? '−'.currency($row->discount) : '—' }}</td>
                                <td class="text-end text-nowrap d-none d-xl-table-cell {{ (float) $row->tax_amount != 0 ? '' : 'text-body-tertiary' }}">{{ (float) $row->tax_amount != 0 ? currency($row->tax_amount) : '—' }}</td>
                                <td class="text-end text-nowrap d-none d-lg-table-cell {{ (float) $row->return_total != 0 ? 'text-warning-emphasis' : 'text-body-tertiary' }}">{{ (float) $row->return_total != 0 ? '−'.currency($row->return_total) : '—' }}</td>
                                <td class="text-end text-nowrap fw-bold">{{ currency($row->net_total) }}</td>
                                <td class="d-none d-sm-table-cell">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" role="progressbar" aria-label="Share of net sales" aria-valuenow="{{ round($rowShare) }}" aria-valuemin="0" aria-valuemax="100" style="height: 6px">
                                            <div class="progress-bar" style="width: {{ $rowShare }}%"></div>
                                        </div>
                                        <small class="fw-semibold text-body-secondary text-end" style="min-width: 44px">{{ number_format($rowShare, 1) }}%</small>
                                    </div>
                                </td>
                                <td class="pe-3 text-end"><i class="fa {{ $isOpen ? 'fa-chevron-down text-primary' : 'fa-chevron-right text-body-secondary' }}"></i></td>
                            </tr>
                            @if ($isOpen)
                                <tr wire:key="category-{{ $key }}-products">
                                    <td colspan="10" class="p-0">
                                        <div class="bg-body-tertiary border-bottom px-3 py-3">
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                <i class="fa fa-cubes text-primary"></i>
                                                <span class="fw-semibold">Top products in {{ $name }}</span>
                                                <small class="text-body-secondary">ranked by net sales · share is of this category</small>
                                            </div>
                                            <div class="table-responsive rounded-3 border bg-body">
                                                <table class="table table-sm align-middle mb-0">
                                                    <thead class="small text-uppercase text-body-secondary">
                                                        <tr>
                                                            <th class="ps-3" style="width: 40px">#</th>
                                                            <th>Product</th>
                                                            <th class="text-end d-none d-md-table-cell">Bills</th>
                                                            <th class="text-end">Qty</th>
                                                            <th class="text-end d-none d-md-table-cell">Returns</th>
                                                            <th class="text-end pe-3 pe-sm-2">Net sales</th>
                                                            <th class="pe-3 d-none d-sm-table-cell" style="min-width: 150px">Share of category</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($products as $index => $product)
                                                            @php $productShare = (float) $row->net_total > 0 ? max(0, min(100, ((float) $product->net_total / (float) $row->net_total) * 100)) : 0; @endphp
                                                            <tr wire:key="category-{{ $key }}-product-{{ $product->group_id }}">
                                                                <td class="ps-3 text-body-secondary">{{ $index + 1 }}</td>
                                                                <td>
                                                                    <div class="fw-medium text-nowrap">{{ $product->group_name }}</div>
                                                                    @if ($product->group_meta)
                                                                        <small class="text-body-secondary">{{ $product->group_meta }}</small>
                                                                    @endif
                                                                </td>
                                                                <td class="text-end d-none d-md-table-cell">{{ number_format($product->bills_count) }}</td>
                                                                <td class="text-end text-nowrap">{{ $qty($product->quantity) }}</td>
                                                                <td class="text-end text-nowrap d-none d-md-table-cell {{ (float) $product->return_total != 0 ? 'text-warning-emphasis' : 'text-body-tertiary' }}">{{ (float) $product->return_total != 0 ? '−'.currency($product->return_total) : '—' }}</td>
                                                                <td class="text-end text-nowrap fw-semibold pe-3 pe-sm-2">{{ currency($product->net_total) }}</td>
                                                                <td class="pe-3 d-none d-sm-table-cell">
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <div class="progress flex-grow-1" role="progressbar" aria-label="Share of category" aria-valuenow="{{ round($productShare) }}" aria-valuemin="0" aria-valuemax="100" style="height: 5px">
                                                                            <div class="progress-bar bg-info" style="width: {{ $productShare }}%"></div>
                                                                        </div>
                                                                        <small class="text-body-secondary text-end" style="min-width: 44px">{{ number_format($productShare, 1) }}%</small>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="7" class="text-center text-body-secondary py-3">No product lines in this category for the period.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                            @if ($row->products_count > $products->count())
                                                <small class="d-block text-body-secondary mt-2">Top {{ $products->count() }} of {{ $row->products_count }} products · see the <a href="{{ route('report::sale_item') }}">Item Wise report</a> for every line.</small>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-body-secondary">
                                    <i class="fa fa-folder-open-o fa-2x d-block mb-2 opacity-50"></i>
                                    <div class="fw-semibold">{{ trim($search) ? 'No category matches “'.trim($search).'”' : 'No category sales for this period' }}</div>
                                    <small>Try a wider date range or clear the filters.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($data->total())
                        <tfoot>
                            <tr class="fw-bold">
                                <td class="ps-3 text-nowrap">Total <small class="fw-normal text-body-secondary">· all {{ $summary['categories'] }} {{ Str::plural('category', $summary['categories']) }}</small></td>
                                <td class="text-end d-none d-md-table-cell">{{ number_format($summary['bills_count']) }}</td>
                                <td class="text-end text-nowrap">{{ $qty($summary['quantity']) }}</td>
                                <td class="text-end text-nowrap d-none d-md-table-cell">{{ currency($summary['gross_amount']) }}</td>
                                <td class="text-end text-nowrap text-danger d-none d-xl-table-cell">{{ $summary['discount'] != 0 ? '−'.currency($summary['discount']) : '—' }}</td>
                                <td class="text-end text-nowrap d-none d-xl-table-cell">{{ currency($summary['tax_amount']) }}</td>
                                <td class="text-end text-nowrap text-warning-emphasis d-none d-lg-table-cell">{{ $summary['return_total'] != 0 ? '−'.currency($summary['return_total']) : '—' }}</td>
                                <td class="text-end text-nowrap text-primary">{{ currency($net) }}</td>
                                <td class="d-none d-sm-table-cell"><small class="text-body-secondary fw-semibold">{{ $net > 0 ? '100%' : '—' }}</small></td>
                                <td class="pe-3"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
        @if ($data->hasPages())
            <div class="card-footer">
                {{ $data->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#category_report_branch_id').on('change', function() {
                    @this.set('branch_id', $(this).val() || null);
                });
                $('#category_report_employee_id').on('change', function() {
                    @this.set('employee_id', $(this).val() || null);
                });
            });
        </script>
    @endpush
</div>
