<div>
    <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold">
            <i class="demo-psi-building fs-4 me-2 text-primary"></i>
            {{ isset($formData['id']) ? 'Edit Building' : 'Create New Building' }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body py-4">
            @if ($errors->any())
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i class="demo-pli-danger-2 fs-4 me-2"></i>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-4">
                {{-- Row 1: Name*, Group/Project* --}}
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.name') is-invalid @enderror" id="name" placeholder="Enter Name" required wire:model="formData.name" maxlength="30" autofocus>
                        <label for="name">Name <span class="text-danger">*</span></label>
                        @error('formData.name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Group/Project <span class="text-danger">*</span></label>
                    <div wire:ignore>
                        <select class="select-property_group_id form-control" id="modal_property_group_id" placeholder="Search Here">
                            <option value="">Search Here</option>
                        </select>
                    </div>
                    @error('formData.property_group_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Row 2: Arabic Name --}}
                <div class="col-md-12">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.arabic_name') is-invalid @enderror" id="arabic_name" placeholder="Enter Arabic Name" wire:model="formData.arabic_name" maxlength="30" dir="rtl">
                        <label for="arabic_name">Arabic Name</label>
                        @error('formData.arabic_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Row 3: Created Date, Reference Code, Building No --}}
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="date" class="form-control @error('formData.created_date') is-invalid @enderror" id="created_date" wire:model="formData.created_date">
                        <label for="created_date">Created Date</label>
                        @error('formData.created_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.reference_code') is-invalid @enderror" id="reference_code" placeholder="Enter Reference Code" wire:model="formData.reference_code">
                        <label for="reference_code">Reference Code</label>
                        @error('formData.reference_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.building_no') is-invalid @enderror" id="building_no" placeholder="Enter Building No" wire:model="formData.building_no">
                        <label for="building_no">Building No</label>
                        @error('formData.building_no')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Row 4: Location, Floors, Investment --}}
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.location') is-invalid @enderror" id="location" placeholder="Enter Location" wire:model="formData.location">
                        <label for="location">Location</label>
                        @error('formData.location')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.floors') is-invalid @enderror" id="floors" placeholder="Enter No Of Floors" wire:model="formData.floors">
                        <label for="floors">Floors</label>
                        @error('formData.floors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.investment') is-invalid @enderror" id="investment" placeholder="Enter Investment" wire:model="formData.investment">
                        <label for="investment">Investment</label>
                        @error('formData.investment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Row 5: Electricity, Road, Landmark --}}
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.electricity') is-invalid @enderror" id="electricity" placeholder="Enter Electricity" wire:model="formData.electricity">
                        <label for="electricity">Electricity</label>
                        @error('formData.electricity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.road') is-invalid @enderror" id="road" placeholder="Enter Road" wire:model="formData.road">
                        <label for="road">Road</label>
                        @error('formData.road')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.landmark') is-invalid @enderror" id="landmark" placeholder="Enter Landmark" wire:model="formData.landmark">
                        <label for="landmark">Landmark</label>
                        @error('formData.landmark')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Row 6: Amount, Owner Mode, Status --}}
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.amount') is-invalid @enderror" id="amount" placeholder="Enter Amount To Pay" wire:model="formData.amount">
                        <label for="amount">Amount</label>
                        @error('formData.amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select @error('formData.ownership') is-invalid @enderror" id="ownership" wire:model="formData.ownership">
                            <option value="">Please Select</option>
                            @foreach(\App\Enums\Property\BuildingOwnership::cases() as $ownership)
                                <option value="{{ $ownership->value }}">{{ $ownership->label() }}</option>
                            @endforeach
                        </select>
                        <label for="ownership">Owner Mode</label>
                        @error('formData.ownership')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select @error('formData.status') is-invalid @enderror" id="status" wire:model="formData.status">
                            <option value="">Please Select</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <label for="status">Status</label>
                        @error('formData.status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Row 7: Remark --}}
                <div class="col-md-12">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.remark') is-invalid @enderror" id="remark" placeholder="Enter Remark" wire:model="formData.remark">
                        <label for="remark">Remark</label>
                        @error('formData.remark')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <div class="ms-auto d-flex gap-2">
                <button type="button" wire:click="save(1)" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                    <i class="demo-psi-repeat-2 fs-5"></i>
                    <span>Save & Add Another</span>
                </button>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4">
                    <i class="demo-psi-save fs-5"></i>
                    <span>Save Building</span>
                </button>
            </div>
        </div>
    </form>

    @push('scripts')
        @include('components.select.propertyGroupSelect')
        <script>
            $(document).ready(function() {
                $('#modal_property_group_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('formData.property_group_id', value);
                });
                window.addEventListener('SelectBuildingDropDownValues', event => {
                    var data = event.detail[0];
                    if (data && data.property_group_id) {
                        var groupTomSelect = document.querySelector('#modal_property_group_id').tomselect;
                        if (groupTomSelect && data.group) {
                            groupTomSelect.addOption({ id: data.property_group_id, name: data.group.name });
                            groupTomSelect.addItem(data.property_group_id);
                        }
                    }
                });
            });
        </script>
    @endpush
</div>
