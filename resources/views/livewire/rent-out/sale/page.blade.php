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
                    <i class="demo-psi-home fs-4 me-2 text-primary"></i>
                    <h5 class="mb-0 fw-bold">Property & Customer Details</h5>
                </div>
            </div>
            <div class="card-body py-4">
                <div class="row g-3">
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label fw-semibold small"><i class="demo-psi-map-marker-2 text-primary me-1"></i> Group/Project</label>
                        <select class="form-select select-property_group_id" id="sale_property_group_id">
                            <option value="">Select Group</option>
                            @if(isset($rentouts['property_group_id']) && $rentouts['property_group_id'])
                                @php $group = \App\Models\PropertyGroup::find($rentouts['property_group_id']); @endphp
                                @if($group)
                                    <option value="{{ $group->id }}" selected>{{ $group->name }}</option>
                                @endif
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label fw-semibold small"><i class="demo-psi-building text-success me-1"></i> Building</label>
                        <select class="form-select select-property_building_id" id="sale_property_building_id">
                            <option value="">Select Building</option>
                            @if(isset($rentouts['property_building_id']) && $rentouts['property_building_id'])
                                @php $building = \App\Models\PropertyBuilding::find($rentouts['property_building_id']); @endphp
                                @if($building)
                                    <option value="{{ $building->id }}" selected>{{ $building->name }}</option>
                                @endif
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label fw-semibold small"><i class="demo-psi-home text-info me-1"></i> Type</label>
                        <select class="form-select select-property_type_id" id="sale_property_type_id">
                            <option value="">Select Type</option>
                            @if(isset($rentouts['property_type_id']) && $rentouts['property_type_id'])
                                @php $propType = \App\Models\PropertyType::find($rentouts['property_type_id']); @endphp
                                @if($propType)
                                    <option value="{{ $propType->id }}" selected>{{ $propType->name }}</option>
                                @endif
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label fw-semibold small"><i class="demo-psi-key text-warning me-1"></i> Property No *</label>
                        <select class="form-select select-property_id" id="sale_property_id">
                            <option value="">Select Property</option>
                            @if(isset($rentouts['property_id']) && $rentouts['property_id'])
                                @php $prop = \App\Models\Property::with('building')->find($rentouts['property_id']); @endphp
                                @if($prop)
                                    <option value="{{ $prop->id }}" selected>{{ $prop->number }}{{ $prop->building ? ' - ' . $prop->building->name : '' }}</option>
                                @endif
                            @endif
                        </select>
                    </div>
                    <div class="col-md-6" wire:ignore>
                        <label class="form-label fw-semibold small"><i class="demo-psi-male text-danger me-1"></i> Customer *</label>
                        <select class="form-select select-account_id" id="sale_account_id">
                            <option value="">Select Customer</option>
                            @if(isset($rentouts['account_id']) && $rentouts['account_id'])
                                @php $customer = \App\Models\Account::find($rentouts['account_id']); @endphp
                                @if($customer)
                                    <option value="{{ $customer->id }}" selected>{{ $customer->name }}</option>
                                @endif
                            @endif
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Sale Details --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center">
                    <i class="demo-psi-tag fs-4 me-2 text-success"></i>
                    <h5 class="mb-0 fw-bold">Sale Details</h5>
                </div>
            </div>
            <div class="card-body py-4">
                {{-- Period --}}
                <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                    <i class="demo-psi-calendar-4 text-primary me-2"></i>
                    <h6 class="mb-0 fw-semibold text-muted">Period</h6>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="demo-psi-calendar-4 text-primary me-1"></i> Start Date *</label>
                        <input type="date" class="form-control" wire:model.live="rentouts.start_date">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="demo-psi-calendar-4 text-danger me-1"></i> End Date *</label>
                        <input type="date" class="form-control" wire:model.live="rentouts.end_date">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="demo-psi-clock text-info me-1"></i> Duration</label>
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
                    <i class="demo-psi-coins text-success me-2"></i>
                    <h6 class="mb-0 fw-semibold text-muted">Payment Information</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="demo-psi-receipt-4 text-info me-1"></i> No of Terms</label>
                        <input type="number" class="form-control" wire:model.live="rentouts.no_of_terms">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="demo-psi-repeat-2 text-primary me-1"></i> Payment Frequency</label>
                        <select class="form-select" wire:model="rentouts.payment_frequency">
                            <option value="Monthly">Monthly</option>
                            <option value="Quarterly">Quarterly</option>
                            <option value="Half Yearly">Half Yearly</option>
                            <option value="Yearly">Yearly</option>
                            <option value="One Time">One Time</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="demo-psi-coins text-success me-1"></i> Unit Sale Price</label>
                        <input type="number" class="form-control" wire:model.live="rentouts.rent" step="0.01">
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Additional Details --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center">
                    <i class="demo-psi-gear fs-4 me-2 text-info"></i>
                    <h5 class="mb-0 fw-bold">Additional Details</h5>
                </div>
            </div>
            <div class="card-body py-4">
                <div class="row g-3">
                    <div class="col-md-4" wire:ignore>
                        <label class="form-label fw-semibold small"><i class="demo-psi-male text-primary me-1"></i> Salesman</label>
                        <select class="form-select select-salesman_id" id="sale_salesman_id">
                            <option value="">Select Salesman</option>
                            @if(isset($rentouts['salesman_id']) && $rentouts['salesman_id'])
                                @php $salesman = \App\Models\User::find($rentouts['salesman_id']); @endphp
                                @if($salesman)
                                    <option value="{{ $salesman->id }}" selected>{{ $salesman->name }}</option>
                                @endif
                            @endif
                        </select>
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold small"><i class="demo-psi-speech-bubble-7 text-muted me-1"></i> Remark</label>
                        <textarea class="form-control" wire:model="rentouts.remark" rows="3" placeholder="Add any additional notes or remarks here..."></textarea>
                    </div>
                </div>

                {{-- Policies & Terms --}}
                <div class="d-flex align-items-center mt-4 mb-3 pb-2 border-bottom">
                    <i class="demo-psi-file text-primary me-2"></i>
                    <h6 class="mb-0 fw-semibold text-muted">Policies & Terms</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="demo-psi-file text-primary me-1"></i> Cancellation Policy (English)</label>
                        <input type="text" class="form-control" wire:model="rentouts.cancellation_policy_en" placeholder="Enter cancellation policy in English...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="demo-psi-file text-success me-1"></i> Cancellation Policy (Arabic)</label>
                        <input type="text" class="form-control" wire:model="rentouts.cancellation_policy_ar" dir="rtl" placeholder="...أدخل قاعدة الإلغاء باللغة العربية">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="demo-psi-receipt-4 text-primary me-1"></i> Payment Terms (English)</label>
                        <input type="text" class="form-control" wire:model="rentouts.payment_terms_en" placeholder="Enter payment terms in English...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="demo-psi-receipt-4 text-success me-1"></i> Payment Terms (Arabic)</label>
                        <input type="text" class="form-control" wire:model="rentouts.payment_terms_ar" dir="rtl" placeholder="...أدخل شروط الدفع باللغة العربية">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="demo-psi-receipt-4 text-warning me-1"></i> Payment Terms Extended (English)</label>
                        <input type="text" class="form-control" wire:model="rentouts.payment_terms_extended_en" placeholder="Enter extended payment terms in English...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="demo-psi-receipt-4 text-info me-1"></i> Payment Terms Extended (Arabic)</label>
                        <input type="text" class="form-control" wire:model="rentouts.payment_terms_extended_ar" dir="rtl" placeholder="...أدخل شروط الدفع الممتدة باللغة العربية">
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 4: Down Payment (Sale only) --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center">
                    <i class="demo-psi-wallet-2 fs-4 me-2 text-success"></i>
                    <h5 class="mb-0 fw-bold">Down Payment</h5>
                </div>
            </div>
            <div class="card-body py-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="demo-psi-coins text-success me-1"></i> Amount</label>
                        <input type="number" class="form-control" wire:model="rentouts.down_payment" step="0.01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="demo-psi-wallet-2 text-primary me-1"></i> Payment Mode</label>
                        <select class="form-select" wire:model="rentouts.down_payment_mode">
                            <option value="">Select...</option>
                            @foreach(\App\Enums\RentOut\PaymentMode::cases() as $mode)
                                <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="demo-psi-speech-bubble-7 text-muted me-1"></i> Remarks</label>
                        <input type="text" class="form-control" wire:model="rentouts.down_payment_remarks" placeholder="Add payment details or notes here...">
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 5: Monthly Collection --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center">
                    <i class="demo-psi-calendar-4 fs-4 me-2 text-primary"></i>
                    <h5 class="mb-0 fw-bold">Monthly Collection</h5>
                </div>
            </div>
            <div class="card-body py-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="demo-psi-calendar-4 text-warning me-1"></i> Collection Starting Day</label>
                        <input type="number" class="form-control" wire:model="rentouts.collection_starting_day" min="1" max="28">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="demo-psi-coins text-success me-1"></i> Payment Mode</label>
                        <select class="form-select" wire:model.live="rentouts.collection_payment_mode">
                            <option value="">Select...</option>
                            @foreach(\App\Enums\RentOut\PaymentMode::cases() as $mode)
                                <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if(($rentouts['collection_payment_mode'] ?? '') && $rentouts['collection_payment_mode'] !== 'cash')
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small"><i class="demo-psi-building text-primary me-1"></i> Bank Name</label>
                            <input type="text" class="form-control" wire:model="rentouts.collection_bank_name" placeholder="Enter bank name">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small"><i class="demo-psi-file text-info me-1"></i> Cheque Starting No</label>
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
                    <a href="{{ $type === 'Booking' ? route('property::sale::booking') : route('property::sale::index') }}" class="btn btn-light d-inline-flex align-items-center gap-2">
                        <i class="demo-psi-arrow-left fs-5"></i>
                        <span>Back to List</span>
                    </a>
                    <div class="d-flex gap-2">
                        @if(isset($rentouts['id']) && $type === 'Booking' && ($rentouts['status'] ?? '') === 'booked' && !($rentouts['submitted_by'] ?? null))
                            <button type="button" wire:click="confirm" class="btn btn-primary d-inline-flex align-items-center gap-2">
                                <i class="demo-psi-check fs-5"></i>
                                <span>Confirm</span>
                            </button>
                            <button type="button" wire:click="cancel" wire:confirm="Are you sure you want to cancel this booking?" class="btn btn-danger d-inline-flex align-items-center gap-2">
                                <i class="demo-psi-cross fs-5"></i>
                                <span>Cancel Booking</span>
                            </button>
                        @endif
                        @if(!isset($rentouts['status']) || ($rentouts['status'] ?? '') !== 'cancelled')
                            <button type="submit" class="btn btn-success d-inline-flex align-items-center gap-2 px-4">
                                <i class="demo-psi-save fs-5"></i>
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

        <script type="text/javascript">
            $(document).ready(function() {
                $('#sale_property_group_id').on('change', function() {
                    @this.set('rentouts.property_group_id', $(this).val());
                });
                $('#sale_property_building_id').on('change', function() {
                    @this.set('rentouts.property_building_id', $(this).val());
                });
                $('#sale_property_type_id').on('change', function() {
                    @this.set('rentouts.property_type_id', $(this).val());
                });
                $('#sale_property_id').on('change', function() {
                    @this.set('rentouts.property_id', $(this).val());
                });
                $('#sale_account_id').on('change', function() {
                    @this.set('rentouts.account_id', $(this).val());
                });
                $('#sale_salesman_id').on('change', function() {
                    @this.set('rentouts.salesman_id', $(this).val());
                });
            });
        </script>
    @endpush
</div>
