<div>
    <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold">
            <i class="demo-psi-home fs-4 me-2 text-primary"></i>
            {{ isset($formData['id']) ? 'Edit Property' : 'Create New Property' }}
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
                {{-- Row 1: Number*, Building*, Furniture --}}
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.number') is-invalid @enderror" id="number" placeholder="Enter No" required wire:model="formData.number" autofocus>
                        <label for="number">Number <span class="text-danger">*</span></label>
                        @error('formData.number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Building <span class="text-danger">*</span></label>
                    <div wire:ignore>
                        <select class="select-property_building_id" id="modal_property_building_id" placeholder="Search Here">
                            <option value="">Search Here</option>
                        </select>
                    </div>
                    @error('formData.property_building_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select @error('formData.furniture') is-invalid @enderror" id="furniture" wire:model="formData.furniture">
                            <option value="Yes">Yes</option>
                            <option value="Semi">Semi</option>
                            <option value="None">None</option>
                        </select>
                        <label for="furniture">Furniture</label>
                        @error('formData.furniture')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Row 2: Type*, Floor, Hall --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                    <div wire:ignore>
                        <select class="select-property_type_id" id="modal_property_type_id" placeholder="Search Here">
                            <option value="">Search Here</option>
                        </select>
                    </div>
                    @error('formData.property_type_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.floor') is-invalid @enderror" id="floor" placeholder="Enter which Floor" wire:model="formData.floor">
                        <label for="floor">Floor</label>
                        @error('formData.floor')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.hall') is-invalid @enderror" id="hall" placeholder="Enter no of Hall" wire:model="formData.hall">
                        <label for="hall">Hall</label>
                        @error('formData.hall')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Row 3: Size --}}
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.size') is-invalid @enderror" id="size" placeholder="Size of the Unit" wire:model="formData.size">
                        <label for="size">Size</label>
                        @error('formData.size')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Row 4: Electricity, Rent*, Rooms --}}
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.electricity') is-invalid @enderror" id="electricity" placeholder="Enter the Electricity no" wire:model="formData.electricity">
                        <label for="electricity">Electricity</label>
                        @error('formData.electricity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.rent') is-invalid @enderror" id="rent" placeholder="Enter Rent" required wire:model="formData.rent">
                        <label for="rent">Rent <span class="text-danger">*</span></label>
                        @error('formData.rent')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.rooms') is-invalid @enderror" id="rooms" placeholder="Enter no of Rooms" wire:model="formData.rooms">
                        <label for="rooms">Rooms</label>
                        @error('formData.rooms')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Row 5: Ownership, Kahramaa, Parking --}}
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.ownership') is-invalid @enderror" id="ownership" placeholder="Enter the Ownership" wire:model="formData.ownership">
                        <label for="ownership">Ownership</label>
                        @error('formData.ownership')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.kahramaa') is-invalid @enderror" id="kahramaa" placeholder="Enter Kahramaa" wire:model="formData.kahramaa">
                        <label for="kahramaa">Kahramaa</label>
                        @error('formData.kahramaa')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.parking') is-invalid @enderror" id="parking" placeholder="Enter Parking" wire:model="formData.parking">
                        <label for="parking">Parking</label>
                        @error('formData.parking')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Row 6: Kitchen, Toilet, Flag* --}}
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.kitchen') is-invalid @enderror" id="kitchen" placeholder="Enter no of Kitchen" wire:model="formData.kitchen">
                        <label for="kitchen">Kitchen</label>
                        @error('formData.kitchen')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.toilet') is-invalid @enderror" id="toilet" placeholder="Enter No Of Toilet" wire:model="formData.toilet">
                        <label for="toilet">Toilet</label>
                        @error('formData.toilet')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select @error('formData.flag') is-invalid @enderror" id="flag" wire:model="formData.flag" required>
                            <option value="active">ACTIVE</option>
                            <option value="disabled">DISABLED</option>
                        </select>
                        <label for="flag">Flag <span class="text-danger">*</span></label>
                        @error('formData.flag')
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

                {{-- Row 8: Floor Plan Image --}}
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Floor Plan Image</label>
                    <input type="file" class="form-control @error('floor_plan') is-invalid @enderror" wire:model="floor_plan" accept="image/*">
                    <small class="text-muted">Upload a floor plan image (JPG, PNG, GIF)</small>
                    @error('floor_plan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @if(isset($formData['floor_plan']) && $formData['floor_plan'])
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $formData['floor_plan']) }}" alt="Floor Plan" class="img-fluid rounded border" style="max-height: 200px;">
                        </div>
                    @endif
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
                    <span>Save Property</span>
                </button>
            </div>
        </div>
    </form>

    @push('scripts')
        @include('components.select.propertyBuildingSelect')
        @include('components.select.propertyTypeSelect')
        <script>
            $(document).ready(function() {
                $('#modal_property_building_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('formData.property_building_id', value);
                });
                $('#modal_property_type_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('formData.property_type_id', value);
                });
                window.addEventListener('SelectPropertyDropDownValues', event => {
                    var data = event.detail[0];
                    if (data && data.property_building_id) {
                        var buildingTomSelect = document.querySelector('#modal_property_building_id').tomselect;
                        if (buildingTomSelect && data.building) {
                            buildingTomSelect.addOption({ id: data.property_building_id, name: data.building.name });
                            buildingTomSelect.addItem(data.property_building_id);
                        }
                    }
                    if (data && data.property_type_id) {
                        var typeTomSelect = document.querySelector('#modal_property_type_id').tomselect;
                        if (typeTomSelect && data.type) {
                            typeTomSelect.addOption({ id: data.property_type_id, name: data.type.name });
                            typeTomSelect.addItem(data.property_type_id);
                        }
                    }
                });
            });
        </script>
    @endpush
</div>
