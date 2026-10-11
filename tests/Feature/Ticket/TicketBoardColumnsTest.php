<?php

use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    $this->world = PosWorld::create();

    foreach (config('permissions.ticket') as $action) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => "ticket.{$action}", 'guard_name' => 'web']));
    }

    $this->actingAs($this->world->user);
    $this->api = fn (string $path = ''): string => $this->world->url('/ticket/api'.$path);
    $this->ticket = fn (array $state = []): Ticket => Ticket::factory()->create(['created_by' => $this->world->user->id, 'updated_by' => $this->world->user->id] + $state);
});

it('starts every tenant on the default four columns and sends them with the board', function (): void {
    $board = $this->getJson(($this->api)('/board'))->assertOk()->json('data');

    expect(collect($board['statuses'])->pluck('label', 'key')->all())->toBe(['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'])
        ->and(array_keys($board['columns']))->toBe(['open', 'in_progress', 'resolved', 'closed'])
        ->and(TicketStatus::count())->toBe(4);

    $this->getJson(($this->api)('/board'))->assertOk();
    expect(TicketStatus::count())->toBe(4);
});

it('adds a column that tickets can move into and keeps each tenant\'s columns apart', function (): void {
    $this->postJson(($this->api)('/statuses'), ['name' => 'QA Review', 'color' => '#9787C4', 'icon' => 'fa-eye'])
        ->assertCreated()
        ->assertJsonPath('message', 'Column "QA Review" added.')
        ->assertJsonPath('data.4', ['id' => TicketStatus::where('key', 'qa_review')->value('id'), 'key' => 'qa_review', 'label' => 'QA Review', 'color' => '#9787c4', 'icon' => 'fa-eye', 'tickets_count' => 0]);

    $ticket = ($this->ticket)();
    $this->patchJson(($this->api)("/{$ticket->id}/status"), ['status' => 'qa_review'])->assertOk()->assertJsonPath('message', 'Moved to QA Review.');
    expect($this->getJson(($this->api)('/board'))->json('data.columns.qa_review.total'))->toBe(1);

    $this->postJson(($this->api)('/statuses'), ['name' => 'qa review', 'color' => '#9787c4', 'icon' => 'fa-eye'])
        ->assertUnprocessable()->assertJsonPath('errors.name.0', 'There is already a column with this name.');
    $this->postJson(($this->api)('/statuses'), ['name' => 'Odd', 'color' => 'red', 'icon' => '<script>'])->assertJsonValidationErrors(['color', 'icon']);

    $other = Tenant::factory()->create();
    expect(TicketStatus::withTenant($other->id)->count())->toBe(0);
});

it('renames and recolours a column without touching its tickets', function (): void {
    $ticket = ($this->ticket)(['status' => 'in_progress']);
    $column = TicketStatus::ordered()->firstWhere('key', 'in_progress');

    $this->putJson(($this->api)("/statuses/{$column->id}"), ['name' => 'Working on it', 'color' => '#64a3a6'])->assertOk();

    expect($column->fresh())->name->toBe('Working on it')->key->toBe('in_progress')->color->toBe('#64a3a6')->icon->toBe('fa-spinner')
        ->and($ticket->fresh()->status)->toBe('in_progress');
});

it('reorders the columns and refuses an incomplete order', function (): void {
    $ids = TicketStatus::ordered()->pluck('id')->all();

    $this->putJson(($this->api)('/statuses/order'), ['ids' => array_reverse($ids)])->assertOk();
    expect(TicketStatus::ordered()->pluck('key')->all())->toBe(['closed', 'resolved', 'in_progress', 'open'])
        ->and(TicketStatus::defaultKey())->toBe('closed');

    $this->putJson(($this->api)('/statuses/order'), ['ids' => [$ids[0]]])->assertUnprocessable();
});

it('removes a column only after its tickets have somewhere to go, and never the last one', function (): void {
    $statuses = TicketStatus::ordered()->keyBy('key');
    ($this->ticket)(['status' => 'resolved']);
    ($this->ticket)(['status' => 'resolved']);

    $this->deleteJson(($this->api)("/statuses/{$statuses['resolved']->id}"))
        ->assertUnprocessable()->assertJsonPath('message', 'Choose a column for the tickets in "Resolved" first.');
    $this->deleteJson(($this->api)("/statuses/{$statuses['resolved']->id}"), ['move_to' => 'resolved'])->assertUnprocessable();

    $this->deleteJson(($this->api)("/statuses/{$statuses['resolved']->id}"), ['move_to' => 'closed'])
        ->assertOk()->assertJsonPath('message', 'Column removed; 2 tickets moved.');
    expect(Ticket::where('status', 'closed')->count())->toBe(2)
        ->and(TicketStatus::where('key', 'resolved')->exists())->toBeFalse();

    $this->deleteJson(($this->api)("/statuses/{$statuses['open']->id}"))->assertOk();
    $this->deleteJson(($this->api)("/statuses/{$statuses['in_progress']->id}"))->assertOk();
    $this->deleteJson(($this->api)("/statuses/{$statuses['closed']->id}"), ['move_to' => 'open'])->assertUnprocessable();
    expect(TicketStatus::count())->toBe(1);

    $this->postJson(($this->api)(), ['title' => 'Lands in the only column', 'status' => 'closed'])->assertCreated();
    $this->postJson(($this->api)(), ['title' => 'Old key', 'status' => 'open'])->assertUnprocessable();
});

it('keeps column management behind its own permission', function (): void {
    $this->world->user->revokePermissionTo('ticket.manage board');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->getJson(($this->api)('/statuses'))->assertForbidden();
    $this->postJson(($this->api)('/statuses'), ['name' => 'QA', 'color' => '#9787c4', 'icon' => 'fa-eye'])->assertForbidden();
});

it('saves, filters and returns a ticket priority', function (): void {
    $this->postJson(($this->api)(), ['title' => 'Till frozen', 'status' => 'open', 'priority' => 'urgent'])->assertCreated()->assertJsonPath('data.priority', 'urgent');
    ($this->ticket)(['title' => 'Typo on receipt', 'priority' => 'low']);
    ($this->ticket)(['title' => 'No priority']);

    $board = $this->getJson(($this->api)('/board?priority=urgent'))->assertOk()->json('data.columns.open');
    expect($board['total'])->toBe(1)->and($board['tickets'][0])->toMatchArray(['title' => 'Till frozen', 'priority' => 'urgent']);

    $ticket = Ticket::where('title', 'Till frozen')->sole();
    $this->post(($this->api)("/{$ticket->id}"), ['title' => 'Till frozen', 'status' => 'open', 'priority' => ''], ['Accept' => 'application/json'])->assertOk();
    expect($ticket->fresh()->priority)->toBeNull();

    $this->postJson(($this->api)(), ['title' => 'Bad', 'status' => 'open', 'priority' => 'asap'])->assertJsonValidationErrors('priority');
});
