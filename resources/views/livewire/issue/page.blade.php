@push('styles')
    <style>
        .issue-page {
            --issue-accent: #4f46e5;
            --issue-muted: #64748b;
            --issue-bg: #f8fafc;
        }

        .issue-page .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
        }

        .issue-page .section-label {
            font-size: 0.68rem;
            letter-spacing: 0.1em;
            color: var(--issue-muted);
            font-weight: 600;
            margin-bottom: 0.5rem;
            padding-bottom: 0.35rem;
            border-bottom: 1px solid rgba(0, 0, 0, .06);
        }

        .issue-page .form-label {
            font-weight: 500;
            color: #334155;
            font-size: 0.875rem;
        }

        .issue-page .form-control,
        .issue-page .form-control:focus {
            border-radius: 6px;
            border-color: #e2e8f0;
        }

        .issue-page .form-control:focus {
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.12);
        }

        .issue-page .items-panel {
            background: var(--issue-bg);
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .issue-page .items-panel .panel-title {
            font-weight: 600;
            color: #1e293b;
            font-size: 1rem;
        }

        .issue-page .items-panel .panel-subtitle {
            font-size: 0.8rem;
            color: var(--issue-muted);
            font-weight: 400;
        }

        .issue-page .add-row-box {
            background: #fff;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
        }

        .issue-page .add-row-box .form-control {
            background: #fff;
        }

        .issue-page .table-issue {
            border-collapse: separate;
            border-spacing: 0;
        }

        .issue-page .table-issue thead th {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            color: var(--issue-muted);
            text-transform: uppercase;
            background: #f1f5f9;
            border: none;
            padding: 0.5rem 0.75rem;
        }

        .issue-page .table-issue thead th:first-child {
            border-radius: 6px 0 0 0;
        }

        .issue-page .table-issue thead th:last-child {
            border-radius: 0 6px 0 0;
        }

        .issue-page .table-issue tbody tr {
            transition: background .12s ease;
        }

        .issue-page .table-issue tbody tr:hover {
            background: #f8fafc;
        }

        .issue-page .table-issue tbody td {
            padding: 0.4rem 0.75rem;
            vertical-align: middle;
            border-color: #f1f5f9;
            font-size: 0.875rem;
        }

        .issue-page .table-issue tbody td .form-control {
            border-radius: 5px;
            width: 100%;
            padding: 0.25rem 0.5rem;
            font-size: 0.8125rem;
        }

        .issue-page .table-issue tfoot th {
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            background: #f1f5f9;
            padding: 0.5rem 0.75rem;
            border: none;
            border-top: 1px solid #e2e8f0;
        }

        .issue-page .table-issue tfoot th:first-child {
            border-radius: 0 0 0 6px;
        }

        .issue-page .table-issue tfoot th:last-child {
            border-radius: 0 0 6px 0;
        }

        .issue-page .btn-add {
            border-radius: 6px;
            font-weight: 500;
            padding: 0.35rem 0.75rem;
            font-size: 0.875rem;
            background: var(--issue-accent);
            border: none;
        }

        .issue-page .btn-add:hover {
            background: #4338ca;
        }

        .issue-page .btn-remove {
            border-radius: 5px;
            padding: 0.25rem 0.4rem;
            color: #dc2626;
            border-color: #fecaca;
            font-size: 0.8rem;
        }

        .issue-page .btn-remove:hover {
            background: #fef2f2;
            color: #b91c1c;
        }

        .issue-page .action-bar {
            padding-top: 0.75rem;
            margin-top: 0.75rem;
            border-top: 1px solid #e2e8f0;
        }

        .issue-page .btn-cancel {
            border-radius: 6px;
            font-weight: 500;
            padding: 0.35rem 0.9rem;
            font-size: 0.875rem;
        }

        .issue-page .btn-save {
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
            background: var(--issue-accent);
            border: none;
            padding: 0.35rem 1rem;
        }

        .issue-page .btn-save:hover {
            background: #4338ca;
        }

        .issue-page .hint-text {
            font-size: 0.75rem;
            color: var(--issue-muted);
        }
    </style>
@endpush

<div class="issue-page">
    <div class="col-12">
        <div class="card shadow-sm h-100">
            <div class="card-body p-3">
                <form wire:submit="save">
                    <div class="section-label">Customer &amp; details</div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4" wire:ignore>
                            <label for="account_id" class="form-label">Customer <span class="text-danger">*</span></label>
                            {{ html()->select('account_id', $accounts)->value($issues['account_id'] ?? '')->class('select-customer_id')->id('issue_account_id')->placeholder('Select Customer')->attribute('style', 'width:100%') }}
                        </div>
                        <div class="col-md-2">
                            <label for="issue_date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" wire:model="issues.date" class="form-control" id="issue_date">
                        </div>
                        <div class="col-md-6">
                            <label for="remarks" class="form-label">Remarks</label>
                            <input type="text" wire:model="issues.remarks" class="form-control" id="remarks" placeholder="Optional notes">
                        </div>
                    </div>

                    <div class="items-panel p-3 mb-3">
                        <h5 class="panel-title mb-0">{{ $this->isReturnMode() ? 'Return Items' : 'Issue Items' }}</h5>
                        <p class="panel-subtitle mb-3">{{ $this->isReturnMode() ? 'Return mode uses quantity in only.' : 'Issue mode uses quantity out only.' }}</p>
                        <div class="add-row-box mb-2">
                            <div class="row align-items-end g-2">
                                <div class="col-md-8">
                                    <label for="barcode_input" class="form-label">Scan barcode</label>
                                    <input type="text" wire:model="barcode_input" wire:keydown.enter.prevent="addToCartByBarcode" class="form-control form-control-sm" id="barcode_input"
                                        placeholder="Scan or type barcode, press Enter to add (qty 1)" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="add-row-box mb-3">
                            <div class="row align-items-end g-2">
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
                        <p class="hint-text mb-2">Each row saves as {{ $this->isReturnMode() ? 'quantity in' : 'quantity out' }} from this page.</p>
                        <div class="table-responsive rounded overflow-hidden">
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
                                                <button type="button" wire:click="removeItem('{{ $item['key'] }}')" wire:confirm="Remove this item?" class="btn btn-sm btn-outline-danger btn-remove"
                                                    title="Remove">
                                                    <i class="demo-pli-recycling"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
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
