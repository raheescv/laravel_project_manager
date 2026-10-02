<?php

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

function revokeTicketPermission(PosWorld $world, string $action): void
{
    $world->user->revokePermissionTo("ticket.{$action}");
    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

it('serves the board and import consoles without the admin chrome', function (): void {
    $this->withoutVite();

    $this->get($this->world->url('/ticket'))
        ->assertOk()
        ->assertSee('id="ticket-console"', false)
        ->assertSee('data-view="board"', false)
        ->assertDontSee('id="mainnav-container"', false);

    $this->get($this->world->url('/ticket/import'))->assertOk()->assertSee('data-view="import"', false);

    revokeTicketPermission($this->world, 'import');
    $this->get($this->world->url('/ticket/import'))->assertForbidden();
});

it('returns the board as status columns with group counts that ignore the group filter', function (): void {
    ($this->ticket)(['title' => 'Printer idle', 'group' => 'Sales', 'status' => 'open']);
    ($this->ticket)(['title' => 'Rounding', 'group' => 'Accounts', 'status' => 'open']);
    ($this->ticket)(['title' => 'Close day spins', 'group' => 'Sales', 'status' => 'in_progress']);
    ($this->ticket)(['title' => 'Loose one', 'group' => null, 'status' => 'closed']);

    $board = $this->getJson(($this->api)('/board'))->assertOk()->json('data');
    expect($board['columns']['open']['total'])->toBe(2)
        ->and($board['columns']['in_progress']['tickets'][0]['title'])->toBe('Close day spins')
        ->and($board['all_groups'])->toBe(['Accounts', 'Sales']);

    $sales = $this->getJson(($this->api)('/board?group=Sales'))->assertOk()->json('data');
    expect($sales['columns']['open']['total'])->toBe(1)
        ->and($sales['columns']['closed']['total'])->toBe(0)
        ->and(collect($sales['groups'])->pluck('count', 'name')->all())->toBe(['' => 1, 'Accounts' => 1, 'Sales' => 2]);

    $ungrouped = $this->getJson(($this->api)('/board?group='.Ticket::NO_GROUP))->json('data');
    expect($ungrouped['columns']['closed']['tickets'][0]['title'])->toBe('Loose one')
        ->and($ungrouped['columns']['open']['total'])->toBe(0);
});

it('creates a ticket with files and folds the group into the spelling already in use', function (): void {
    Storage::fake('public');
    ($this->ticket)(['group' => 'Sales']);

    $response = $this->post(($this->api)(), [
        'title' => 'Receipt not printing',
        'description' => 'Idle after cash sale',
        'status' => 'in_progress',
        'group' => '  sales ',
        'files' => [UploadedFile::fake()->image('shot.png')],
    ], ['Accept' => 'application/json'])->assertCreated();

    $ticket = Ticket::latest('id')->first();
    expect($ticket->group)->toBe('Sales')
        ->and($ticket->status)->toBe('in_progress')
        ->and($response->json('data.attachments.0.is_image'))->toBeTrue();
    Storage::disk('public')->assertExists($ticket->attachments->first()->file_path);
});

it('rejects a ticket without a title and refuses users without the create permission', function (): void {
    $this->postJson(($this->api)(), ['title' => '', 'status' => 'open'])->assertUnprocessable()->assertJsonPath('errors.title.0', 'Give the ticket a title.');

    revokeTicketPermission($this->world, 'create');
    $this->postJson(($this->api)(), ['title' => 'x', 'status' => 'open'])->assertForbidden();
});

it('moves a ticket between columns and edits it', function (): void {
    $ticket = ($this->ticket)(['status' => 'open', 'group' => 'Sales']);

    $this->patchJson(($this->api)("/{$ticket->id}/status"), ['status' => 'resolved'])
        ->assertOk()
        ->assertJsonPath('message', 'Moved to Resolved.');
    expect($ticket->fresh()->status)->toBe('resolved');

    $this->patchJson(($this->api)("/{$ticket->id}/status"), ['status' => 'nope'])->assertUnprocessable();

    $this->post(($this->api)("/{$ticket->id}"), ['title' => 'Renamed', 'status' => 'resolved', 'group' => ''], ['Accept' => 'application/json'])->assertOk();
    expect($ticket->fresh())->title->toBe('Renamed')->group->toBeNull();
});

it('adds, edits and deletes comments on a ticket', function (): void {
    $ticket = ($this->ticket)();

    $this->postJson(($this->api)("/{$ticket->id}/comment"), ['comment' => 'Reproduced'])->assertOk();
    $comment = TicketComment::firstOrFail();

    $this->putJson(($this->api)("/{$ticket->id}/comment/{$comment->id}"), ['comment' => 'Reproduced twice'])->assertOk();
    expect($comment->fresh()->comment)->toBe('Reproduced twice');

    $this->getJson(($this->api)("/{$ticket->id}"))->assertOk()->assertJsonPath('data.comments.0.comment', 'Reproduced twice');

    $this->postJson(($this->api)("/{$ticket->id}/comment"), ['comment' => ''])->assertUnprocessable();
    $this->deleteJson(($this->api)("/{$ticket->id}/comment/{$comment->id}"))->assertOk();
    expect(TicketComment::count())->toBe(0);
});

it('deletes a ticket together with its comments and stored files', function (): void {
    Storage::fake('public');
    $ticket = ($this->ticket)();
    Storage::disk('public')->put("tickets/{$ticket->id}/a.png", 'x');
    TicketAttachment::create(['ticket_id' => $ticket->id, 'file_path' => "tickets/{$ticket->id}/a.png", 'file_name' => 'a.png', 'mime_type' => 'image/png', 'file_size' => 1]);
    TicketComment::create(['ticket_id' => $ticket->id, 'comment' => 'hi', 'created_by' => $this->world->user->id, 'updated_by' => $this->world->user->id]);

    $this->deleteJson(($this->api)("/{$ticket->id}"))->assertOk();

    expect(Ticket::count())->toBe(0)->and(TicketComment::count())->toBe(0)->and(TicketAttachment::count())->toBe(0);
    Storage::disk('public')->assertMissing("tickets/{$ticket->id}/a.png");
});
