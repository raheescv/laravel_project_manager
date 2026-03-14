<div>
    {{-- Header --}}
    <div class="modal-header bg-primary text-white border-0 py-2 px-3">
        <h6 class="modal-title fw-bold mb-0 text-white">
            <i class="fa fa-cogs me-2"></i> {{ $editingId ? 'Edit Service' : 'Add Service' }}
        </h6>
        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-header">
        @if ($this->getErrorBag()->count())
            <ol>
                @foreach ($this->getErrorBag()->toArray() as $value)
                    <li style="color:red">* {{ $value[0] }}</li>
                @endforeach
            </ol>
        @endif
    </div>

    {{-- Body --}}
    <div class="modal-body p-3">
        <div class="row g-3">
            <div class="col-md-6 col-12">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-calendar me-1 text-muted"></i> Date <span class="text-danger">*</span>
                </label>
                <input type="date" class="form-control form-control-sm" wire:model="form.date">
                @error('form.date')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6 col-12">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-money me-1 text-muted"></i> Amount <span class="text-danger">*</span>
                </label>
                <input type="number" class="form-control form-control-sm" wire:model="form.amount" step="0.01">
                @error('form.amount')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6 col-12" wire:ignore>
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-tag me-1 text-muted"></i> Category <span class="text-danger">*</span>
                </label>
                <select class="select-account_id-list" wire:model="form.category" id='service_category_id'>
                    <option value="">Please Select Any</option>
                </select>
            </div>
            <div class="col-md-6 col-12" wire:ignore>
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-credit-card me-1 text-muted"></i> Payment Mode <span class="text-danger">*</span>
                </label>
                <select class="select-payment_method_id-list" wire:model="form.account_id" id="service_account_id">
                    <option value="">Please Select Any</option>
                </select>
            </div>
            <div class="col-12 mt-3">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-comment-o me-1 text-muted"></i> Remark
                </label>
                <textarea class="form-control form-control-sm" wire:model="form.remark" rows="3" placeholder="Optional remark..."></textarea>
            </div>
        </div>
    </div>
    {{-- Footer --}}
    <div class="modal-footer py-2 px-3 border-top">
        <button type="button" class="btn btn-sm btn-primary" wire:click="payNow" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="payNow"><i class="fa fa-credit-card me-1"></i> Pay Now</span>
            <span wire:loading wire:target="payNow"><i class="fa fa-spinner fa-spin me-1"></i> Saving...</span>
        </button>
        @if(!$editingId)
        <button type="button" class="btn btn-sm btn-success" wire:click="payLater" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="payLater"><i class="fa fa-clock-o me-1"></i> Pay Later</span>
            <span wire:loading wire:target="payLater"><i class="fa fa-spinner fa-spin me-1"></i>
                Saving...</span>
        </button>
        @endif
    </div>
    @push('scripts')
        <script>
            document.addEventListener('livewire:init', function() {
                // Helper to reset a TomSelect by element ID
                function resetTomSelect(id) {
                    var el = document.getElementById(id);
                    if (el && el.tomselect) {
                        el.tomselect.clear();
                        el.tomselect.clearOptions();
                    }
                }

                // Helper to set a TomSelect value with label
                function setTomSelectValue(id, value, label) {
                    var el = document.getElementById(id);
                    if (el && el.tomselect) {
                        el.tomselect.clearOptions();
                        if (value) {
                            el.tomselect.addOption({ id: value, name: label || value });
                            el.tomselect.setValue(value, true);
                        }
                    }
                }

                // Reset TomSelects when modal opens
                Livewire.on('ToggleServiceModal', () => {
                    setTimeout(() => {
                        let categoryVal = @this.get('form.category');
                        let categoryName = @this.get('editCategoryName');
                        let accountVal = @this.get('form.account_id');
                        let accountName = @this.get('editAccountName');

                        if (categoryVal && categoryName) {
                            setTomSelectValue('service_category_id', categoryVal, categoryName);
                        } else {
                            resetTomSelect('service_category_id');
                        }

                        if (accountVal && accountName) {
                            setTomSelectValue('service_account_id', accountVal, accountName);
                        } else {
                            resetTomSelect('service_account_id');
                        }
                    }, 100);
                });

                $('#service_category_id').on('change', function() {
                    @this.set('form.category', $(this).val());
                });
                $('#service_account_id').on('change', function() {
                    @this.set('form.account_id', $(this).val());
                });
            });
        </script>
    @endpush
</div>
