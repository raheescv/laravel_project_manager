{{-- Multiple Payment Term Modal - follows vendor-payment-modal pattern --}}
<div class="modal fade" id="MultiplePaymentTermModal" tabindex="-1" aria-hidden="true"
     x-data="multipleTermModalData()" x-cloak>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            {{-- Header --}}
            <div class="modal-header py-2 px-3 text-white border-0"
                 style="background: linear-gradient(135deg, #5b7fb5, #3f6096);">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="fa fa-calendar me-2"></i> Add Multiple Payment Terms
                </h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body p-3">
                {{-- Agreement Info Summary --}}
                <div class="card border mb-3">
                    <div class="card-body p-2">
                        <div class="row g-2 small">
                            <div class="col-6 col-md-4 d-flex justify-content-between">
                                <span class="fw-semibold text-muted">Price:</span>
                                <span class="fw-bold" x-text="formatNumber(info.rent)"></span>
                            </div>
                            <div class="col-6 col-md-4 d-flex justify-content-between">
                                <span class="fw-semibold text-muted">Terms:</span>
                                <span class="fw-bold" x-text="info.noOfTerms"></span>
                            </div>
                            <div class="col-6 col-md-4 d-flex justify-content-between">
                                <span class="fw-semibold text-muted">Frequency:</span>
                                <span class="fw-bold" x-text="info.frequency"></span>
                            </div>
                            <div class="col-6 col-md-6 d-flex justify-content-between">
                                <span class="fw-semibold text-muted">Start:</span>
                                <span x-text="info.startDate"></span>
                            </div>
                            <div class="col-6 col-md-6 d-flex justify-content-between">
                                <span class="fw-semibold text-muted">End:</span>
                                <span x-text="info.endDate"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Fields --}}
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small mb-1">From Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm" x-model="form.fromDate" @change="generatePreview()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small mb-1">No Of Terms</label>
                        <input type="number" class="form-control form-control-sm" x-model.number="form.noOfTerms" @input="generatePreview()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small mb-1">Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-sm" x-model="form.rent" step="0.01" @input="generatePreview()">
                    </div>
                </div>

                {{-- Preview Table --}}
                <template x-if="previewList.length > 0">
                    <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                        <table class="table table-striped table-bordered table-sm mb-0">
                            <thead class="table-dark sticky-top" style="z-index: 1;">
                                <tr>
                                    <th class="py-1" style="width: 40px;">#</th>
                                    <th class="py-1">Date</th>
                                    <th class="py-1 text-end">Amount</th>
                                    <th class="py-1 text-end">Discount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in previewList" :key="index">
                                    <tr>
                                        <td class="py-1" x-text="index + 1"></td>
                                        <td class="py-1" x-text="formatDate(item.date)"></td>
                                        <td class="py-1 text-end" x-text="formatNumber(item.rent)"></td>
                                        <td class="py-1 text-end" x-text="formatNumber(item.discount)"></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold" style="color: #b94a3a;">
                                    <td colspan="2" class="py-1 text-end">Total</td>
                                    <td class="py-1 text-end" x-text="formatNumber(previewList.reduce((s, i) => s + parseFloat(i.rent || 0), 0))"></td>
                                    <td class="py-1 text-end" x-text="formatNumber(previewList.reduce((s, i) => s + parseFloat(i.discount || 0), 0))"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </template>
                <template x-if="previewList.length === 0">
                    <div class="text-center text-muted py-3 small">No terms to generate.</div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="modal-footer py-2 px-3 border-top">
                <span class="me-auto badge bg-secondary" x-show="previewList.length > 0" x-text="previewList.length + ' terms'"></span>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Close
                </button>
                <button type="button" class="btn btn-sm btn-success" @click="save()" :disabled="saving || previewList.length === 0">
                    <i class="fa fa-check me-1"></i>
                    <span x-text="saving ? 'Saving...' : 'Save'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function multipleTermModalData() {
    return {
        saving: false,
        form: { fromDate: '', noOfTerms: 12, rent: 0 },
        info: { rent: 0, noOfTerms: 0, frequency: '', startDate: '', endDate: '' },
        previewList: [],
        frequency: 'Monthly',
        endDate: null,
        init() {
            Livewire.on('open-multiple-term-modal', (params) => {
                var data = Array.isArray(params) ? params[0] : params;
                this.form = {
                    fromDate: data.fromDate || '',
                    noOfTerms: data.noOfTerms || 12,
                    rent: data.rent || 0
                };
                this.info = data.info || {};
                this.frequency = data.frequency || 'Monthly';
                this.endDate = data.endDate || null;
                this.saving = false;
                this.generatePreview();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('MultiplePaymentTermModal')).show();
            });
            Livewire.on('multiple-terms-saved', () => {
                this.saving = false;
                bootstrap.Modal.getInstance(document.getElementById('MultiplePaymentTermModal'))?.hide();
            });
        },
        generatePreview() {
            this.previewList = [];
            if (!this.form.fromDate || !this.form.noOfTerms || !this.form.rent) return;
            var freq = this.getFrequencyParams(this.frequency);
            for (var i = 0; i < this.form.noOfTerms; i++) {
                var d = new Date(this.form.fromDate + 'T00:00:00');
                if (freq.unit === 'days') d.setDate(d.getDate() + (i * freq.multiplier));
                else if (freq.unit === 'weeks') d.setDate(d.getDate() + (i * freq.multiplier * 7));
                else if (freq.unit === 'months') d.setMonth(d.getMonth() + (i * freq.multiplier));
                else if (freq.unit === 'years') d.setFullYear(d.getFullYear() + (i * freq.multiplier));
                if (this.endDate && d > new Date(this.endDate + 'T23:59:59')) break;
                var year = d.getFullYear();
                var month = String(d.getMonth() + 1).padStart(2, '0');
                var day = String(d.getDate()).padStart(2, '0');
                this.previewList.push({ date: year + '-' + month + '-' + day, rent: this.form.rent, discount: 0 });
            }
        },
        getFrequencyParams(freq) {
            var map = {
                'Daily': { unit: 'days', multiplier: 1 },
                'Weekly': { unit: 'weeks', multiplier: 1 },
                'Bi-Weekly': { unit: 'weeks', multiplier: 2 },
                'Monthly': { unit: 'months', multiplier: 1 },
                'Quarterly': { unit: 'months', multiplier: 3 },
                'Half Yearly': { unit: 'months', multiplier: 6 },
                'Yearly': { unit: 'years', multiplier: 1 },
                'One Time': { unit: 'years', multiplier: 100 }
            };
            return map[freq] || map['Monthly'];
        },
        formatNumber(n) { return parseFloat(n || 0).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        formatDate(d) {
            if (!d) return '';
            var parts = d.split('-');
            return parts[2] + '-' + parts[1] + '-' + parts[0];
        },
        save() {
            if (this.previewList.length === 0) return;
            this.saving = true;
            Livewire.dispatch('saveMultipleTermsFromModal', {
                terms: JSON.parse(JSON.stringify(this.previewList))
            });
        }
    }
}
</script>
@endpush
