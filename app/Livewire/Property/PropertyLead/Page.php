<?php

namespace App\Livewire\Property\PropertyLead;

use App\Actions\Property\PropertyLead\CreateAction;
use App\Actions\Property\PropertyLead\TransferAction;
use App\Actions\Property\PropertyLead\UpdateAction;
use App\Models\Country;
use App\Models\PropertyLead;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use OwenIt\Auditing\Models\Audit;

class Page extends Component
{
    public $lead_id;

    public $formData = [];

    public $note = '';

    public $noteDate;

    public $notes = [];

    public $audits = [];

    public $auditColumns = [];

    public function mount($lead_id = null): void
    {
        $this->lead_id = $lead_id;
        $this->noteDate = now()->format('Y-m-d');

        if ($this->lead_id) {
            $lead = PropertyLead::with(['country', 'group', 'assignee'])->findOrFail($this->lead_id);
            $this->formData = $lead->toArray();

            // Ensure date/time fields are in HTML5 input compatible format
            $this->formData['assign_date'] = $lead->assign_date?->format('Y-m-d');
            $this->formData['meeting_date'] = $lead->meeting_date?->format('Y-m-d');
            $this->formData['meeting_time'] = $lead->meeting_time
                ? \Carbon\Carbon::parse($lead->meeting_time)->format('H:i')
                : null;

            $this->notes = is_array($lead->remarks) ? $lead->remarks : (json_decode($lead->remarks ?? '[]', true) ?: []);
            $this->loadAudits();
        } else {
            $this->formData = [
                'name' => 'New Lead - '.((PropertyLead::withTrashed()->count() ?? 0) + 1),
                'mobile' => '',
                'branch_id' => session('branch_id'),
                'email' => '',
                'company_name' => '',
                'company_contact_no' => '',
                'property_group_id' => '',
                'assigned_to' => Auth::id(),
                'assign_date' => now()->format('Y-m-d'),
                'source' => 'Outdoor Marketing',
                'type' => 'Sales',
                'status' => 'New Lead',
                'location' => null,
                'meeting_date' => null,
                'meeting_time' => null,
                'country_id' => null,
                'nationality' => null,
            ];
            $this->notes = [];
        }

        $this->buildAuditColumns();
    }

    protected function loadAudits(): void
    {
        $this->audits = Audit::where('auditable_type', PropertyLead::class)
            ->where('auditable_id', $this->lead_id)
            ->with('user')
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    protected function buildAuditColumns(): void
    {
        $columns = Schema::getColumnListing('property_leads');
        $remove = ['id', 'tenant_id', 'branch_id'];
        $this->auditColumns = array_values(array_diff($columns, $remove));
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
            'formData.name' => 'required|string|max:255',
            'formData.mobile' => 'nullable|string|max:20',
            'formData.email' => 'nullable|email|max:255',
            'formData.type' => 'required|in:Sales,Rentout',
            'formData.source' => 'required|string',
            'formData.status' => 'nullable|string|max:30',
        ];
    }

    protected $messages = [
        'formData.name.required' => 'The name field is required.',
        'formData.mobile.regex' => 'Mobile must contain 6-20 digits.',
        'formData.email.email' => 'The email must be a valid email address.',
        'formData.type.required' => 'The type field is required.',
        'formData.source.required' => 'The source field is required.',
    ];

    public function save()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $payload = $this->formData;
            $payload['remarks'] = $this->notes;

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
        return view('livewire.property.property-lead.page', [
            'statuses' => leadStatuses(),
            'sources' => leadSources(),
            'types' => leadTypes(),
            'locations' => propertyLeadLocations(),
            'countries' => Country::orderBy('name')->pluck('name', 'id')->toArray(),
        ]);
    }
}
