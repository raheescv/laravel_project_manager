{{-- Pay Selected Modal - follows vendor-payment-modal pattern --}}
<div class="modal fade" id="PaySelectedModal" tabindex="-1" aria-hidden="true"
     x-data="paySelectedModalData()" x-cloak>
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            {{-- Header --}}
            <div class="modal-header py-2 px-3 text-white border-0"
                 style="background: linear-gradient(135deg, #d4a843, #b8922e);">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="fa fa-money me-2"></i> Pay Selected Terms
                </h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body p-3">
                {{-- Payment Details --}}
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small mb-1">Payment Date</label>
                        <input type="date" class="form-control form-control-sm" x-model="payDate">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small mb-1">Payment Mode</label>
                        <select class="form-select form-select-sm" x-model="payPaymentMode">
                            @foreach(paymentModeOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small mb-1">Remark</label>
                        <input type="text" class="form-control form-control-sm" x-model="payRemark" placeholder="Enter remark...">
                    </div>
                </div>

                {{-- Payment Terms Table --}}
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="py-1" style="width:35px;">#</th>
                                <th class="py-1">Date</th>
                                <th class="py-1">Customer</th>
                                <th class="py-1">Property</th>
                                <th class="py-1 text-end">Balance</th>
                                <th class="py-1" style="width:130px;">Payment Mode</th>
                                <th class="py-1 text-end" style="width:110px;">Amount</th>
                                <th class="py-1">Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="cashTerms.length > 0">
                                <template x-for="(ct, index) in cashTerms" :key="index">
                                    <tr>
                                        <td class="py-1" x-text="index + 1"></td>
                                        <td class="py-1" x-text="ct.date"></td>
                                        <td class="py-1" x-text="ct.customer"></td>
                                        <td class="py-1" x-text="ct.property"></td>
                                        <td class="py-1 text-end fw-semibold" x-text="formatNumber(ct.balance)"></td>
                                        <td class="py-1">
                                            <select class="form-select form-select-sm" x-model="ct.payment_mode">
                                                @foreach(paymentModeOptions() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="py-1">
                                            <input type="number" class="form-control form-control-sm text-end" x-model="ct.amount" step="0.01">
                                        </td>
                                        <td class="py-1">
                                            <input type="text" class="form-control form-control-sm" x-model="ct.remark" placeholder="">
                                        </td>
                                    </tr>
                                </template>
                            </template>
                            <template x-if="cashTerms.length === 0">
                                <tr><td colspan="8" class="text-center text-muted py-3">No pending payment terms selected</td></tr>
                            </template>
                        </tbody>
                        <tfoot class="table-light" x-show="cashTerms.length > 0">
                            <tr class="fw-bold">
                                <td colspan="4" class="py-1 text-end">Total</td>
                                <td class="py-1 text-end" x-text="formatNumber(cashTerms.reduce((s, c) => s + parseFloat(c.balance || 0), 0))"></td>
                                <td></td>
                                <td class="py-1 text-end" style="color: #2e7d56;" x-text="formatNumber(cashTerms.reduce((s, c) => s + parseFloat(c.amount || 0), 0))"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer py-2 px-3 border-top">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Close
                </button>
                <button type="button" class="btn btn-sm btn-success" @click="submit()" :disabled="saving || cashTerms.length === 0">
                    <i class="fa fa-check me-1"></i>
                    <span x-text="saving ? 'Submitting...' : 'Submit Payment'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function paySelectedModalData() {
    return {
        saving: false,
        payDate: new Date().toISOString().split('T')[0],
        payPaymentMode: 'cash',
        payRemark: '',
        cashTerms: [],
        init() {
            Livewire.on('open-pay-selected-modal', (params) => {
                var data = Array.isArray(params) ? params[0] : params;
                this.payDate = data.payDate || new Date().toISOString().split('T')[0];
                this.payPaymentMode = data.payPaymentMode || 'cash';
                this.payRemark = '';
                this.cashTerms = data.cashTerms || [];
                this.saving = false;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('PaySelectedModal')).show();
            });
            Livewire.on('payment-submitted', () => {
                this.saving = false;
                bootstrap.Modal.getInstance(document.getElementById('PaySelectedModal'))?.hide();
            });
        },
        formatNumber(n) { return parseFloat(n || 0).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        submit() {
            this.saving = true;
            Livewire.dispatch('submitPaymentFromModal', {
                payDate: this.payDate,
                payPaymentMode: this.payPaymentMode,
                payRemark: this.payRemark,
                cashTerms: JSON.parse(JSON.stringify(this.cashTerms))
            });
        }
    }
}
</script>
@endpush
