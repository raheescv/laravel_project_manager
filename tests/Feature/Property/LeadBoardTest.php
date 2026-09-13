<?php

use App\Actions\Property\PropertyLead\UpdateStatusAction;
use App\Livewire\Property\PropertyLead\Board;
use App\Livewire\Property\PropertyLead\BoardPeek;
use App\Models\PropertyLead;
use App\Models\UserPreference;
use App\Support\LeadPipeline;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The lead board reads real, drifted data: stored statuses carry trailing
 * spaces, odd casing and typos, and legacy mobiles fail the lead form's rules.
 * These drive the real database so the grouping and the moves are proven
 * against MySQL collation rather than a mock.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);
});

function leadBoardGrant(string $action): void
{
    $permission = config('permission.models.permission');

    test()->world->user->givePermissionTo($permission::firstOrCreate([
        'tenant_id' => test()->world->tenant->id,
        'name' => "property lead.{$action}",
        'guard_name' => 'web',
    ]));
}

function leadBoardLead(array $attributes = []): PropertyLead
{
    return PropertyLead::create([
        'tenant_id' => test()->world->tenant->id,
        'branch_id' => test()->world->branch->id,
        'name' => 'Lead '.uniqid(),
        'type' => 'Sales',
        'source' => 'Walk-In',
        'status' => 'New Lead',
        ...$attributes,
    ]);
}

it('folds drifted stored statuses into their real column and parks unknown ones', function (): void {
    leadBoardLead(['status' => 'Follow Up']);
    leadBoardLead(['status' => 'follow up ']);
    leadBoardLead(['status' => 'Fallow Up']);

    Livewire::test(Board::class)
        ->assertViewHas('counts', fn (array $counts) => $counts['Follow Up'] === 2 && $counts[LeadPipeline::UNMAPPED] === 1)
        ->assertViewHas('cards', fn (array $cards) => $cards['Follow Up']->count() === 2)
        ->assertSee('Needs fixing');
});

it('moves a lead to another status and can undo the move', function (): void {
    leadBoardGrant('edit');
    $lead = leadBoardLead();

    $board = Livewire::test(Board::class)
        ->call('moveLead', $lead->id, 'Interested')
        ->assertDispatched('lead-board-moved', id: $lead->id)
        ->assertSet('lastMove.from', 'New Lead')
        ->assertSet('lastMove.to', 'Interested')
        ->assertViewHas('cards', fn (array $cards) => $cards['Interested']->pluck('id')->contains($lead->id));

    expect($lead->fresh())
        ->status->toBe('Interested')
        ->updated_by->toBe($this->world->user->id);

    $board->call('undoMove')->assertSet('lastMove', null);

    expect($lead->fresh()->status)->toBe('New Lead');
});

it('refuses to move a lead without the edit permission', function (): void {
    $lead = leadBoardLead();

    Livewire::test(Board::class)
        ->call('moveLead', $lead->id, 'Interested')
        ->assertForbidden();

    expect($lead->fresh()->status)->toBe('New Lead');
});

it('changes only the status, so a legacy mobile never blocks a move', function (): void {
    $lead = leadBoardLead(['mobile' => 'call the office']);

    $moved = (new UpdateStatusAction())->execute($lead->id, 'Closed Deal', $this->world->user->id);
    $unknown = (new UpdateStatusAction())->execute($lead->id, 'Priority', $this->world->user->id);

    expect($moved['success'])->toBeTrue()
        ->and($unknown['success'])->toBeFalse()
        ->and($lead->fresh()->status)->toBe('Closed Deal');
});

it('remembers pinned columns per user', function (): void {
    Livewire::test(Board::class)
        ->assertViewHas('pinned', fn (array $pinned) => ! in_array('Drop', $pinned, true))
        ->call('togglePin', 'Drop')
        ->assertViewHas('pinned', fn (array $pinned) => in_array('Drop', $pinned, true));

    expect(UserPreference::getValue('property.lead.board.columns'))->toHaveKey('Drop', true);

    Livewire::test(Board::class)
        ->assertViewHas('pinned', fn (array $pinned) => in_array('Drop', $pinned, true));
});

it('narrows the board to the signed-in user with the My leads preset', function (): void {
    leadBoardLead(['assigned_to' => $this->world->user->id]);
    leadBoardLead();

    Livewire::test(Board::class)
        ->assertViewHas('counts', fn (array $counts) => $counts['New Lead'] === 2)
        ->assertViewHas('presetCounts', fn (array $counts) => $counts['mine'] === 1 && $counts['unassigned'] === 1)
        ->call('setPreset', 'mine')
        ->assertViewHas('counts', fn (array $counts) => $counts['New Lead'] === 1)
        ->call('setPreset', 'mine')
        ->assertSet('preset', 'all')
        ->call('clearFilters')
        ->assertDispatched('lead-board-filters-cleared');
});

it('loads another page of cards into a column', function (): void {
    foreach (range(1, Board::PAGE + 5) as $ignored) {
        leadBoardLead();
    }

    Livewire::test(Board::class)
        ->assertViewHas('cards', fn (array $cards) => $cards['New Lead']->count() === Board::PAGE)
        ->call('loadMore', 'New Lead')
        ->assertViewHas('cards', fn (array $cards) => $cards['New Lead']->count() === Board::PAGE + 5);
});

it('adds a note to the lead from the details panel', function (): void {
    leadBoardGrant('edit');
    $lead = leadBoardLead(['remarks' => [['date' => '2026-09-01', 'note' => 'First call', 'user' => 'Someone']]]);

    Livewire::test(BoardPeek::class)
        ->call('open', $lead->id)
        ->assertSee('First call')
        ->set('note', 'Wants a sea view')
        ->set('noteDate', '2026-09-13')
        ->call('addNote')
        ->assertDispatched('PropertyLead-Refresh-Component')
        ->assertSee('Wants a sea view');

    expect($lead->fresh()->remarks)->toHaveCount(2)
        ->and($lead->fresh()->remarks[1])->toMatchArray([
            'date' => '2026-09-13',
            'note' => 'Wants a sea view',
            'user' => $this->world->user->name,
        ]);
});
