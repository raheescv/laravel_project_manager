<div>
    <form wire:submit="save">
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

        {{-- Section 1: Property & Customer Details --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center">
                    <i class="fa fa-building fs-5 me-2 text-primary"></i>
                    <h5 class="mb-0 fw-bold">Property & Customer Details</h5>
                </div>
            </div>
            <div class="card-body py-4">
                @php
                    $groupOptions = [];
                    if (!empty($rentouts['property_group_id'])) {
                        $group = \App\Models\PropertyGroup::find($rentouts['property_group_id']);
                        if ($group) $groupOptions[$group->id] = $group->name;
                    }
                    $buildingOptions = [];
                    if (!empty($rentouts['property_building_id'])) {
                        $building = \App\Models\PropertyBuilding::find($rentouts['property_building_id']);
                        if ($building) $buildingOptions[$building->id] = $building->name;
                    }
                    $typeOptions = [];
                    if (!empty($rentouts['property_type_id'])) {
                        $propType = \App\Models\PropertyType::find($rentouts['property_type_id']);
                        if ($propType) $typeOptions[$propType->id] = $propType->name;
                    }
                    $propertyOptions = [];
                    if (!empty($rentouts['property_id'])) {
                        $prop = \App\Models\Property::with('building')->find($rentouts['property_id']);
                        if ($prop) $propertyOptions[$prop->id] = $prop->number . ($prop->building ? ' - ' . $prop->building->name : '');
                    }
                    $customerOptions = [];
                    if (!empty($rentouts['account_id'])) {
                        $customer = \App\Models\Account::find($rentouts['account_id']);
                        if ($customer) $customerOptions[$customer->id] = $customer->name;
                    }
                @endphp
                <div class="row g-3">
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label fw-semibold small"><i class="fa fa-map-marker text-primary me-1"></i> Group/Project</label>
                        {{ html()->select('property_group_id', $groupOptions)->value($rentouts['property_group_id'] ?? '')->class('form-select select-property_group_id')->id('property_group_id')->placeholder('Select Group') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label fw-semibold small"><i class="fa fa-building text-success me-1"></i> Building</label>
                        {{ html()->select('property_building_id', $buildingOptions)->value($rentouts['property_building_id'] ?? '')->class('form-select select-property_building_id')->id('property_building_id')->placeholder('Select Building') }}
                    </div>
                    <div class="col-md-2" wire:ignore>
                        <label class="form-label fw-semibold small"><i class="fa fa-home text-info me-1"></i> Type</label>
                        {{ html()->select('property_type_id', $typeOptions)->value($rentouts['property_type_id'] ?? '')->class('form-select select-property_type_id')->id('property_type_id')->placeholder('Select Type') }}
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold small mb-0"><i class="fa fa-key text-warning me-1"></i> Property No/Unit *</label>
                            <label class="form-check-label small text-muted d-flex align-items-center gap-1">
                                <input type="checkbox" class="form-check-input form-check-input-sm" wire:model.live="vacant_only" id="vacant_only">
                                Vacant Only
                            </label>
                        </div>
                        <div wire:ignore>
                            {{ html()->select('property_id', $propertyOptions)->value($rentouts['property_id'] ?? '')->class('form-select select-property_id')->id('property_id')->placeholder('Search Here') }}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold small mb-0"><i class="fa fa-user text-danger me-1"></i> Customer *</label>
                            <div class="d-flex gap-2">
                                @if(isset($rentouts['account_id']) && $rentouts['account_id'])
                                    <a href="#" class="btn btn-sm btn-outline-primary py-0 px-2 edit_customer" title="Edit Customer">
                                        <i class="fa fa-pencil"></i> Edit
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div wire:ignore>
                            {{ html()->select('account_id', $customerOptions)->value($rentouts['account_id'] ?? '')->class('form-select select-customer_id')->id('account_id')->placeholder('Search Customer Name') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Rent Details --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center">
                    <i class="fa fa-money fs-5 me-2 text-warning"></i>
                    <h5 class="mb-0 fw-bold">Rent Details</h5>
                </div>
            </div>
            <div class="card-body py-4">
                {{-- Rental Period --}}
                <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                    <i class="fa fa-calendar text-primary me-2"></i>
                    <h6 class="mb-0 fw-semibold text-muted">Rental Period</h6>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="fa fa-calendar-o text-primary me-1"></i> Start Date *</label>
                        <input type="date" class="form-control" id="start_date" wire:model.live="rentouts.start_date">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="fa fa-calendar text-danger me-1"></i> End Date *</label>
                        <input type="date" class="form-control" id="end_date" wire:model.live="rentouts.end_date">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="fa fa-clock-o text-info me-1"></i> Duration</label>
                        <div class="bg-light rounded-3 p-3 d-flex align-items-center gap-3">
                            @if($days > 30)
                                <div class="d-flex align-items-center">
                                    <span class="text-muted small me-2">Month(s):</span>
                                    <span class="badge bg-primary px-3 py-2 fs-6">{{ $months }}</span>
                                </div>
                            @endif
                            <div class="d-flex align-items-center">
                                <span class="text-muted small me-2">Day(s):</span>
                                <span class="badge bg-info px-3 py-2 fs-6">{{ $days }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Payment Information --}}
                <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                    <i class="fa fa-money text-success me-2"></i>
                    <h6 class="mb-0 fw-semibold text-muted">Payment Information</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="fa fa-list-ol text-info me-1"></i> No of Terms</label>
                        <input type="number" class="form-control" id="no_of_terms" wire:model.live="rentouts.no_of_terms">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="fa fa-repeat text-primary me-1"></i> Payment Frequency</label>
                        <select class="form-select" id="payment_frequency" wire:model="rentouts.payment_frequency">
                            <option value="Monthly">Monthly</option>
                            <option value="Quarterly">Quarterly</option>
                            <option value="Half Yearly">Half Yearly</option>
                            <option value="Yearly">Yearly</option>
                            <option value="One Time">One Time</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="fa fa-money text-warning me-1"></i> Rent</label>
                        <input type="number" class="form-control" id="rent" wire:model.live="rentouts.rent" step="0.01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="fa fa-calculator text-success me-1"></i> Total Amount</label>
                        <div class="bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 p-3 text-center">
                            <span class="fs-4 fw-bold text-success">{{ number_format($rentouts['total'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Additional Details --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center">
                    <i class="fa fa-sliders fs-5 me-2 text-info"></i>
                    <h5 class="mb-0 fw-bold">Additional Details</h5>
                </div>
            </div>
            <div class="card-body py-4">
                <div class="row g-3">
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label fw-semibold small"><i class="fa fa-user text-primary me-1"></i> Salesman</label>
                        @php
                            $salesmanOptions = [];
                            if (!empty($rentouts['salesman_id'])) {
                                $sm = \App\Models\User::find($rentouts['salesman_id']);
                                if ($sm) $salesmanOptions[$sm->id] = $sm->name;
                            }
                        @endphp
                        {{ html()->select('salesman_id', $salesmanOptions)->value($rentouts['salesman_id'] ?? '')->class('form-select select-employee_id-list')->id('salesman_id')->placeholder('Select Employee') }}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="fa fa-bookmark text-warning me-1"></i> Booking Type *</label>
                        <select class="form-select" id="booking_type" wire:model="rentouts.booking_type">
                            <option value="Long Term">Long Term</option>
                            <option value="Short Term">Short Term</option>
                            <option value="Commercial">Commercial</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="fa fa-check-circle text-success me-1"></i> Included Amenities</label>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-warning bg-opacity-10 border-warning border-opacity-25"><i class="fa fa-bolt text-warning"></i></span>
                                    <select class="form-select" wire:model="rentouts.include_electricity_water">
                                        <option value="Included">Elec & Water: Incl.</option>
                                        <option value="Excluded">Elec & Water: Excl.</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-info bg-opacity-10 border-info border-opacity-25"><i class="fa fa-asterisk text-info"></i></span>
                                    <select class="form-select" wire:model="rentouts.include_ac">
                                        <option value="Included">AC: Included</option>
                                        <option value="Excluded">AC: Excluded</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-success bg-opacity-10 border-success border-opacity-25"><i class="fa fa-wifi text-success"></i></span>
                                    <select class="form-select" wire:model="rentouts.include_wifi">
                                        <option value="Included">WiFi: Included</option>
                                        <option value="Excluded">WiFi: Excluded</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold small"><i class="fa fa-comment text-muted me-1"></i> Remark</label>
                        <textarea class="form-control" id="remark" wire:model="rentouts.remark" rows="3" placeholder="Add any additional notes or remarks here..."></textarea>
                    </div>
                </div>

                {{-- Policies & Terms --}}
                <div class="d-flex align-items-center mt-4 mb-3 pb-2 border-bottom">
                    <i class="fa fa-file-text text-primary me-2"></i>
                    <h6 class="mb-0 fw-semibold text-muted">Policies & Terms</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="fa fa-file-text text-primary me-1"></i> Cancellation Policy (English)</label>
                        <input type="text" class="form-control" wire:model="rentouts.cancellation_policy_en" placeholder="Enter cancellation policy in English...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="fa fa-file-text text-success me-1"></i> Cancellation Policy (Arabic)</label>
                        <input type="text" class="form-control" wire:model="rentouts.cancellation_policy_ar" dir="rtl" placeholder="...أدخل قاعدة الإلغاء باللغة العربية">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="fa fa-credit-card text-primary me-1"></i> Payment Terms (English)</label>
                        <input type="text" class="form-control" wire:model="rentouts.payment_terms_en" placeholder="Enter payment terms in English...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="fa fa-credit-card text-success me-1"></i> Payment Terms (Arabic)</label>
                        <input type="text" class="form-control" wire:model="rentouts.payment_terms_ar" dir="rtl" placeholder="...أدخل شروط الدفع باللغة العربية">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="fa fa-credit-card text-warning me-1"></i> Payment Terms Extended (English)</label>
                        <input type="text" class="form-control" wire:model="rentouts.payment_terms_extended_en" placeholder="Enter extended payment terms in English...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="fa fa-credit-card text-info me-1"></i> Payment Terms Extended (Arabic)</label>
                        <input type="text" class="form-control" wire:model="rentouts.payment_terms_extended_ar" dir="rtl" placeholder="...أدخل شروط الدفع الممتدة باللغة العربية">
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 4: Monthly Collection --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center">
                    <i class="fa fa-calendar fs-5 me-2 text-primary"></i>
                    <h5 class="mb-0 fw-bold">Monthly Collection</h5>
                </div>
            </div>
            <div class="card-body py-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="fa fa-calendar text-warning me-1"></i> Collection Starting Day</label>
                        <input type="number" class="form-control" id="collection_starting_day" wire:model="rentouts.collection_starting_day" min="1" max="28">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="fa fa-money text-success me-1"></i> Payment Mode</label>
                        {{ html()->select('collection_payment_mode', paymentModeOptions())->value($rentouts['collection_payment_mode'] ?? '')->class('form-select')->id('collection_payment_mode')->attribute('wire:model.live', 'rentouts.collection_payment_mode')->placeholder('Select...') }}
                    </div>
                    @if(($rentouts['collection_payment_mode'] ?? '') && $rentouts['collection_payment_mode'] !== 'cash')
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small"><i class="fa fa-university text-primary me-1"></i> Bank Name</label>
                            <input type="text" class="form-control" wire:model="rentouts.collection_bank_name" placeholder="Enter bank name">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small"><i class="fa fa-file-text text-info me-1"></i> Cheque Starting No</label>
                            <input type="text" class="form-control" wire:model="rentouts.collection_cheque_no" placeholder="Enter cheque number">
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ $type === 'Booking' ? route('property::rent::booking') : route('property::rent::index') }}" class="btn btn-light d-inline-flex align-items-center gap-2">
                        <i class="fa fa-arrow-left"></i>
                        <span>Back to List</span>
                    </a>
                    <div class="d-flex gap-2">
                        @if(isset($rentouts['id']) && $type === 'Booking' && ($rentouts['status'] ?? '') === 'booked' && !($rentouts['submitted_by'] ?? null))
                            <button type="button" wire:click="confirm" class="btn btn-primary d-inline-flex align-items-center gap-2">
                                <i class="fa fa-check-circle"></i>
                                <span>Confirm</span>
                            </button>
                            <button type="button" wire:click="cancel" wire:confirm="Are you sure you want to cancel this booking?" class="btn btn-danger d-inline-flex align-items-center gap-2">
                                <i class="fa fa-times-circle"></i>
                                <span>Cancel Booking</span>
                            </button>
                        @endif
                        @if(!isset($rentouts['status']) || ($rentouts['status'] ?? '') !== 'cancelled')
                            <button type="submit" class="btn btn-success d-inline-flex align-items-center gap-2 px-4">
                                <i class="fa fa-check"></i>
                                <span>Save</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
        <x-select.propertyGroupSelect />
        <x-select.propertyBuildingSelect />
        <x-select.propertyTypeSelect />
        <x-select.propertySelect />
        <x-select.customerSelect />
        <x-select.employeeSelect />

        <script type="text/javascript">
            $(document).ready(function() {
                // Property selects
                $('#property_group_id').on('change', function() {
                    @this.set('rentouts.property_group_id', $(this).val());
                });
                $('#property_building_id').on('change', function() {
                    @this.set('rentouts.property_building_id', $(this).val());
                });
                $('#property_type_id').on('change', function() {
                    @this.set('rentouts.property_type_id', $(this).val());
                });
                $('#property_id').on('change', function() {
                    @this.set('rentouts.property_id', $(this).val());
                });

                // Re-initialize property TomSelect to support vacant_only filter
                var propTs = document.querySelector('#property_id').tomselect;
                if (propTs) {
                    var originalLoad = propTs.settings.load;
                    propTs.settings.load = function(query, callback) {
                        var url = "{{ route('property::property::list') }}";
                        url += '?query=' + encodeURIComponent(query);
                        var vacantOnly = document.querySelector('#vacant_only');
                        if (vacantOnly && vacantOnly.checked) {
                            url += '&vacant_only=1';
                        }
                        fetch(url).then(response => response.json()).then(json => {
                            callback(json.items);
                        }).catch(() => {
                            callback();
                        });
                    };
                }

                // Customer select
                $('#account_id').on('change', function() {
                    @this.set('rentouts.account_id', $(this).val());
                });

                // Salesman select
                $('#salesman_id').on('change', function() {
                    @this.set('rentouts.salesman_id', $(this).val());
                });

                // Edit customer button
                $(document).on('click', '.edit_customer', function(e) {
                    e.preventDefault();
                    var customer_id = @this.rentouts['account_id'];
                    if (!customer_id) return;
                    Livewire.dispatch("Customer-Page-Update-Component", { id: customer_id });
                });

                // Auto-populate selects on edit
                window.addEventListener('RentOutSelectValues', event => {
                    var data = event.detail[0];
                    if (data.property_group_id) {
                        var groupTs = document.querySelector('#property_group_id').tomselect;
                        if (groupTs && data.group_name) {
                            groupTs.addOption({ id: data.property_group_id, name: data.group_name });
                            groupTs.addItem(data.property_group_id);
                        }
                    }
                    if (data.property_building_id) {
                        var buildingTs = document.querySelector('#property_building_id').tomselect;
                        if (buildingTs && data.building_name) {
                            buildingTs.addOption({ id: data.property_building_id, name: data.building_name });
                            buildingTs.addItem(data.property_building_id);
                        }
                    }
                    if (data.property_type_id) {
                        var typeTs = document.querySelector('#property_type_id').tomselect;
                        if (typeTs && data.type_name) {
                            typeTs.addOption({ id: data.property_type_id, name: data.type_name });
                            typeTs.addItem(data.property_type_id);
                        }
                    }
                    if (data.property_id) {
                        var propTs = document.querySelector('#property_id').tomselect;
                        if (propTs && data.property_name) {
                            propTs.addOption({ id: data.property_id, name: data.property_name });
                            propTs.addItem(data.property_id);
                        }
                    }
                    if (data.account_id) {
                        var custTs = document.querySelector('#account_id').tomselect;
                        if (custTs && data.customer_name) {
                            custTs.addOption({ id: data.account_id, name: data.customer_name });
                            custTs.addItem(data.account_id);
                        }
                    }
                    if (data.salesman_id) {
                        var empTs = document.querySelector('#salesman_id').tomselect;
                        if (empTs && data.salesman_name) {
                            empTs.addOption({ id: data.salesman_id, name: data.salesman_name });
                            empTs.addItem(data.salesman_id);
                        }
                    }
                });

                // Auto-fill group/building/type when property is selected
                window.addEventListener('PropertyAutoFill', event => {
                    var data = event.detail[0];
                    if (data.property_group_id) {
                        var groupTs = document.querySelector('#property_group_id').tomselect;
                        if (groupTs) {
                            groupTs.addOption({ id: data.property_group_id, name: data.group_name });
                            groupTs.setValue(data.property_group_id, true);
                        }
                    }
                    if (data.property_building_id) {
                        var buildingTs = document.querySelector('#property_building_id').tomselect;
                        if (buildingTs) {
                            buildingTs.addOption({ id: data.property_building_id, name: data.building_name });
                            buildingTs.setValue(data.property_building_id, true);
                        }
                    }
                    if (data.property_type_id) {
                        var typeTs = document.querySelector('#property_type_id').tomselect;
                        if (typeTs) {
                            typeTs.addOption({ id: data.property_type_id, name: data.type_name });
                            typeTs.setValue(data.property_type_id, true);
                        }
                    }
                });
            });
        </script>
    @endpush
</div>
