<?php

use App\Events\NotificationCreatedEvent;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\TicketNotification;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    Event::fake([NotificationCreatedEvent::class]);
    $this->world = PosWorld::create();

    foreach (config('permissions.ticket') as $action) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => "ticket.{$action}", 'guard_name' => 'web']));
    }

    $member = fn (array $state = []): User => User::factory()->create(['tenant_id' => $this->world->tenant->id, 'is_admin' => 1, 'is_active' => 1] + $state);
    $this->reporter = $member();
    $this->commenter = $member();
    $this->bystander = $member();

    $this->ticket = Ticket::factory()->create(['title' => 'Date range', 'created_by' => $this->reporter->id, 'updated_by' => $this->reporter->id]);
    TicketComment::create(['ticket_id' => $this->ticket->id, 'comment' => 'Which range?', 'created_by' => $this->commenter->id, 'updated_by' => $this->commenter->id]);

    $this->actingAs($this->world->user);
    $this->api = fn (string $path = ''): string => $this->world->url("/ticket/api/{$this->ticket->id}".$path);
    $this->ticketNotifications = fn (User $user) => $user->notifications()->where('type', TicketNotification::class)->get();
});

it('notifies the reporter and commenters, never the actor or bystanders, on a new comment', function (): void {
    $this->postJson(($this->api)('/comment'), ['comment' => 'Per rent-out'])->assertOk();

    $notification = ($this->ticketNotifications)($this->reporter)->sole();
    expect($notification->data['title'])->toBe("New comment on #{$this->ticket->id}")
        ->and($notification->data['content'])->toContain('Per rent-out')
        ->and($notification->data['model_id'])->toBe($this->ticket->id)
        ->and($notification->data['link'])->toEndWith("/ticket?ticket={$this->ticket->id}")
        ->and(($this->ticketNotifications)($this->commenter))->toHaveCount(1)
        ->and(($this->ticketNotifications)($this->bystander))->toBeEmpty()
        ->and(($this->ticketNotifications)($this->world->user))->toBeEmpty();
});

it('announces a status move and an edit to the participants', function (): void {
    $this->patchJson(($this->api)('/status'), ['status' => 'in_progress'])->assertOk();
    expect(($this->ticketNotifications)($this->reporter)->sole()->data['title'])->toBe("#{$this->ticket->id} moved to In Progress");

    $this->post(($this->api)(), ['title' => 'Date range control', 'status' => 'in_progress'], ['Accept' => 'application/json'])->assertOk();
    expect(($this->ticketNotifications)($this->commenter)->pluck('data.title'))->toContain("#{$this->ticket->id} was updated");
});

it('stays quiet when a save changes nothing', function (): void {
    $this->patchJson(($this->api)('/status'), ['status' => $this->ticket->status])->assertOk();

    expect(($this->ticketNotifications)($this->reporter))->toBeEmpty();
});

it('marks the ticket notifications read when the link is opened', function (): void {
    $this->withoutVite();
    $this->postJson(($this->api)('/comment'), ['comment' => 'Ping'])->assertOk();
    $this->reporter->givePermissionTo('ticket.view');

    $this->actingAs($this->reporter)
        ->get($this->world->url("/ticket?ticket={$this->ticket->id}"))
        ->assertOk();

    expect($this->reporter->unreadNotifications()->count())->toBe(0);
});
