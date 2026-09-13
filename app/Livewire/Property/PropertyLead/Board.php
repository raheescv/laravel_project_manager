<?php

namespace App\Livewire\Property\PropertyLead;

use App\Actions\Property\PropertyLead\GetAction;
use App\Actions\Property\PropertyLead\UpdateStatusAction;
use App\Livewire\Concerns\HasColumnPreferences;
use App\Models\UserPreference;
use App\Support\LeadPipeline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Renderless;
use Livewire\Component;

/**
 * Lead board ("Focus deck"): a column per pinned status, a rail of preset views
 * and column pins, and the BoardPeek details panel for the selected lead.
 *
 * Columns count leads by real status, so drifted stored values ("Low Budget ",
 * "Dead lead") land in their proper column and unknown ones in
 * LeadPipeline::UNMAPPED. Pins (via HasColumnPreferences) and card density
 * are saved per user.
 */
class Board extends Component
{
    use HasColumnPreferences;

    /** Cards per column page. */
    public const PAGE = 20;

    /** Days without an update before an open lead counts as idle. */
    public const IDLE_DAYS = 14;

    public const PRESETS = ['all', 'mine', 'meetings', 'idle', 'unassigned'];

    private const DEFAULT_PINNED = ['New Lead', 'Follow Up', 'Call Back', 'Interested', 'Visit Scheduled', 'Closed Deal'];

    private const DENSITY_KEY = 'property.lead.board.density';

    private const FILTERS = ['search', 'filterType', 'filterPropertyGroupId', 'filterAssignedTo', 'filterSource', 'fromDate', 'toDate'];

    public $search = '';

    public $filterType = '';

    public $filterPropertyGroupId = '';

    /** A user id, '' for everyone, or 'none' for unassigned leads. */
    public $filterAssignedTo = '';

    public $filterSource = '';

    public $fromDate = '';

    public $toDate = '';

    public $preset = 'all';

    public $density = 'comfort';

    /** @var array<string, int> pages of cards loaded per column */
    public array $pages = [];

    /** @var array{id: int, name: string, from: string, to: string, key: string}|null */
    public ?array $lastMove = null;

    protected $listeners = [
        'PropertyLead-Refresh-Component' => '$refresh',
    ];

    public function mount(): void
    {
        $this->initializeColumnPreferences();
        $this->density = UserPreference::getValue(self::DENSITY_KEY) === 'compact' ? 'compact' : 'comfort';
    }

    protected function defaultColumns(): array
    {
        $columns = [];
        foreach ([...LeadPipeline::statuses(), LeadPipeline::UNMAPPED] as $status) {
            $columns[$status] = in_array($status, self::DEFAULT_PINNED, true);
        }

        return $columns;
    }

    protected function columnPreferenceKey(): string
    {
        return 'property.lead.board.columns';
    }

    public function updated($property): void
    {
        if (in_array($property, self::FILTERS, true)) {
            $this->pages = [];
        }
    }

    public function togglePin(string $status): void
    {
        if (array_key_exists($status, $this->columns)) {
            $this->columns[$status] = ! $this->columns[$status];
            $this->persistColumns();
        }
    }

    /** Switch to a preset view; picking the active one goes back to all leads. */
    public function setPreset(string $preset): void
    {
        $this->preset = in_array($preset, self::PRESETS, true) && $preset !== $this->preset ? $preset : 'all';
        $this->pages = [];
    }

    public function loadMore(string $status): void
    {
        $this->pages[$status] = ($this->pages[$status] ?? 1) + 1;
    }

    #[Renderless]
    public function saveDensity(string $density): void
    {
        $this->density = $density === 'compact' ? 'compact' : 'comfort';
        UserPreference::setValue(self::DENSITY_KEY, $this->density);
    }

    public function clearFilters(): void
    {
        $this->reset([...self::FILTERS, 'preset', 'pages']);
        $this->dispatch('lead-board-filters-cleared');
    }

