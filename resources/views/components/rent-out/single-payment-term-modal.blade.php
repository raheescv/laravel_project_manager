{{-- Single Payment Term Modal - follows vendor-payment-modal pattern --}}
<div class="modal fade" id="SinglePaymentTermModal" tabindex="-1" aria-hidden="true"
     x-data="singleTermModalData()" x-cloak>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            {{-- Header --}}
            <div class="modal-header py-2 px-3 text-white border-0"
                 style="background: linear-gradient(135deg, #3a9e7a, #2e7d56);">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="fa fa-calendar-plus-o me-2"></i>
                    <span x-text="editingTermId ? 'Edit Payment Term' : 'Add Single Payment Term'"></span>
                </h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body p-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small mb-1">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm" x-model="form.due_date">
                        <template x-if="errors.due_date">
                            <small class="text-danger" x-text="errors.due_date"></small>
                        </template>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small mb-1">Label</label>
                        <select class="form-select form-select-sm" x-model="form.label">
                            <option value="">Select</option>
                            @foreach(paymentTermLabels() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small mb-1">Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-sm" x-model="form.amount" step="0.01">
                        <template x-if="errors.amount">
                            <small class="text-danger" x-text="errors.amount"></small>
                        </template>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small mb-1">Discount</label>
                        <input type="number" class="form-control form-control-sm" x-model="form.discount" step="0.01">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small mb-1">Remark</label>
                        <textarea class="form-control form-control-sm" x-model="form.remarks" rows="2" placeholder="Optional remark..."></textarea>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer py-2 px-3 border-top">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Close
                </button>
                <button type="button" class="btn btn-sm btn-success" @click="save()" :disabled="saving">
                    <i class="fa fa-check me-1"></i>
                    <span x-text="saving ? 'Saving...' : 'Save'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function singleTermModalData() {
    return {
        editingTermId: null,
        saving: false,
        form: { due_date: '', label: '', amount: 0, discount: 0, remarks: '' },
        errors: {},
        init() {
            Livewire.on('open-single-term-modal', (params) => {
                var data = Array.isArray(params) ? params[0] : params;
                this.form = data.form || { due_date: new Date().toISOString().split('T')[0], label: '', amount: 0, discount: 0, remarks: '' };
                this.editingTermId = data.editingTermId || null;
                this.errors = {};
                this.saving = false;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('SinglePaymentTermModal')).show();
            });
            Livewire.on('single-term-saved', () => {
                this.saving = false;
                bootstrap.Modal.getInstance(document.getElementById('SinglePaymentTermModal'))?.hide();
            });
        },
        save() {
            this.errors = {};
            if (!this.form.due_date) { this.errors.due_date = 'Date is required.'; return; }
            if (!this.form.amount || this.form.amount <= 0) { this.errors.amount = 'Amount is required.'; return; }
            this.saving = true;
            Livewire.dispatch('saveSingleTermFromModal', {
                form: JSON.parse(JSON.stringify(this.form)),
                editingTermId: this.editingTermId
            });
        }
    }
}
</script>
@endpush
