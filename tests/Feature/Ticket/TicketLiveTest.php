<?php

use App\Events\TicketActivity;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    Event::fake([TicketActivity::class]);
    $this->withoutDefer();
    $this->world = PosWorld::create();

    foreach (config('permissions.ticket') as $action) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => "ticket.{$action}", 'guard_name' => 'web']));
    }

    $this->actingAs($this->world->user);
    $this->api = fn (string $path = ''): string => $this->world->url('/ticket/api'.$path);
    $this->ticket = Ticket::factory()->create(['created_by' => $this->world->user->id, 'updated_by' => $this->world->user->id]);
    $this->broadcasted = fn (string $action, ?int $ticketId) => Event::assertDispatched(
        TicketActivity::class,
        fn (TicketActivity $event): bool => $event->action === $action
            && $event->ticketId === $ticketId
            && $event->tenantId === $this->world->tenant->id
            && $event->broadcastOn()->name === "private-tenant.{$this->world->tenant->id}.tickets",
    );
});

it('tells the other consoles about every ticket change', function (): void {
    $id = $this->ticket->id;

    $created = $this->postJson(($this->api)(), ['title' => 'Printer offline', 'status' => 'open'])->assertCreated()->json('data.id');
    ($this->broadcasted)('created', $created);

    $this->postJson(($this->api)("/{$id}"), ['title' => 'Renamed', 'status' => 'open'])->assertOk();
    ($this->broadcasted)('updated', $id);

    $this->patchJson(($this->api)("/{$id}/status"), ['status' => 'resolved'])->assertOk();
    ($this->broadcasted)('status', $id);

    $this->postJson(($this->api)("/{$id}/comment"), ['comment' => 'On it'])->assertOk();
    ($this->broadcasted)('comment', $id);

    $this->deleteJson(($this->api)("/{$id}"))->assertOk();
    ($this->broadcasted)('deleted', $id);
});

it('stays silent when a change is refused', function (): void {
    $this->patchJson(($this->api)('/999999/status'), ['status' => 'resolved']);
    $this->postJson(($this->api)("/{$this->ticket->id}/comment"), ['comment' => '']);

    Event::assertNotDispatched(TicketActivity::class);
});

it('sends only a pointer, never ticket data', function (): void {
    expect((new TicketActivity(7, 'updated', 42))->broadcastWith())->toBe(['action' => 'updated', 'ticket_id' => 42])
        ->and((new TicketActivity(7, 'updated', 42))->broadcastAs())->toBe('ticket.activity');
});

it('hands the console its tenant channel', function (): void {
    $this->withoutVite();

    $this->get($this->world->url('/ticket'))
        ->assertOk()
        ->assertSee('data-live-channel="tenant.'.$this->world->tenant->id.'.tickets"', false)
        ->assertSee('name="pusher-key"', false);
});

it('lets only the tenant\'s ticket viewers join the channel', function (): void {
    config(['broadcasting.default' => 'pusher', 'broadcasting.connections.pusher.key' => 'key', 'broadcasting.connections.pusher.secret' => 'secret', 'broadcasting.connections.pusher.app_id' => '1']);
    app(BroadcastManager::class)->purge('pusher');
    require base_path('routes/channels.php');

    $auth = fn (int $tenantId) => $this->post($this->world->url('/broadcasting/auth'), ['socket_id' => '1234.5678', 'channel_name' => "private-tenant.{$tenantId}.tickets"]);

    $auth($this->world->tenant->id)->assertOk()->assertJsonStructure(['auth']);
    $auth($this->world->tenant->id + 1)->assertForbidden();

    $outsider = User::factory()->create(['tenant_id' => $this->world->tenant->id, 'is_admin' => 0, 'is_active' => 1]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->actingAs($outsider);
    $auth($this->world->tenant->id)->assertForbidden();
});