    public function moveLead($id, string $status): void
    {
        abort_unless(auth()->user()?->can('property lead.edit'), 403);
        try {
            DB::beginTransaction();
            $response = (new UpdateStatusAction())->execute($id, $status, Auth::id());
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();

            $from = LeadPipeline::canonical($response['data']['from']);
            $this->lastMove = $from === $status ? null : [
                'id' => $response['data']['id'],
                'name' => $response['data']['model']->name,
                'from' => $from,
                'to' => $status,
                'key' => uniqid(),
            ];
            $this->dispatch('lead-board-moved', id: $response['data']['id']);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    /** Put the last moved lead back. A lead that came from an unknown status cannot go back to it. */
    public function undoMove(): void
    {
        $move = $this->lastMove;
        if (! $move || $move['from'] === LeadPipeline::UNMAPPED) {
            return;
        }

        $this->moveLead($move['id'], $move['from']);
        $this->lastMove = null;
    }

    /** Leads matching the toolbar filters, narrowed by the preset view unless asked not to. */
    protected function leadQuery(bool $withPreset = true): Builder
    {
        $query = (new GetAction())->execute([
            'search' => trim((string) $this->search),
            'type' => $this->filterType,
            'property_group_id' => $this->filterPropertyGroupId,
            'assigned_to' => $this->filterAssignedTo === 'none' ? '' : $this->filterAssignedTo,
            'source' => $this->filterSource,
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
        ])['list']->setEagerLoads([]);

        $query->when($this->filterAssignedTo === 'none', fn ($q) => $q->whereNull('assigned_to'));

        if (! $withPreset) {
            return $query;
        }

        return match ($this->preset) {
            'mine' => $query->where('assigned_to', Auth::id()),
            'unassigned' => $query->whereNull('assigned_to'),
            'meetings' => $query->whereBetween('meeting_date', [today()->toDateString(), today()->addDays(6)->toDateString()]),
            'idle' => $query->where('updated_at', '<=', now()->subDays(self::IDLE_DAYS))->whereNotIn('status', LeadPipeline::closedStatuses()),
            default => $query,
        };
    }

    /**
     * Lead count per real status, plus the stored values behind each so a column
     * query can match them exactly.
     *
     * @return array{0: array<string, int>, 1: array<string, list<string>>}
     */
    protected function statusBreakdown(): array
    {
        $counts = array_fill_keys([...LeadPipeline::statuses(), LeadPipeline::UNMAPPED], 0);
        $stored = [];

        $rows = $this->leadQuery()->toBase()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get();

        foreach ($rows as $row) {
            $status = LeadPipeline::canonical($row->status);
            $counts[$status] += (int) $row->total;
            $stored[$status][] = $row->status;
        }

        return [$counts, $stored];
    }

    /**
     * Most recently updated leads for each pinned column.
     *
     * @param  list<string>  $statuses
     * @param  array<string, list<string>>  $stored
     * @return array<string, Collection>
     */
    protected function columnCards(array $statuses, array $stored): array
    {
        $cards = [];
        foreach ($statuses as $status) {
            $cards[$status] = empty($stored[$status])
                ? new Collection()
                : $this->leadQuery()
                    ->whereIn('status', $stored[$status])
                    ->select(['id', 'name', 'company_name', 'type', 'source', 'property_group_id', 'assigned_to', 'meeting_date', 'meeting_time', 'status', 'updated_at'])
                    ->selectRaw('COALESCE(JSON_LENGTH(remarks), 0) as notes_count')
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->limit(self::PAGE * ($this->pages[$status] ?? 1))
                    ->get();
        }

        (new Collection(array_merge(...array_map(fn (Collection $column) => $column->all(), array_values($cards)))))
            ->load(['assignee:id,name', 'group:id,name']);

        return $cards;
    }

    /** @return array{total: int, mine: int, unassigned: int, meetings: int, idle: int} leads behind each preset, under the toolbar filters */
    protected function presetCounts(): array
    {
        $closed = LeadPipeline::closedStatuses() ?: [''];
        $placeholders = implode(', ', array_fill(0, count($closed), '?'));

        $row = $this->leadQuery(withPreset: false)->toBase()->selectRaw(
            "COUNT(*) as total,
            SUM(assigned_to = ?) as mine,
            SUM(assigned_to IS NULL) as unassigned,
            SUM(meeting_date BETWEEN ? AND ?) as meetings,
            SUM(updated_at <= ? AND status NOT IN ($placeholders)) as idle",
            [Auth::id(), today()->toDateString(), today()->addDays(6)->toDateString(), now()->subDays(self::IDLE_DAYS), ...$closed]
        )->first();

        return array_map(fn ($value) => (int) $value, (array) $row);
    }

    public function render()
    {
        [$counts, $stored] = $this->statusBreakdown();

        $pinned = array_values(array_filter(
            [...LeadPipeline::statuses(), LeadPipeline::UNMAPPED],
            fn (string $status) => ! empty($this->columns[$status])
        ));

        return view('livewire.property.property-lead.board', [
            'stages' => LeadPipeline::stages(),
            'counts' => $counts,
            'total' => array_sum($counts),
            'pinned' => $pinned,
            'cards' => $this->columnCards($pinned, $stored),
            'presetCounts' => $this->presetCounts(),
            'idleBefore' => now()->subDays(self::IDLE_DAYS),
            'sources' => leadSources(),
            'types' => leadTypes(),
            'canMove' => (bool) auth()->user()?->can('property lead.edit'),
        ]);
    }
}
