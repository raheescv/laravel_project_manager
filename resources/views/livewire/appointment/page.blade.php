<div>
    <style>
        .appointment-header {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            padding: 1.75rem;
            border-radius: 0.5rem 0.5rem 0 0;
            position: relative;
            overflow: hidden;
        }

        .appointment-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1));
            pointer-events: none;
        }

        .header-icon-wrapper {
            background: rgba(255, 255, 255, 0.2);
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .header-icon-wrapper:hover {
            transform: translateY(-2px) rotate(10deg);
            background: rgba(255, 255, 255, 0.3);
        }

        .appointment-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #fff;
            margin: 0;
            line-height: 1.4;
        }

        .appointment-subtitle {
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.875rem;
            margin: 0;
        }

        .header-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-left: auto;
            /* Push to right */
            padding-left: 1rem;
            border-left: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-content {
            display: flex;
            flex-direction: row;
            align-items: center;
            width: 100%;
            justify-content: space-between;
            gap: 1rem;
        }

        .header-main {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .edit-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .edit-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-close-custom {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-close-custom:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: rotate(90deg);
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            margin-left: 1rem;
        }

        .color-picker-section {
            margin-top: 1rem;
            padding: 1rem 0;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .color-options {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .color-option {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        .color-option:hover {
            transform: scale(1.1);
        }

        .color-option.active {
            border-color: #4f46e5;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
        }
    </style>

    <form wire:submit="save">
        <div class="modal-header appointment-header">
            <div class="header-content">
                <div class="header-main">
                    <div class="header-icon-wrapper">
                        <i class="demo-psi-calendar-4 fs-2 text-white"></i>
                    </div>
                    <div>
                        <h5 class="appointment-title">
                            {{ isset($appointments['id']) ? ($editMode ? 'Edit' : 'View') : 'New' }} Appointment
                            @if (isset($appointments['id']))
                                <span class="status-badge">#{{ $appointments['id'] }}</span>
                            @endif
                        </h5>
                        <p class="appointment-subtitle">
                            {{ isset($appointments['id']) ? 'Modify existing appointment details' : 'Schedule a new appointment' }}
                            @if (isset($appointments['id']))
                                <span class="ms-2">
                                    <select class="form-select form-select-sm d-inline-block status-select" style="width: auto; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: #fff; font-size: 0.75rem; padding: 0.25rem 0.75rem; border-radius: 1rem; cursor: pointer;">
                                        @foreach (appointmentStatuses() as $value => $label)
                                            <option value="{{ $value }}" style="color: #000;" {{ ($appointments['status'] ?? 'pending') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="header-actions">
                    @if (isset($appointments['id']) && $appointments['status'] == 'pending')
                        <button type="button" class="edit-btn" wire:click="toggleEditMode">
                            @if (!$editMode)
                                <i class="demo-psi-pen-5 fs-6"></i>
                                <span>Edit</span>
                            @else
                                <i class="fa fa-eye"></i>
                                <span>View</span>
                            @endif
                        </button>
                    @endif
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                        <i class="demo-psi-cross fs-5 text-white"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="modal-body">
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger mb-3">
                    <ul class="mb-0">
                        @foreach ($this->getErrorBag()->toArray() as $value)
                            <li>{{ $value[0] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label text-md fw-semibold mb-2">Customer <span class="text-danger">*</span></label>
                        <div wire:ignore>
                            {{ html()->select('account_id', [])->value($appointments['account_id'] ?? '')->class('select-customer_id')->id('account_id')->placeholder('Select Customer')->attribute('wire:model.live.blur', 'appointments.account_id') }}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label text-md fw-semibold mb-2">Start Time <span class="text-danger">*</span></label>
                        @if ($editMode)
                            {{ html()->datetime('start_time')->value('')->class('form-control shadow-none')->attribute('wire:model.live', 'appointments.start_time') }}
                        @else
                            <div class="p-3 bg-light rounded">
                                <div class="d-flex align-items-center">
                                    <i class="demo-psi-clock fs-4 me-2 text-primary"></i>
                                    <strong>{{ systemDateTime($appointments['start_time']) }}</strong>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label text-md fw-semibold mb-2">End Time <span class="text-danger">*</span></label>
                        @if ($editMode)
                            {{ html()->datetime('end_time')->value('')->class('form-control shadow-none')->attribute('wire:model.live', 'appointments.end_time') }}
                        @else
                            <div class="p-3 bg-light rounded">
                                <div class="d-flex align-items-center">
                                    <i class="demo-psi-clock fs-4 me-2 text-primary"></i>
                                    <strong>{{ systemDateTime($appointments['end_time']) }}</strong>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="color-picker-section">
                <label class="form-label text-md fw-semibold d-flex flex-wrap justify-content-center">Appointment Color</label>
                <div class="color-options d-flex flex-wrap justify-content-center">
                    <div class="color-option @if ($appointments['color'] === '#3788d8') active @endif" style="background-color: #3788d8" wire:click="$set('appointments.color', '#3788d8')"></div>
                    <div class="color-option @if ($appointments['color'] === '#28a745') active @endif" style="background-color: #28a745" wire:click="$set('appointments.color', '#28a745')"></div>
                    <div class="color-option @if ($appointments['color'] === '#dc3545') active @endif" style="background-color: #dc3545" wire:click="$set('appointments.color', '#dc3545')"></div>
                    <div class="color-option @if ($appointments['color'] === '#ffc107') active @endif" style="background-color: #ffc107" wire:click="$set('appointments.color', '#ffc107')"></div>
                    <div class="color-option @if ($appointments['color'] === '#6f42c1') active @endif" style="background-color: #6f42c1" wire:click="$set('appointments.color', '#6f42c1')"></div>
                    <div class="color-option @if ($appointments['color'] === '#fd7e14') active @endif" style="background-color: #fd7e14" wire:click="$set('appointments.color', '#fd7e14')"></div>
                    <div class="color-option @if ($appointments['color'] === '#20c997') active @endif" style="background-color: #20c997" wire:click="$set('appointments.color', '#20c997')"></div>
                    <div class="color-option @if ($appointments['color'] === '#e83e8c') active @endif" style="background-color: #e83e8c" wire:click="$set('appointments.color', '#e83e8c')"></div>
                </div>
            </div>

            <div class="card border mb-1">
                <div class="card-body p-3">
                    <table class="table table-sm table-bordered table-striped mb-0">
                        <thead>
                            <tr class="bg-light">
                                <th width='30%'>Employee</th>
                                <th width='60%'>Service</th>
                                @if ($editMode)
                                    <th class="text-center">Action</th>
                                @endif
                            </tr>
                            <tr @if (!$editMode) hidden @endif>
                                <th wire:ignore>
                                    {{ html()->select('employee_id', [])->value('')->class('select-employee_id-list shadow-none')->id('modal_employee_id')->placeholder('Select Employee') }}
                                </th>
                                <th wire:ignore>
                                    {{ html()->select('service_id', [])->value('')->class('select-product_id-list shadow-none')->attribute('type', 'service')->id('modal_service_id')->placeholder('Select Service') }}
                                </th>
                                <th class="text-center">
                                    <button type="button" class="btn btn-sm btn-primary" wire:click="addItem">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $key => $value)
                                <tr>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            <i class="demo-psi-user fs-5 me-2 text-primary"></i>
                                            {{ $value['employee'] }}
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            <i class="demo-psi-gear fs-5 me-2 text-primary"></i>
                                            {{ $value['service'] }}
                                        </div>
                                    </td>
                                    @if ($editMode)
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-danger" wire:click="removeItem('{{ $key }}')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label text-md fw-semibold mb-2">Notes</label>
                @if ($editMode)
                    {{ html()->textarea('notes', [])->value('')->class('form-control shadow-none')->rows(3)->attribute('wire:model.live', 'appointments.notes')->placeholder('Enter any additional notes...') }}
                @else
                    <div class="p-3 bg-light rounded">
                        <div class="d-flex">
                            <i class="demo-psi-notepad fs-4 me-2 text-primary"></i>
                            <div class="flex-grow-1">
                                @if ($appointments['notes'])
                                    {{ $appointments['notes'] }}
                                @else
                                    <span class="text-muted">No notes added</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="modal-footer bg-light">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            @if ($editMode)
                <button type="button" wire:click="save(1)" class="btn btn-success">Save & Add New</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            @else
                @if (isset($appointments['sale_id']))
                    <a href="{{ route('sale::view', $appointments['sale_id']) }}" class="btn btn-success">View Sale</a>
                @else
                    <button type="button" wire:click="checkout" class="btn btn-success">Checkout</button>
                @endif
            @endif
        </div>
    </form>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#account_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('appointments.account_id', value);
                    document.querySelector('#modal_employee_id').tomselect.open();
                });
                $('#modal_service_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('item.service_id', value);
                });
                $('#modal_employee_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('item.employee_id', value);
                    document.querySelector('#modal_service_id').tomselect.open();
                });

                // Handle status change
                $(document).on('change', '.status-select', function(e) {
                    const value = $(this).val();
                    if (value) {
                        @this.call('updateStatus', value);
                    }
                });

                window.addEventListener('AddToCustomerSelectBox', event => {
                    var data = event.detail[0];
                    var tomSelectInstance = document.querySelector('#account_id').tomselect;
                    if (data['name']) {
                        preselectedData = {
                            id: data['id'],
                            name: data['name'],
                            mobile: data['mobile'],
                        };
                        tomSelectInstance.addOption(preselectedData);
                    }
                    tomSelectInstance.addItem(data['id']);
                    @this.set('appointments.account_id', data['id']);
                });
                window.addEventListener('SelectDropDownValues', event => {
                    var data = event.detail[0];
                    @this.set('appointments.account_id', data.account_id);

                    var tomSelectInstance = document.querySelector('#account_id').tomselect;
                    if (data.account_id) {
                        preselectedData = {
                            id: data.account_id,
                            name: data.account_name,
                        };
                        tomSelectInstance.addOption(preselectedData);
                        tomSelectInstance.addItem(preselectedData.id);
                    } else {
                        tomSelectInstance.clear();
                    }
                    var tomSelectInstance = document.querySelector('#modal_service_id').tomselect;
                    tomSelectInstance.clear();
                    var tomSelectInstanceEmployee = document.querySelector('#modal_employee_id').tomselect;
                    tomSelectInstanceEmployee.clear();
                });
            });
        </script>
    @endpush
</div>
