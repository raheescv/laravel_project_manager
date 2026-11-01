<?php

namespace App\Livewire\Property\ServiceRequest;

use App\Actions\Property\ServiceRequest\CreateAction;
use App\Actions\Property\ServiceRequest\StatusChangeAction;
use App\Actions\Property\ServiceRequest\UpdateAction;
use App\Enums\Property\PropertyStatus;
use App\Enums\Property\ServiceRequestStatus;
use App\Enums\Property\ServiceRequestType;
use App\Models\Configuration;
use App\Models\Property;
use App\Models\PropertyServiceRequest;
use App\Models\RentOut;
use App\Models\Utility;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    public $request_id;

    public $formData = [];

    public $status = 'requested';

    public $notes = [];

    public $note = '';

    public $preFilledDropDowns = [];

    public $propertyInfo = [];

    public $workflow = [];

    public $history = [];

    public function mount($id = null)
    {
        $this->request_id = $id;
        $this->note = '';
        if ($this->request_id) {
            $this->loadData();

            return;
        }

        $this->formData = [
            'branch_id' => session('branch_id'),
            'property_id' => '',
            'property_group_id' => '',
            'property_building_id' => '',
            'property_type_id' => '',
            'utility_id' => Utility::orderBy('id')->value('id'),
            'type' => ServiceRequestType::Connect->value,
            'date' => now()->format('Y-m-d'),
            'amount' => 0,
            'remarks' => '',
        ];
        $this->status = ServiceRequestStatus::Requested->value;
        $this->notes = [];
        $this->preFilledDropDowns = [];
        $this->propertyInfo = [];
        $this->workflow = ['requested_by' => auth()->user()?->name];
        $this->history = [];
    }

    public function loadData()
    {
        $model = PropertyServiceRequest::with(['property.building.group', 'property.type', 'requestedBy', 'approvedBy', 'financialApprovedBy', 'completedBy'])
            ->find($this->request_id);

        if (! $model) {
            $this->dispatch('error', ['message' => 'Service Request not found.']);

            return;
        }

        $this->formData = [
            'property_id' => $model->property_id,
            'property_group_id' => $model->property_group_id,
            'property_building_id' => $model->property_building_id,
            'property_type_id' => $model->property_type_id,
            'utility_id' => $model->utility_id,
            'type' => $model->type->value,
            'date' => $model->date->format('Y-m-d'),
            'amount' => $model->amount,
            'remarks' => $model->remarks,
        ];
        $this->status = $model->status->value;
        $this->notes = $model->notes ?? [];

        $propertyName = $model->property ? $model->property->number.($model->property->building ? ' - '.$model->property->building->name : '') : '';
        $this->preFilledDropDowns = [
            'group' => $model->property_group_id ? [$model->property_group_id => $model->property?->building?->group?->name] : [],
            'building' => $model->property_building_id ? [$model->property_building_id => $model->property?->building?->name] : [],
            'type' => $model->property_type_id ? [$model->property_type_id => $model->property?->type?->name] : [],
            'property' => $model->property_id ? [$model->property_id => $propertyName] : [],
        ];

        $this->dispatch('ServiceRequestSelectValues', [
            'property_group_id' => $model->property_group_id,
            'group_name' => $model->property?->building?->group?->name,
            'property_building_id' => $model->property_building_id,
            'building_name' => $model->property?->building?->name,
            'property_type_id' => $model->property_type_id,
            'type_name' => $model->property?->type?->name,
            'property_id' => $model->property_id,
            'property_name' => $propertyName,
        ]);

        $this->workflow = [
            'requested_by' => $model->requestedBy?->name,
            'requested_at' => $model->requested_at?->format('d M Y'),
            'approved_by' => $model->approvedBy?->name,
            'approved_at' => $model->approved_at?->format('d M Y'),
            'financial_approved_by' => $model->financialApprovedBy?->name,
            'financial_approved_at' => $model->financial_approved_at?->format('d M Y'),
            'completed_by' => $model->completedBy?->name,
            'completed_at' => $model->completed_at?->format('d M Y'),
        ];

        $this->loadPropertyInfo($model->property, $model->rent_out_service_status);
    }

    public function updatedFormDataPropertyId($value)
    {
        $property = $value ? Property::with(['building.group', 'type'])->find($value) : null;
        if ($property) {
            $this->formData['property_group_id'] = $property->property_group_id;
            $this->formData['property_building_id'] = $property->property_building_id;
            $this->formData['property_type_id'] = $property->property_type_id;

            $this->dispatch('PropertyDetailsLoaded', [
                'property_group_id' => $property->property_group_id,
                'group_name' => $property->building?->group->name ?? '',
                'property_building_id' => $property->property_building_id,
                'building_name' => $property->building->name ?? '',
                'property_type_id' => $property->property_type_id,
                'type_name' => $property->type->name ?? '',
            ]);
        }
        $this->loadPropertyInfo($property);
        $this->defaultAmount();
    }

    public function updatedFormDataType()
    {
        $this->defaultAmount();
    }

    public function updatedFormDataUtilityId()
    {
        $this->loadPropertyInfo(Property::find($this->formData['property_id'] ?? null));
    }

    /**
     * A connect on an occupied unit starts at the tenant's configured charge;
     * anything else starts at zero.
     */
    private function defaultAmount(): void
    {
        $isOccupied = ($this->propertyInfo['property_status_value'] ?? '') === PropertyStatus::Occupied->value;
        $isConnect = ($this->formData['type'] ?? '') === ServiceRequestType::Connect->value;

        $this->formData['amount'] = $isOccupied && $isConnect
            ? (float) Configuration::where('key', 'service_request_connect_amount')->value('value')
            : 0;
    }

    private function loadPropertyInfo(?Property $property, ?string $storedServiceStatus = null): void
    {
        $this->propertyInfo = [];
        $this->history = [];
        if (! $property) {
            return;
        }

        $rentOut = RentOut::where('property_id', $property->id)->occupied()->with('customer')->latest('start_date')->first();
        $this->propertyInfo = [
            'property_status' => $property->status?->label() ?? '',
            'property_status_value' => $property->status?->value ?? '',
            'rent_out_id' => $rentOut?->id,
            'customer_name' => $rentOut?->customer?->name ?? '',
            'service_status' => $storedServiceStatus ?? PropertyServiceRequest::rentOutServiceStatus($rentOut, Utility::find($this->formData['utility_id'] ?? null)),
        ];

        $this->history = PropertyServiceRequest::connectionPeriods()
            ->with('utility')
            ->where('property_service_requests.property_id', $property->id)
            ->orderBy('property_service_requests.date')
            ->get()
            ->map(fn ($row) => [
                'utility' => $row->utility?->name,
                'connect_date' => $row->date?->format('d M Y'),
                'disconnect_date' => $row->disconnect_date ? date('d M Y', strtotime($row->disconnect_date)) : '',
            ])
            ->toArray();
    }

    public function addNote()
    {
        if (! trim($this->note)) {
            return;
        }
        $this->notes[] = [
            'note' => trim($this->note),
            'created_by' => auth()->id(),
            'creator' => auth()->user()?->name,
            'created_at' => now()->format('Y-m-d H:i:s'),
        ];
        $this->note = '';
    }

    public function deleteNote($index)
    {
        abort_unless(auth()->user()?->can('property service request.delete note'), 403);
        unset($this->notes[$index]);
        $this->notes = array_values($this->notes);
    }

    public function save()
    {
        abort_unless(auth()->user()?->can($this->request_id ? 'property service request.edit' : 'property service request.create'), 403);
        try {
            DB::beginTransaction();
            $data = $this->formData;
            $data['notes'] = array_values($this->notes);
            if ($this->request_id && ! auth()->user()->can('property service request.edit date')) {
                $data['date'] = PropertyServiceRequest::find($this->request_id)?->date?->format('Y-m-d');
            }

            if ($this->request_id) {
                $response = (new UpdateAction())->execute($data, $this->request_id, auth()->id());
            } else {
                $response = (new CreateAction())->execute($data, auth()->id());
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();

            if (! $this->request_id) {
                session()->flash('success', $response['message']);

                return $this->redirect(route('property::service_request::edit', $response['data']->id));
            }
            $this->loadData();
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function changeStatus()
    {
        $next = ServiceRequestStatus::from($this->status)->next();
        abort_unless($next && auth()->user()?->can($next->permission()), 403);
        try {
            DB::beginTransaction();
            $response = (new StatusChangeAction())->execute($this->request_id, $next, auth()->id());
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();
            $this->loadData();
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.property.service-request.page', [
            'utilities' => Utility::orderBy('name')->pluck('name', 'id'),
            'types' => ServiceRequestType::cases(),
            'currentStatus' => ServiceRequestStatus::from($this->status),
        ]);
    }
}
