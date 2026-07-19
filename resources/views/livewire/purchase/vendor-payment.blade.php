<div>
    @php
        $collection = collect($vendor_purchases);
        $selectedCount = $collection->where('selected', true)->count();
        $hasSelection = $selectedCount > 0;
        $totalPayment = array_sum(array_column($vendor_purchases, 'payment'));
        $totalDiscount = array_sum(array_column($vendor_purchases, 'discount'));
    @endphp
    <form wire:submit.prevent="save" class="vpx">
        <div class="modal-content modal-lg vpx-shell">
            {{-- Hero header --}}
            <div class="modal-header vpx-hero">
                <div class="vpx-hero-main">
                    <div class="vpx-hero-icon"><i class="fa fa-money"></i></div>
                    <div>
                        <h3 class="vpx-hero-title">{{ $name ?? 'Vendor' }}</h3>
                        <span class="vpx-hero-sub">Purchase Payment Form</span>
                    </div>
                </div>
                <div class="vpx-hero-stats">
                    <div class="vpx-stat">
                        <span class="vpx-stat-label">Open Balance</span>
                        <span class="vpx-stat-value text-danger">{{ currency($total['balance']) }}</span>
                    </div>
                    <div class="vpx-stat">
                        <span class="vpx-stat-label">Selected</span>
                        <span class="vpx-stat-value">{{ $selectedCount }} <small>bill{{ $selectedCount == 1 ? '' : 's' }}</small></span>
                    </div>
                    <div class="vpx-stat vpx-stat-accent">
                        <span class="vpx-stat-label">Paying Now</span>
                        <span class="vpx-stat-value">{{ currency($totalPayment) }}</span>
                    </div>
                </div>
            </div>

            @if ($this->getErrorBag()->count())
                <div class="vpx-errors">
                    <i class="demo-pli-information me-1"></i>
                    <ul>
                        @foreach ($this->getErrorBag()->toArray() as $value)
                            <li>{{ $value[0] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="modal-body vpx-body">
                {{-- ============ STEP 1 : SELECT INVOICES ============ --}}
                <div class="vpx-section">
                    <div class="vpx-step-head">
                        <span class="vpx-step-badge">1</span>
                        <div>
                            <h4 class="vpx-step-title">Select Invoices</h4>
                            <span class="vpx-step-hint">Tick the bills you want to settle — the payment amount fills in automatically.</span>
                        </div>
                    </div>

                    <div class="table-responsive vpx-table-wrap">
                        <table class="table align-middle mb-0 vpx-table">
                            <thead>
                                <tr>
                                    <th class="ps-3">
                                        <div class="form-check mb-0">
                                            <input type="checkbox" class="form-check-input" wire:model.live="checkAll" id="checkAll">
                                            <label class="form-check-label small text-muted" for="checkAll">All</label>
                                        </div>
                                    </th>
                                    <th>Invoice No</th>
                                    <th class="text-end">Purchase</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-end">Grand Total</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-end vpx-col-input">Discount</th>
                                    <th class="text-end vpx-col-input">Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data as $item)
                                    @php
                                        $vendorPayment = $vendor_purchases[$item->id];
                                        $rowSelected = $vendor_purchases[$item->id]['selected'];
                                        $disabled = $rowSelected ? '' : 'disabled';
                                    @endphp
                                    <tr wire:key="item-{{ $item->id }}" class="{{ $rowSelected ? 'vpx-row-active' : '' }}">
                                        <td class="ps-3">
                                            <div class="form-check mb-0">
                                                <input type="checkbox" class="form-check-input" wire:model="vendor_purchases.{{ $item->id }}.selected"
                                                    wire:change="selectAction({{ $item->id }})" value="{{ $item->id }}">
                                            </div>
                                        </td>
                                        <td class="text-nowrap">
                                            <a href="{{ route('purchase::edit', $item->id) }}" class="vpx-inv-link" target="_blank">
                                                {{ $item->invoice_no }}
                                            </a>
                                            <span class="vpx-inv-id">#{{ $item->id }}</span>
                                        </td>
                                        <td class="text-end">{{ currency($item->total) }}</td>
                                        <td class="text-end text-danger">
                                            {{ $item->other_discount != 0 ? '-' . currency($item->other_discount) : '—' }}
                                        </td>
                                        <td class="text-end fw-bold vpx-accent-text">{{ currency($item->grand_total) }}</td>
                                        <td class="text-end text-success">{{ currency($item->paid) }}</td>
                                        <td class="text-end text-danger fw-semibold">{{ $item->balance != 0 ? currency($item->balance) : '—' }}</td>
                                        <td class="vpx-col-input">
                                            {{ html()->number('discount')->value($vendorPayment['discount'])->class('form-control form-control-sm number select_on_focus vpx-num')->attribute('step', 'any')->attribute('max', $item['balance'] - $vendorPayment['payment'])->attribute($disabled)->attribute('wire:model.live', 'vendor_purchases.' . $item['id'] . '.discount') }}
                                        </td>
                                        <td class="vpx-col-input">
                                            {{ html()->number('payment')->value($vendorPayment['payment'])->class('form-control form-control-sm number select_on_focus vpx-num')->attribute('step', 'any')->attribute('max', $item['balance'] - $vendorPayment['discount'])->attribute($disabled)->attribute('wire:model.live', 'vendor_purchases.' . $item['id'] . '.payment') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            <i class="demo-pli-inbox-empty d-block mb-2" style="font-size:1.6rem;opacity:.5"></i>
                                            No outstanding invoices for this vendor.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="vpx-total-row">
                                    <th class="ps-3" colspan="2">TOTALS</th>
                                    <th class="text-end">{{ currency($total['total']) }}</th>
                                    <th class="text-end text-danger">-{{ currency($total['other_discount']) }}</th>
                                    <th class="text-end vpx-accent-text">{{ currency($total['grand_total']) }}</th>
                                    <th class="text-end text-success">{{ currency($total['paid']) }}</th>
                                    <th class="text-end text-danger">{{ currency($total['balance']) }}</th>
                                    <th class="text-end text-danger">-{{ currency($totalDiscount) }}</th>
                                    <th class="text-end text-success">{{ currency($totalPayment) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- ============ STEP 2 : PAYMENT DETAILS ============ --}}
                <div class="vpx-section vpx-pay-section {{ $hasSelection ? '' : 'vpx-locked' }}">
                    <div class="vpx-step-head">
                        <span class="vpx-step-badge">2</span>
                        <div>
                            <h4 class="vpx-step-title">Payment Details</h4>
                            <span class="vpx-step-hint">Confirm the amount, choose a payment mode and save.</span>
                        </div>
                    </div>

                    @unless ($hasSelection)
                        <div class="vpx-lock-note">
                            <i class="demo-pli-lock-2 me-2"></i>
                            <span>Select at least one invoice in <strong>Step&nbsp;1</strong> to unlock the payment form.</span>
                        </div>
                    @endunless

                    <div class="vpx-pay-card">
                        <fieldset @disabled(!$hasSelection) class="vpx-fieldset">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="vpx-label" for="date"><i class="demo-psi-calendar-4"></i> Date</label>
                                    {{ html()->date('date')->class('form-control form-control-sm')->id('date')->required(true)->attribute('wire:model', 'payment.date') }}
                                </div>
                                <div class="col-md-3">
                                    <label class="vpx-label" for="amount"><i class="fa fa-money"></i> Amount</label>
                                    {{ html()->number('amount')->class('form-control form-control-sm number vpx-amount')->id('amount')->attribute('step', 'any')->required(true)->attribute('wire:model.live', 'payment.amount') }}
                                </div>
                                <div class="col-md-6" wire:ignore>
                                    <label class="vpx-label" for="payment_method_id"><i class="demo-pli-credit-card-2"></i> Payment Mode</label>
                                    {{ html()->select('payment_method_id', $paymentMethods)->value($default_payment_method_id)->class('select-payment_method_id-list')->id('payment_method_id')->placeholder('Select Payment Method') }}
                                </div>
                            </div>

                            @if ($isChequeMethod)
                                <div class="row g-3 mt-1 vpx-cheque-block">
                                    <div class="col-12">
                                        <span class="vpx-subhead"><i class="demo-pli-check me-1"></i> Cheque Information</span>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="vpx-label" for="cheque_no"><i class="demo-pli-check"></i> Cheque No</label>
                                        {{ html()->text('cheque_no')->class('form-control form-control-sm')->id('cheque_no')->required(true)->attribute('wire:model', 'payment.cheque_no') }}
                                    </div>
                                    <div class="col-md-4">
                                        <label class="vpx-label" for="bank_name"><i class="demo-pli-bank"></i> Bank Name</label>
                                        {{ html()->text('bank_name')->class('form-control form-control-sm')->id('bank_name')->attribute('wire:model', 'payment.bank_name') }}
                                    </div>
                                    <div class="col-md-4">
                                        <label class="vpx-label" for="cheque_date"><i class="demo-psi-calendar-4"></i> Cheque Date</label>
                                        {{ html()->date('cheque_date')->class('form-control form-control-sm')->id('cheque_date')->required(true)->attribute('wire:model', 'payment.cheque_date') }}
                                    </div>
                                </div>
                            @endif

                            <div class="row g-3 mt-0">
                                <div class="col-12">
                                    <label class="vpx-label" for="remarks"><i class="demo-pli-file-edit"></i> Remarks</label>
                                    {{ html()->text('remarks')->class('form-control form-control-sm')->id('remarks')->placeholder('Optional note…')->attribute('wire:model', 'payment.remarks') }}
                                </div>
                            </div>
                        </fieldset>

                        <div class="vpx-actions">
                            <div class="vpx-actions-summary">
                                <span class="vpx-actions-label">Total to pay</span>
                                <span class="vpx-actions-amount">{{ currency($totalPayment) }}</span>
                                @if ($totalDiscount > 0)
                                    <span class="vpx-actions-disc">incl. {{ currency($totalDiscount) }} discount</span>
                                @endif
                            </div>
                            <button type="submit" class="btn btn-success vpx-save" @disabled(!$hasSelection)>
                                <i class="demo-pli-check me-1"></i> Save Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            $('#payment_method_id').on('change', function(e) {
                const value = $(this).val() || null;
                const text = $(this).text() || null;
                @this.set('payment.payment_method_id', value);
                $('#payment').select();
            });
        </script>
    @endpush

    <style>
        .vpx-shell { border: none; }
            /* ---- Hero ---- */
            .vpx-hero {
                display: flex; align-items: center; justify-content: space-between;
                flex-wrap: wrap; gap: 1rem;
                background: linear-gradient(135deg, var(--bs-primary, #2c7be5) 0%, color-mix(in srgb, var(--bs-primary, #2c7be5) 70%, #000) 100%);
                color: #fff; padding: 1rem 1.25rem; border: none;
            }
            .vpx-hero-main { display: flex; align-items: center; gap: .85rem; }
            .vpx-hero-icon {
                width: 44px; height: 44px; border-radius: 12px; flex: 0 0 auto;
                background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center;
                font-size: 1.25rem;
            }
            .vpx-hero-title { margin: 0; font-size: 1.15rem; font-weight: 700; line-height: 1.2; color: #fff; }
            .vpx-hero-sub { font-size: .78rem; opacity: .8; letter-spacing: .3px; text-transform: uppercase; }
            .vpx-hero-stats { display: flex; gap: .5rem; flex-wrap: wrap; }
            .vpx-stat {
                background: rgba(255,255,255,.12); border-radius: 10px; padding: .4rem .75rem;
                min-width: 90px; display: flex; flex-direction: column; line-height: 1.3;
            }
            .vpx-stat-accent { background: rgba(255,255,255,.24); }
            .vpx-stat-label { font-size: .66rem; text-transform: uppercase; letter-spacing: .4px; opacity: .8; }
            .vpx-stat-value { font-size: 1rem; font-weight: 700; }
            .vpx-stat-value small { font-size: .66rem; font-weight: 500; opacity: .8; }
            .vpx-stat .text-danger { color: #ffd7d7 !important; }

            /* ---- Errors ---- */
            .vpx-errors {
                display: flex; gap: .5rem; align-items: flex-start;
                margin: .75rem 1rem 0; padding: .6rem .85rem;
                background: #fff5f5; border: 1px solid #f5c2c7; border-radius: 10px; color: #b02a37;
            }
            .vpx-errors ul { margin: 0; padding-left: 1rem; font-size: .82rem; }

            /* ---- Body / sections ---- */
            .vpx-body { padding: 1rem 1.25rem 1.25rem; }
            .vpx-section { margin-bottom: 1.25rem; }
            .vpx-section:last-child { margin-bottom: 0; }
            .vpx-step-head { display: flex; align-items: center; gap: .65rem; margin-bottom: .65rem; }
            .vpx-step-badge {
                width: 26px; height: 26px; border-radius: 50%; flex: 0 0 auto;
                background: var(--bs-primary, #2c7be5); color: #fff; font-weight: 700; font-size: .85rem;
                display: flex; align-items: center; justify-content: center;
            }
            .vpx-step-title { margin: 0; font-size: .98rem; font-weight: 700; }
            .vpx-step-hint { font-size: .76rem; color: #6c757d; }

            /* ---- Table ---- */
            .vpx-table-wrap { border: 1px solid #e6e9ee; border-radius: 12px; overflow: hidden; }
            .vpx-table { margin: 0; font-size: .84rem; }
            .vpx-table thead th {
                background: #f6f8fb; color: #55606e; font-size: .7rem; text-transform: uppercase;
                letter-spacing: .4px; font-weight: 700; border-bottom: 1px solid #e6e9ee; padding: .55rem .6rem; white-space: nowrap;
            }
            .vpx-table tbody td { padding: .5rem .6rem; border-bottom: 1px solid #f0f2f5; vertical-align: middle; }
            .vpx-table tbody tr:last-child td { border-bottom: none; }
            .vpx-table tbody tr { transition: background .15s ease; }
            .vpx-table tbody tr:hover { background: #f9fbfd; }
            .vpx-row-active { background: color-mix(in srgb, var(--bs-primary, #2c7be5) 7%, #fff) !important; }
            .vpx-row-active td { box-shadow: inset 3px 0 0 var(--bs-primary, #2c7be5); }
            .vpx-inv-link { color: var(--bs-primary, #2c7be5); font-weight: 600; text-decoration: none; }
            .vpx-inv-link:hover { text-decoration: underline; }
            .vpx-inv-id { color: #adb5bd; font-size: .72rem; margin-left: .35rem; }
            .vpx-accent-text { color: var(--bs-primary, #2c7be5) !important; }
            .vpx-col-input { width: 130px; }
            .vpx-num { text-align: right; }
            .vpx-num:disabled { background: #f1f3f5; cursor: not-allowed; }
            .vpx-total-row th {
                background: #f6f8fb; border-top: 2px solid #e6e9ee; padding: .6rem; font-size: .82rem; font-weight: 700;
            }

            /* ---- Payment step ---- */
            .vpx-locked .vpx-step-badge { background: #adb5bd; }
            .vpx-lock-note {
                display: flex; align-items: center; gap: .25rem;
                background: #fff8e6; border: 1px dashed #f0c36d; color: #8a6d1a;
                border-radius: 10px; padding: .55rem .85rem; font-size: .82rem; margin-bottom: .65rem;
            }
            .vpx-pay-card { background: #f8fafc; border: 1px solid #e6e9ee; border-radius: 12px; padding: 1rem; }
            .vpx-locked .vpx-pay-card { opacity: .55; }
            .vpx-fieldset { border: none; padding: 0; margin: 0; min-width: 0; }
            .vpx-label {
                display: block; font-size: .72rem; font-weight: 600; color: #6c757d;
                text-transform: uppercase; letter-spacing: .3px; margin-bottom: .3rem;
            }
            .vpx-label i { color: var(--bs-primary, #2c7be5); margin-right: .2rem; }
            .vpx-amount { font-weight: 700; }
            .vpx-subhead { font-size: .74rem; font-weight: 700; color: #55606e; text-transform: uppercase; letter-spacing: .4px; }
            .vpx-cheque-block { border-top: 1px dashed #dee2e6; padding-top: .5rem; }

            /* ---- Actions ---- */
            .vpx-actions {
                display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .75rem;
                margin-top: 1rem; padding-top: .85rem; border-top: 1px solid #e6e9ee;
            }
            .vpx-actions-summary { display: flex; flex-direction: column; line-height: 1.25; }
            .vpx-actions-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .4px; color: #6c757d; }
            .vpx-actions-amount { font-size: 1.35rem; font-weight: 800; color: var(--bs-primary, #2c7be5); }
            .vpx-actions-disc { font-size: .72rem; color: #d63384; }
            .vpx-save { padding: .5rem 1.4rem; font-weight: 600; border-radius: 10px; }
            .vpx-save:disabled { opacity: .5; cursor: not-allowed; }

            @media (max-width: 575.98px) {
                .vpx-hero { flex-direction: column; align-items: flex-start; }
                .vpx-hero-stats { width: 100%; }
                .vpx-stat { flex: 1; }
            }
    </style>
</div>
