<?php

namespace App\Livewire\Property\PropertyLead;

use App\Actions\Property\PropertyLead\CreateAction;
use App\Actions\Property\PropertyLead\TransferAction;
use App\Actions\Property\PropertyLead\UpdateAction;
use App\Models\Country;
use App\Models\PropertyLead;
use App\Support\LeadAuditTrail;
use App\Support\LeadPipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    public $lead_id;

    public $formData = [];

    public $note = '';

    public $noteDate;

    public $notes = [];

    /** Labels for the remote TomSelects, keyed by id, so a saved value renders its name. */
    public $groups = [];

    public $propertyTypes = [];

    public function mount($lead_id = null): void
    {
        $this->lead_id = $lead_id;
        $this->noteDate = now()->format('Y-m-d');

        if ($this->lead_id) {
            $lead = PropertyLead::with(['country', 'group', 'assignee', 'propertyType'])->findOrFail($this->lead_id);
            $this->formData = $lead->toArray();

            // Stored statuses drift ("Low Budget ", "Dead lead"); show the real one when it maps.
            $canonical = LeadPipeline::canonical($lead->status);
            if ($canonical !== LeadPipeline::UNMAPPED) {
                $this->formData['status'] = $canonical;
            }

            if ($lead->group) {
                $this->groups[$lead->property_group_id] = $lead->group->name;
            }
            if ($lead->propertyType) {
                $this->propertyTypes[$lead->property_type_id] = $lead->propertyType->name;
            }

            // Ensure date/time fields are in HTML5 input compatible format
            $this->formData['assign_date'] = $lead->assign_date?->format('Y-m-d');
            $this->formData['meeting_date'] = $lead->meeting_date?->format('Y-m-d');
            $this->formData['meeting_time'] = $lead->meeting_time
                ? \Carbon\Carbon::parse($lead->meeting_time)->format('H:i')
                : null;

            $this->notes = is_array($lead->remarks) ? $lead->remarks : (json_decode($lead->remarks ?? '[]', true) ?: []);
        } else {
            $this->formData = [
                'name' => 'New Lead - '.((PropertyLead::withTrashed()->count() ?? 0) + 1),
                'mobile' => '',
                'branch_id' => session('branch_id'),
                'email' => '',
                'company_name' => '',
                'company_contact_person' => '',
                'company_contact_no' => '',
                'property_group_id' => '',
                'property_type_id' => '',
                'rental_type' => '',
                'budget_min' => null,
                'budget_max' => null,
                'assigned_to' => Auth::id(),
                'assign_date' => now()->format('Y-m-d'),
                'source' => 'Outdoor Marketing',
                'sub_source' => '',
                'type' => 'Sales',
                'status' => 'New Lead',
                'sub_status' => '',
                'location' => null,
                'meeting_date' => null,
                'meeting_time' => null,
                // Most leads are local, so new leads start as Qatari.
                'country_id' => Country::where('code', 'QA')->value('id'),
                'nationality' => null,
            ];
            $this->notes = [];
        }
    }

    public function updatedFormData($value, $key): void
    {
        // A sub option only means something under the parent it was picked for.
        if ($key === 'source') {
            $this->formData['sub_source'] = '';
        }
        if ($key === 'status') {
            $this->formData['sub_status'] = '';
        }
        // Rental type only applies to rent out leads.
        if ($key === 'type' && $value !== 'Rentout') {
            $this->formData['rental_type'] = '';
        }
    }

    public function addNote(): void
    {
        $note = trim((string) $this->note);
        if ($note === '') {
            return;
        }
        $this->notes[] = [
            'date' => $this->noteDate ?: now()->format('Y-m-d'),
            'note' => $note,
            'user' => Auth::user()?->name,
            'created_at' => now()->toDateTimeString(),
        ];
        $this->note = '';
    }

    public function removeNote($key): void
    {
        unset($this->notes[$key]);
        $this->notes = array_values($this->notes);
    }

    public function rules(): array
    {
        return [
            // Same basics as the accounts lead form: a lead needs a name, a way to reach them
            // (mobile or email) and a nationality.
            'formData.name' => 'required|string|max:255',
            'formData.mobile' => ['nullable', 'required_without:formData.email', 'regex:/^[0-9]{6,15}$/'],
            'formData.email' => ['nullable', 'required_without:formData.mobile', 'email', 'max:255'],
            'formData.country_id' => 'required',
            'formData.type' => ['required', 'in:'.implode(',', array_keys(leadTypes()))],
            'formData.source' => 'required|string',
            'formData.sub_source' => 'nullable|string|max:255',
            'formData.status' => 'nullable|string|max:30',
            'formData.sub_status' => 'nullable|string|max:255',
            'formData.rental_type' => ['nullable', 'in:'.implode(',', array_keys(leadRentalTypes()))],
            'formData.budget_min' => 'nullable|numeric|min:0',
            'formData.budget_max' => ['nullable', 'numeric', 'min:0', ...(is_numeric($this->formData['budget_min'] ?? null) ? ['gte:formData.budget_min'] : [])],
            'formData.location' => 'required_if:formData.status,Visit Scheduled',
        ];
    }

    protected $messages = [
        'formData.name.required' => 'The name field is required',
        'formData.mobile.required_without' => 'The mobile field is required',
        'formData.mobile.regex' => 'The mobile field must be between 6-15 digits',
        'formData.email.required_without' => 'The email field is required',
        'formData.email.email' => 'The email must be a valid email address',
        'formData.country_id.required' => 'The nationality field is required',
        'formData.type.required' => 'The type field is required',
        'formData.source.required' => 'The source field is required',
        'formData.budget_max.gte' => 'Budget max must be at least the budget min',
        'formData.location.required_if' => 'The location field is required',
    ];

    public function save()
    {
        abort_unless(auth()->user()?->can($this->lead_id ? 'property lead.edit' : 'property lead.create'), 403);
        foreach (['name', 'mobile', 'email'] as $field) {
            $this->formData[$field] = trim((string) ($this->formData[$field] ?? ''));
        }
        $this->validate();

        try {
            DB::beginTransaction();

            $payload = $this->formData;
            $payload['remarks'] = $this->notes;
            if (($payload['type'] ?? null) !== 'Rentout') {
                $payload['rental_type'] = null;
            }
            foreach (['property_group_id', 'property_type_id', 'rental_type', 'budget_min', 'budget_max'] as $nullable) {
                $payload[$nullable] = blank($payload[$nullable] ?? null) ? null : $payload[$nullable];
            }

            if (! $this->lead_id) {
                $response = (new CreateAction())->execute($payload, Auth::id());
            } else {
                $payload['id'] = $this->lead_id;
                $response = (new UpdateAction())->execute($payload, $this->lead_id, Auth::id());
            }

            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            DB::commit();

            $this->dispatch('success', ['message' => $response['message']]);

            if (! $this->lead_id) {
                return redirect()->route('property::lead::edit', $response['data']['id']);
            }

            $this->mount($this->lead_id);
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function transfer()
    {
        abort_unless(auth()->user()?->can('property lead.booking transfer'), 403);
        try {
            if (! $this->lead_id) {
                throw new \Exception('Please save the lead before transferring.', 1);
            }
            $response = (new TransferAction())->execute($this->lead_id);
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }
            $this->dispatch('success', ['message' => $response['message']]);

            return redirect($response['data']['redirect']);
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        // Keep a legacy stored value selectable instead of silently blanking it.
        $withLegacy = fn (array $options, ?string $current): array => filled($current) && ! isset($options[$current])
            ? $options + [$current => $current.' (legacy)']
            : $options;

        return view('livewire.property.property-lead.page', [
            'statuses' => $withLegacy(leadStatuses(), $this->formData['status'] ?? null),
            'sources' => $withLegacy(leadSources(), $this->formData['source'] ?? null),
            'auditTrail' => $this->lead_id ? LeadAuditTrail::for((int) $this->lead_id) : null,
            'subSources' => $withLegacy(leadSubOptions('lead_sub_sources', $this->formData['source'] ?? null), $this->formData['sub_source'] ?? null),
            'subStatuses' => $withLegacy(leadSubOptions('lead_sub_statuses', $this->formData['status'] ?? null), $this->formData['sub_status'] ?? null),
            'rentalTypes' => leadRentalTypes(),
            'types' => leadTypes(),
            'locations' => propertyLeadLocations(),
            'countries' => Country::orderBy('name')->pluck('name', 'id')->toArray(),
        ]);
    }
}
