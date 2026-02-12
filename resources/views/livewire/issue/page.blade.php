@push('styles')
    <style>
        .issue-page {
            --issue-accent: var(--bs-primary, #0d6efd);
            --issue-accent-dark: color-mix(in srgb, var(--issue-accent) 82%, #000 18%);
            --issue-accent-soft: color-mix(in srgb, var(--issue-accent) 10%, #fff 90%);
            --issue-secondary: var(--bs-info, #0dcaf0);
            --issue-text: var(--bs-emphasis-color, #0f172a);
            --issue-muted: var(--bs-secondary-color, #64748b);
            --issue-stroke: color-mix(in srgb, var(--issue-accent) 16%, #cfd8dc 84%);
            --issue-surface: var(--bs-body-bg, #ffffff);
            --issue-surface-soft: color-mix(in srgb, var(--issue-accent) 4%, #ffffff 96%);
            --issue-table-soft: color-mix(in srgb, var(--issue-accent) 8%, #ffffff 92%);
            --issue-table-soft-2: color-mix(in srgb, var(--issue-accent) 14%, #ffffff 86%);
            --issue-focus-ring: color-mix(in srgb, var(--issue-accent) 22%, transparent);
            --issue-danger: var(--bs-danger, #dc2626);
            --issue-danger-soft: color-mix(in srgb, var(--issue-danger) 10%, #ffffff 90%);
            --issue-border-strong: color-mix(in srgb, var(--issue-stroke) 70%, var(--issue-text) 30%);
            --issue-label: color-mix(in srgb, var(--issue-text) 78%, #ffffff 22%);
            --issue-placeholder: color-mix(in srgb, var(--issue-muted) 70%, #ffffff 30%);
            --issue-danger-border: color-mix(in srgb, var(--issue-danger) 28%, var(--issue-surface) 72%);
            --issue-danger-hover-bg: color-mix(in srgb, var(--issue-danger) 10%, var(--issue-surface) 90%);
            --issue-danger-hover-text: color-mix(in srgb, var(--issue-danger) 86%, #000 14%);
            --issue-hint-bg: color-mix(in srgb, var(--issue-accent) 8%, var(--issue-surface) 92%);
            --issue-hint-border: color-mix(in srgb, var(--issue-accent) 18%, var(--issue-surface) 82%);
            --issue-empty-bg: color-mix(in srgb, var(--issue-accent) 4%, var(--issue-surface) 96%);
            --issue-shadow: 0 14px 40px rgba(15, 23, 42, 0.08);
            --issue-shadow-soft: 0 8px 24px rgba(15, 23, 42, 0.06);
            background:
                radial-gradient(circle at 5% 0%, color-mix(in srgb, var(--issue-accent) 12%, transparent) 0%, transparent 32%),
                radial-gradient(circle at 95% 15%, color-mix(in srgb, var(--issue-secondary) 11%, transparent) 0%, transparent 28%),
                linear-gradient(180deg, color-mix(in srgb, var(--issue-accent) 5%, #fff 95%) 0%, color-mix(in srgb, var(--issue-secondary) 4%, #fff 96%) 100%);
            border-radius: 20px;
            padding: 1rem;
        }

        .issue-page .issue-shell {
            background: color-mix(in srgb, var(--issue-surface) 74%, transparent);
            border: 1px solid color-mix(in srgb, var(--issue-stroke) 90%, transparent);
            border-radius: 18px;
            box-shadow: var(--issue-shadow);
            backdrop-filter: blur(4px);
        }

        .issue-page .issue-header {
            padding: 1rem 1.2rem;
            border-bottom: 1px solid color-mix(in srgb, var(--issue-accent) 20%, transparent);
            background: linear-gradient(130deg, color-mix(in srgb, var(--issue-accent) 12%, transparent) 0%, color-mix(in srgb, var(--issue-secondary) 10%, transparent) 100%);
            border-radius: 18px 18px 0 0;
        }

        .issue-page .issue-title {
            margin: 0;
            color: var(--issue-text);
            font-weight: 700;
            font-size: 1.2rem;
            letter-spacing: 0.01em;
        }

        .issue-page .issue-subtitle {
            margin: 0.2rem 0 0;
            color: var(--issue-muted);
            font-size: 0.85rem;
        }

        .issue-page .mode-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--issue-accent) 28%, transparent);
            background: color-mix(in srgb, var(--issue-surface) 86%, transparent);
            color: var(--issue-accent);
            padding: 0.32rem 0.72rem;
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .issue-page .issue-body {
            padding: 1.1rem;
        }

        .issue-page .content-card {
            background: var(--issue-surface);
            border: 1px solid var(--issue-stroke);
            border-radius: 14px;
            box-shadow: var(--issue-shadow-soft);
            padding: 1rem;
        }

        .issue-page .section-label {
            font-size: 0.72rem;
            letter-spacing: 0.11em;
            color: var(--issue-muted);
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 0.8rem;
            padding-bottom: 0.55rem;
            border-bottom: 1px dashed color-mix(in srgb, var(--issue-accent) 32%, transparent);
        }

        .issue-page .form-label {
            font-weight: 600;
            color: var(--issue-label);
            font-size: 0.8rem;
            letter-spacing: 0.01em;
            margin-bottom: 0.32rem;
        }

        .issue-page .form-control,
        .issue-page .form-control:focus {
            border-radius: 10px;
            border: 1px solid var(--issue-border-strong);
            min-height: 40px;
            background: var(--issue-surface-soft);
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .issue-page .form-control:focus {
            border-color: color-mix(in srgb, var(--issue-accent) 48%, transparent);
            box-shadow: 0 0 0 3px var(--issue-focus-ring);
            background: var(--issue-surface);
        }

        .issue-page .form-control::placeholder {
            color: var(--issue-placeholder);
        }

        .issue-page .ts-control,
        .issue-page .ts-wrapper.single .ts-control {
            border: 1px solid var(--issue-border-strong) !important;
            border-radius: 10px !important;
            min-height: 40px;
            background: var(--issue-surface-soft) !important;
            box-shadow: none !important;
        }

        .issue-page .ts-wrapper.focus .ts-control {
            border-color: color-mix(in srgb, var(--issue-accent) 48%, transparent) !important;
            box-shadow: 0 0 0 3px var(--issue-focus-ring) !important;
            background: var(--issue-surface) !important;
        }

        .issue-page .scan-card,
        .issue-page .entry-card {
            background: linear-gradient(180deg, var(--issue-surface) 0%, var(--issue-surface-soft) 100%);
            border: 1px solid var(--issue-stroke);
            border-radius: 12px;
            padding: 0.85rem;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
        }

        .issue-page .items-head .panel-title {
            font-weight: 700;
            color: var(--issue-text);
            font-size: 1.02rem;
            margin: 0;
        }

        .issue-page .items-head .panel-subtitle {
            font-size: 0.82rem;
            color: var(--issue-muted);
            margin: 0.2rem 0 0;
        }

        .issue-page .table-issue {
            border-collapse: separate;
            border-spacing: 0;
            min-width: 650px;
        }

        .issue-page .table-issue thead th {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.09em;
            color: var(--issue-muted);
            text-transform: uppercase;
            background: linear-gradient(180deg, var(--issue-table-soft) 0%, var(--issue-table-soft-2) 100%);
            border: none;
            padding: 0.72rem 0.8rem;
        }

        .issue-page .table-issue thead th:first-child {
            border-radius: 6px 0 0 0;
        }

        .issue-page .table-issue thead th:last-child {
            border-radius: 0 6px 0 0;
        }

        .issue-page .table-issue tbody tr {
            transition: background .12s ease, transform .12s ease;
        }

        .issue-page .table-issue tbody tr:hover {
            background: color-mix(in srgb, var(--issue-accent) 8%, var(--issue-surface) 92%);
            transform: translateY(-1px);
        }

        .issue-page .table-issue tbody td {
            padding: 0.6rem 0.8rem;
            vertical-align: middle;
            border-color: color-mix(in srgb, var(--issue-stroke) 72%, var(--issue-surface) 28%);
            font-size: 0.875rem;
        }

        .issue-page .table-issue tbody td .form-control {
            border-radius: 8px;
            width: 100%;
            padding: 0.3rem 0.55rem;
            font-size: 0.8125rem;
            min-height: 34px;
        }

        .issue-page .table-issue tfoot th {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--issue-label);
            background: linear-gradient(180deg, var(--issue-table-soft) 0%, var(--issue-table-soft-2) 100%);
            padding: 0.6rem 0.8rem;
            border: none;
            border-top: 1px solid var(--issue-stroke);
        }

        .issue-page .table-issue tfoot th:first-child {
            border-radius: 0 0 0 6px;
        }

        .issue-page .table-issue tfoot th:last-child {
            border-radius: 0 0 6px 0;
        }

        .issue-page .btn-add {
            border-radius: 10px;
            font-weight: 600;
            padding: 0.56rem 0.9rem;
            font-size: 0.875rem;
            background: linear-gradient(135deg, var(--issue-accent) 0%, var(--issue-accent-dark) 100%);
            border: none;
            box-shadow: 0 10px 20px color-mix(in srgb, var(--issue-accent) 32%, transparent);
            transition: transform .12s ease, box-shadow .12s ease, opacity .12s ease;
        }

        .issue-page .btn-add:hover {
            box-shadow: 0 12px 24px color-mix(in srgb, var(--issue-accent-dark) 36%, transparent);
            transform: translateY(-1px);
        }

        .issue-page .btn-remove {
            border-radius: 9px;
            padding: 0.34rem 0.55rem;
            color: var(--issue-danger);
            border-color: var(--issue-danger-border);
            font-size: 0.8rem;
        }

        .issue-page .btn-remove:hover {
            background: var(--issue-danger-hover-bg);
            color: var(--issue-danger-hover-text);
        }

        .issue-page .table-shell {
            border: 1px solid var(--issue-stroke);
            border-radius: 12px;
            background: var(--issue-surface);
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        }

        .issue-page .hint-bar {
            font-size: 0.78rem;
            color: var(--issue-muted);
            background: var(--issue-hint-bg);
            border: 1px solid var(--issue-hint-border);
            border-radius: 10px;
            padding: 0.58rem 0.72rem;
            margin-bottom: 0.75rem;
        }

        .issue-page .action-bar {
            padding-top: 1rem;
            margin-top: 1rem;
            border-top: 1px solid color-mix(in srgb, var(--issue-accent) 20%, transparent);
        }

        .issue-page .btn-cancel {
            border-radius: 10px;
            font-weight: 600;
            padding: 0.48rem 1rem;
            font-size: 0.875rem;
            border-color: var(--issue-stroke);
            color: var(--issue-label);
        }

        .issue-page .btn-save {
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            background: linear-gradient(135deg, var(--issue-accent) 0%, var(--issue-accent-dark) 100%);
            border: none;
            padding: 0.48rem 1.2rem;
            box-shadow: 0 10px 22px color-mix(in srgb, var(--issue-accent) 30%, transparent);
            transition: transform .12s ease, box-shadow .12s ease, opacity .12s ease;
        }

        .issue-page .btn-save:hover {
            box-shadow: 0 14px 26px color-mix(in srgb, var(--issue-accent-dark) 36%, transparent);
            transform: translateY(-1px);
        }

        .issue-page .alert-danger {
            border-left: 4px solid var(--issue-danger) !important;
            background: linear-gradient(180deg, var(--issue-danger-soft) 0%, var(--issue-surface) 100%);
        }

        .issue-page .empty-state {
            text-align: center;
            color: var(--issue-muted);
            font-size: 0.84rem;
            padding: 1.15rem 0.75rem;
            background: var(--issue-empty-bg);
        }

        @media (max-width: 768px) {
            .issue-page {
                padding: 0.5rem;
                border-radius: 14px;
            }

            .issue-page .issue-shell {
                border-radius: 12px;
            }

            .issue-page .issue-header {
                border-radius: 10px;
                padding: 0.85rem 0.9rem;
            }

            .issue-page .issue-body {
                padding: 0.9rem;
            }

            .issue-page .content-card,
            .issue-page .scan-card,
            .issue-page .entry-card,
            .issue-page .table-shell {
                border-radius: 10px;
            }
        }
    </style>
@endpush

<div class="issue-page">
    <div class="col-12">
        <div class="issue-shell h-100">
            <div class="issue-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h4 class="issue-title">{{ $this->isReturnMode() ? 'Return Entry' : 'Issue Entry' }}</h4>
                    <p class="issue-subtitle">Prepare transaction details and item lines before saving.</p>
                </div>
                <span class="mode-chip">
                    <i class="demo-psi-tag"></i>
                    {{ $this->isReturnMode() ? 'Return Mode' : 'Issue Mode' }}
                </span>
            </div>

            <div class="issue-body">
                <form wire:submit="save">
                    <div class="content-card mb-3">
                        <div class="section-label">Customer &amp; details</div>
                        <div class="row g-3">
                            <div class="col-md-4" wire:ignore>
                                <label for="account_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                {{ html()->select('account_id', $accounts)->value($issues['account_id'] ?? '')->class('select-customer_id')->id('issue_account_id')->placeholder('Select Customer')->attribute('style', 'width:100%') }}
                            </div>
                            <div class="col-md-3">
                                <label for="issue_date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" wire:model="issues.date" class="form-control" id="issue_date">
                            </div>
                            <div class="col-md-5">
                                <label for="remarks" class="form-label">Remarks</label>
                                <input type="text" wire:model="issues.remarks" class="form-control" id="remarks" placeholder="Optional notes">
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="items-head mb-3">
                            <h5 class="panel-title">{{ $this->isReturnMode() ? 'Return Items' : 'Issue Items' }}</h5>
                            <p class="panel-subtitle">{{ $this->isReturnMode() ? 'Use quantity in to register returned stock.' : 'Use quantity out to register issued stock.' }}</p>
                        </div>

                        <div class="scan-card mb-3">
                            <div class="row align-items-end g-3">
                                <div class="col-md-9">
                                    <label for="barcode_input" class="form-label">Scan barcode</label>
                                    <input type="text" wire:model="barcode_input" wire:keydown.enter.prevent="addToCartByBarcode" class="form-control form-control-sm" id="barcode_input"
                                        placeholder="Scan or type barcode, press Enter to add (qty 1)" autocomplete="off">
                                </div>
                                <div class="col-md-3">
                                    <button type="button" wire:click="addToCartByBarcode" class="btn btn-outline-primary w-100 btn-cancel">
                                        <i class="demo-psi-barcode me-1"></i> Add by Barcode
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="entry-card mb-3">
                            <div class="row align-items-end g-3">
                                <div class="col-md-7" wire:ignore>
                                    <label for="product_id" class="form-label">Product</label>
                                    {{ html()->select('product_id', [])->value('')->class('select-product_id-list')->id('issue_product_id')->attribute('style', 'width:100%')->placeholder('Select Product') }}
                                </div>
                                @if ($this->isReturnMode())
                                    <div class="col-md-2">
                                        <label for="add_quantity_in" class="form-label">Qty In</label>
                                        <input type="number" wire:model="add_quantity_in" step="any" min="0" wire:keydown.enter.prevent="addToCart" class="form-control form-control-sm"
                                            id="add_quantity_in" placeholder="0">
                                    </div>
                                @else
                                    <div class="col-md-2">
                                        <label for="add_quantity_out" class="form-label">Qty Out</label>
                                        <input type="number" wire:model="add_quantity_out" step="any" min="0" wire:keydown.enter.prevent="addToCart" class="form-control form-control-sm"
                                            id="add_quantity_out" placeholder="0">
                                    </div>
                                @endif
                                <div class="col-md-2">
                                    <button type="button" wire:click="addToCart" class="btn btn-primary btn-add w-100">
                                        <i class="demo-psi-add me-1"></i> Add
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="hint-bar">
                            Each row saves as <strong>{{ $this->isReturnMode() ? 'quantity in' : 'quantity out' }}</strong> from this page.
                        </div>

                        <div class="table-shell">
                            <div class="table-responsive">
                                <table class="table table-sm table-issue align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Product</th>
                                            <th class="text-end">{{ $this->isReturnMode() ? 'Qty In' : 'Qty Out' }}</th>
                                            <th class="text-center" style="width: 4rem;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $item)
                                            <tr wire:key="item-{{ $item['key'] }}">
                                                <td class="text-muted fw-medium">{{ $loop->iteration }}</td>
                                                <td class="fw-medium">{{ $item['name'] }}</td>
                                                <td class="text-end">
                                                    @if ($this->isReturnMode())
                                                        <input type="number" step="any" min="0" wire:model.live="items.{{ $item['key'] }}.quantity_in"
                                                            class="form-control form-control-sm text-end">
                                                    @else
                                                        <input type="number" step="any" min="0" wire:model.live="items.{{ $item['key'] }}.quantity_out"
                                                            class="form-control form-control-sm text-end">
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" wire:click="removeItem('{{ $item['key'] }}')" wire:confirm="Remove this item?"
                                                        class="btn btn-sm btn-outline-danger btn-remove" title="Remove">
                                                        <i class="demo-pli-recycling"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                        @if (count($items) === 0)
                                            <tr>
                                                <td colspan="4" class="empty-state">No items added yet. Start by scanning barcode or selecting a product.</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                    @if (count($items) > 0)
                                        <tfoot>
                                            <tr>
                                                <th colspan="2" class="text-end">Total</th>
                                                <th class="text-end">
                                                    {{ number_format(collect($items)->sum(fn($i) => (float) ($this->isReturnMode() ? $i['quantity_in'] ?? 0 : $i['quantity_out'] ?? 0)), 2) }}
                                                </th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>

                    @if ($this->getErrorBag()->count())
                        <div class="alert alert-danger mb-3 rounded-2 border-0 shadow-sm py-2 px-3" role="alert">
                            <ul class="mb-0 ps-3">
                                @foreach ($this->getErrorBag()->toArray() as $messages)
                                    @foreach ($messages as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="d-flex justify-content-end gap-2 action-bar">
                        <a href="{{ route('issue::index') }}" class="btn btn-secondary btn-cancel">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-save">Save {{ $this->isReturnMode() ? 'Return' : 'Issue' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var productEl = document.querySelector('#issue_product_id');
                window.addEventListener('OpenProductBox', function() {
                    if (productEl && productEl.tomselect) {
                        productEl.tomselect.clear();
                        @this.set('product_id', '');
                        productEl.tomselect.open();
                    }
                });
            });
            $('#issue_product_id').on('change', function(e) {
                const value = $(this).val() || null;
                @this.set('product_id', value);
            });
            $('#issue_account_id').on('change', function(e) {
                const value = $(this).val() || null;
                @this.set('issues.account_id', value);
            });
        </script>
    @endpush
</div>
