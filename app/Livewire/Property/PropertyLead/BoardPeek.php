<?php

namespace App\Livewire\Property\PropertyLead;

use App\Actions\Property\PropertyLead\AddNoteAction;
use App\Models\PropertyLead;
use App\Support\LeadPipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use OwenIt\Auditing\Models\Audit;

/**
 * Details panel beside the lead board: contact actions, "Move to" chips, notes
 * and recent history for one lead. Status moves go through the parent Board so
 * its columns and undo strip stay in step.
 */
class BoardPeek extends Component
{
    public ?int $leadId = null;

    public $note = '';

    public $noteDate;

    public function mount(): void
    {
        $this->noteDate = now()->format('Y-m-d');
    }

    #[On('lead-board-open')]
    public function open(int $id): void
    {
        $this->leadId = $id;
        $this->note = '';
        $this->noteDate = now()->format('Y-m-d');
    }

    #[On('lead-board-moved')]
    public function leadMoved(int $id): void
    {
        if ($id !== $this->leadId) {
            $this->skipRender();
        }
    }

    #[On('lead-board-close')]
    public function close(): void
    {
        $this->leadId = null;
        $this->dispatch('lead-board-closed');
    }

    public function addNote(): void
    {
        abort_unless(auth()->user()?->can('property lead.edit'), 403);
        try {
            DB::beginTransaction();
            $response = (new AddNoteAction())->execute($this->leadId, ['note' => $this->note, 'date' => $this->noteDate], Auth::id());
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();

            $this->note = '';
            $this->dispatch('success', ['message' => $response['message']]);
            $this->dispatch('PropertyLead-Refresh-Component');
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    /** @return array{icon: string, label: string, from: ?string, to: ?string, user: string, at: ?string} */
    protected function describe(Audit $audit): array
    {
        $old = $audit->old_values ?? [];
        $new = $audit->new_values ?? [];

        [$icon, $label, $from, $to] = match (true) {
            $audit->event === 'created' => ['fa-plus-circle', 'Lead created', null, null],
            array_key_exists('status', $new) => ['fa-exchange', 'Status', $old['status'] ?? null, $new['status']],
            array_key_exists('remarks', $new) => ['fa-comments-o', 'Notes updated', null, null],
            array_key_exists('assigned_to', $new) => ['fa-user', 'Reassigned', null, null],
            default => ['fa-pencil', 'Details updated', null, null],
        };

        return [
            'icon' => $icon,
            'label' => $label,
            'from' => $from,
            'to' => $to,
            'user' => $audit->user?->name ?? 'System',
            'at' => $audit->created_at?->diffForHumans(),
        ];
    }

    public function render()
    {
        $lead = $this->leadId
            ? PropertyLead::with(['assignee:id,name', 'group:id,name', 'country:id,name'])->find($this->leadId)
            : null;

        $notes = [];
        $activity = [];
        if ($lead) {
            $notes = is_array($lead->remarks) ? $lead->remarks : (json_decode((string) $lead->remarks, true) ?: []);
            $activity = Audit::where('auditable_type', PropertyLead::class)
                ->where('auditable_id', $lead->id)
                ->with('user')
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (Audit $audit) => $this->describe($audit))
                ->all();
        }

        return view('livewire.property.property-lead.board-peek', [
            'lead' => $lead,
            'status' => $lead ? LeadPipeline::canonical($lead->status) : null,
            'stages' => LeadPipeline::stages(),
            'notes' => array_reverse($notes),
            'activity' => $activity,
            'canEdit' => (bool) auth()->user()?->can('property lead.edit'),
        ]);
    }
}
