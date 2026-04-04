<?php

namespace App\Livewire\Maintenance;

use App\Actions\Maintenance\CreateAction;
use App\Actions\Maintenance\UpdateAction;
use App\Enums\Maintenance\MaintenancePriority;
use App\Enums\Maintenance\MaintenanceSegment;
use App\Enums\Maintenance\MaintenanceStatus;
use App\Models\Maintenance;
use App\Models\RentOut;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    public $maintenance_id;

    public $formData = [];

    public $complaints = [];

    public $preFilledDropDowns = [];

    // Property info sidebar
    public $propertyInfo = [];

    // Activity log sidebar
    public $activityLog = [];

    // New complaint row
    public $newComplaint = [
        'complaint_category_id' => '',
        'complaint_id' => '',
        'technician_id' => '',
    ];

    // Assign technician modal
    public $assignModal = [
        'show' => false,
        'index' => null,
        'technician_id' => '',
    ];

    public function mount($id = null)
    {
        $this->maintenance_id = $id;
        if ($this->maintenance_id) {
            $this->loadData();
        } else {
            $this->formData = [
                'branch_id' => session('branch_id'),
                'property_id' => '',
                'property_group_id' => '',
                'property_building_id' => '',
                'property_type_id' => '',
                'rent_out_id' => '',
                'account_id' => '',
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i'),
                'priority' => MaintenancePriority::Medium->value,
                'segment' => '',
                'contact_no' => '',
                'remark' => '',
                'company_remark' => '',
                'status' => MaintenanceStatus::Pending->value,
            ];
            $this->preFilledDropDowns = [];
            $this->complaints = [];
            $this->propertyInfo = [];
            $this->activityLog = [];
        }
    }

    public function loadData()
    {
        $maintenance = Maintenance::with([
            'property.building.group', 'property.type',
            'rentOut.customer', 'customer', 'creator', 'updater', 'completedBy',
            'maintenanceComplaints.complaint.category',
            'maintenanceComplaints.technician',
        ])->find($this->maintenance_id);

        if (! $maintenance) {
            $this->dispatch('error', ['message' => 'Maintenance request not found.']);

            return;
        }

        $this->formData = [
            'property_id' => $maintenance->property_id,
            'property_group_id' => $maintenance->property_group_id,
            'property_building_id' => $maintenance->property_building_id,
            'property_type_id' => $maintenance->property_type_id,
            'rent_out_id' => $maintenance->rent_out_id,
            'account_id' => $maintenance->account_id,
            'date' => $maintenance->date?->format('Y-m-d'),
            'time' => $maintenance->time,
            'priority' => $maintenance->priority?->value,
            'segment' => $maintenance->segment?->value,
            'contact_no' => $maintenance->contact_no,
            'remark' => $maintenance->remark,
            'company_remark' => $maintenance->company_remark,
            'status' => $maintenance->status?->value,
        ];

        $property_name = $maintenance->property ? $maintenance->property->number.($maintenance->property->building ? ' - '.$maintenance->property->building->name : '') : '';

        $this->preFilledDropDowns = [
            'group' => $maintenance->property_group_id ? [$maintenance->property_group_id => $maintenance->property?->building?->group?->name] : [],
            'building' => $maintenance->property_building_id ? [$maintenance->property_building_id => $maintenance->property?->building?->name] : [],
            'type' => $maintenance->property_type_id ? [$maintenance->property_type_id => $maintenance->property?->type?->name] : [],
            'property' => $maintenance->property_id ? [$maintenance->property_id => $property_name] : [],
        ];

        $this->dispatch('MaintenanceSelectValues', [
            'property_group_id' => $maintenance->property_group_id,
            'group_name' => $maintenance->property?->building?->group?->name,
            'property_building_id' => $maintenance->property_building_id,
            'building_name' => $maintenance->property?->building?->name,
            'property_type_id' => $maintenance->property_type_id,
            'type_name' => $maintenance->property?->type?->name,
            'property_id' => $maintenance->property_id,
            'property_name' => $property_name,
        ]);

        // Property info sidebar
        $rentOut = $maintenance->rentOut;
        $this->propertyInfo = [
            'rentout_id' => $maintenance->rent_out_id,
            'status' => $maintenance->status?->label() ?? 'Pending',
            'status_color' => $maintenance->status?->color() ?? 'warning',
            'property_status' => $maintenance->property?->status?->label() ?? '',
            'customer_name' => $maintenance->customer?->name ?? ($rentOut?->customer?->name ?? ''),
            'customer_mobile' => $maintenance->contact_no ?? '',
            'agreement_start_date' => $rentOut?->start_date?->format('d M Y') ?? '',
        ];

        // Activity log sidebar
        $this->activityLog = [
            'created_by' => $maintenance->creator?->name ?? '',
            'created_at' => $maintenance->created_at?->format('d M Y H:i') ?? '',
            'updated_by' => $maintenance->updater?->name ?? '',
            'updated_at' => $maintenance->updated_at?->format('d M Y H:i') ?? '',
            'completed_by' => $maintenance->completedBy?->name ?? '',
            'completed_at' => $maintenance->completed_at?->format('d M Y H:i') ?? '',
        ];

        $this->complaints = $maintenance->maintenanceComplaints->map(function ($mc) {
            return [
                'id' => $mc->id,
                'complaint_id' => $mc->complaint_id,
                'complaint_name' => $mc->complaint?->name ?? '',
                'category_name' => $mc->complaint?->category?->name ?? '',
                'technician_id' => $mc->technician_id,
                'technician_name' => $mc->technician?->name ?? '',
                'technician_remark' => $mc->technician_remark ?? '',
                'status' => $mc->status?->value ?? 'pending',
                'status_label' => $mc->status?->label() ?? 'Pending',
                'status_color' => $mc->status?->color() ?? 'warning',
            ];
        })->toArray();
    }

    public function updatedFormDataPropertyId($value)
    {
        if ($value) {
            // Load property with relationships to auto-fill group/building/type
            $property = \App\Models\Property::with(['building.group', 'type'])->find($value);
            if ($property) {
                $this->formData['property_group_id'] = $property->property_group_id;
                $this->formData['property_building_id'] = $property->property_building_id;
                $this->formData['property_type_id'] = $property->property_type_id;

                // Update property status in sidebar
                $this->propertyInfo['property_status'] = $property->status?->label() ?? '';

                // Dispatch event to auto-fill Group/Building/Type TomSelects in JS
                $this->dispatch('PropertyDetailsLoaded', [
                    'property_group_id' => $property->property_group_id,
                    'group_name' => $property->building?->group?->name ?? '',
                    'property_building_id' => $property->property_building_id,
                    'building_name' => $property->building?->name ?? '',
                    'property_type_id' => $property->property_type_id,
                    'type_name' => $property->type?->name ?? '',
                ]);
            }

            // Auto-fill rentout and customer from occupied rentout for this property
            $rentOut = RentOut::where('property_id', $value)
                ->where('status', 'occupied')
                ->with('customer')
                ->latest('start_date')
                ->first();
            if ($rentOut) {
                $this->formData['rent_out_id'] = $rentOut->id;
                $this->formData['account_id'] = $rentOut->account_id;
                $this->formData['contact_no'] = $rentOut->customer?->mobile ?? '';
                $this->propertyInfo['rentout_id'] = $rentOut->id;
                $this->propertyInfo['customer_name'] = $rentOut->customer?->name ?? '';
                $this->propertyInfo['customer_mobile'] = $rentOut->customer?->mobile ?? '';
                $this->propertyInfo['agreement_start_date'] = $rentOut->start_date?->format('d M Y') ?? '';
            } else {
                // Clear if no occupied rentout
                $this->formData['rent_out_id'] = '';
                $this->formData['account_id'] = '';
                $this->formData['contact_no'] = '';
                $this->propertyInfo['rentout_id'] = '';
                $this->propertyInfo['customer_name'] = '';
                $this->propertyInfo['customer_mobile'] = '';
                $this->propertyInfo['agreement_start_date'] = '';
            }
        }
    }

    public function addComplaint()
    {
        if (empty($this->newComplaint['complaint_id'])) {
            $this->dispatch('error', ['message' => 'Please select a complaint.']);

            return;
        }

        // Lookup complaint + category names from DB
        $complaint = \App\Models\Complaint::with('category')->find($this->newComplaint['complaint_id']);
        $complaintName = $complaint?->name ?? '';
        $categoryName = $complaint?->category?->name ?? '';

        // Lookup technician name from DB
        $technicianName = '';
        $technicianId = $this->newComplaint['technician_id'] ?? '';
        if ($technicianId) {
            $technician = \App\Models\User::find($technicianId);
            $technicianName = $technician?->name ?? '';
        }

        $this->complaints[] = [
            'id' => null,
            'complaint_id' => $this->newComplaint['complaint_id'],
            'complaint_name' => $complaintName,
            'category_name' => $categoryName,
            'technician_id' => $technicianId,
            'technician_name' => $technicianName,
            'technician_remark' => '',
            'status' => 'pending',
            'status_label' => 'Pending',
            'status_color' => 'warning',
        ];

        $this->newComplaint = [
            'complaint_category_id' => '',
            'complaint_id' => '',
            'technician_id' => '',
        ];

        $this->dispatch('ClearComplaintRow');
    }

    public function removeComplaint($index)
    {
        unset($this->complaints[$index]);
        $this->complaints = array_values($this->complaints);
    }

    public function openAssignModal($index)
    {
        $this->assignModal = [
            'show' => true,
            'index' => $index,
            'technician_id' => $this->complaints[$index]['technician_id'] ?? '',
        ];

        $this->dispatch('OpenAssignTechnicianModal', [
            'technician_id' => $this->complaints[$index]['technician_id'] ?? '',
            'technician_name' => $this->complaints[$index]['technician_name'] ?? '',
        ]);
    }

    public function closeAssignModal()
    {
        $this->assignModal = [
            'show' => false,
            'index' => null,
            'technician_id' => '',
        ];
    }

    public function saveAssignTechnician()
    {
        $index = $this->assignModal['index'];
        if ($index === null || ! isset($this->complaints[$index])) {
            $this->dispatch('error', ['message' => 'Invalid complaint selected.']);

            return;
        }

        $technicianId = $this->assignModal['technician_id'];
        $technicianName = '';
        if ($technicianId) {
            $technician = \App\Models\User::find($technicianId);
            $technicianName = $technician?->name ?? '';
        }

        $this->complaints[$index]['technician_id'] = $technicianId;
        $this->complaints[$index]['technician_name'] = $technicianName;

        $this->closeAssignModal();
        $this->dispatch('CloseAssignTechnicianModal');
        $this->dispatch('success', ['message' => 'Technician assigned successfully.']);
    }

    public function save()
    {
        try {
            DB::beginTransaction();
            $this->formData['created_by'] = auth()->id();
            $this->formData['updated_by'] = auth()->id();

            $complaintData = collect($this->complaints)
                ->filter(fn ($c) => ! empty($c['complaint_id']))
                ->map(fn ($c) => [
                    'id' => $c['id'] ?? null,
                    'complaint_id' => $c['complaint_id'],
                    'technician_id' => $c['technician_id'] ?? null,
                ])
                ->values()
                ->toArray();

            if (! $this->maintenance_id) {
                $response = (new CreateAction())->execute($this->formData, $complaintData);
            } else {
                $response = (new UpdateAction())->execute($this->formData, $this->maintenance_id, $complaintData);
            }

            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);

            if (! $this->maintenance_id) {
                $this->maintenance_id = $response['data']->id;
            }

            return redirect()->route('property::maintenance::index');
        } catch (\Throwable $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $priorities = MaintenancePriority::cases();
        $segments = MaintenanceSegment::cases();
        $statuses = MaintenanceStatus::cases();

        return view('livewire.maintenance.page', [
            'priorities' => $priorities,
            'segments' => $segments,
            'statuses' => $statuses,
        ]);
    }
}
