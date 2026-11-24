<?php

use App\Livewire\SaleDaySession\BranchSaleDaySessionManager;
use App\Livewire\SaleDaySession\MoqSync;
use App\Models\ApiLog;
use App\Models\SaleDaySession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * MOQ revenue sync lives on /sale/day-session/{id} behind its own permission
 * (`day session.moq sync`), not in the Day Management close form. The user
 * types the amount; closing a day never posts to MOQ.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);

    $this->world->branch->update(['moq_sync' => true]);

    config([
        'moq.username' => 'moq-user',
        'moq.password' => 'moq-pass',
        'moq.token' => 'moq-token',
        'moq.endpoint' => 'https://moq.test/live/postsales',
    ]);

    $this->session = SaleDaySession::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => Carbon::today()->setTime(9, 0),
        'opening_amount' => 100,
        'status' => 'open',
    ]);

    $this->grant = fn () => $this->world->user->givePermissionTo(Permission::findOrCreate('day session.moq sync', 'web'));
});

it('posts the typed amount to MOQ and stamps the session', function (): void {
    ($this->grant)();
    Http::fake(['moq.test/*' => Http::response(['status' => 'Posted'], 200)]);

    Livewire::test(MoqSync::class, ['sessionId' => $this->session->id])
        ->set('amount', '1250')
        ->call('sync')
        ->assertDispatched('success');

    $this->session->refresh();

    expect((float) $this->session->sync_amount)->toBe(1250.0)
        ->and($this->session->synced_at)->not->toBeNull()
        ->and($this->session->synced_by)->toBe($this->world->user->id)
        ->and(ApiLog::query()->where('service_name', 'Moq DayClose Sync')->value('status'))->toBe('success');

    Http::assertSent(fn ($request) => $request->url() === 'https://moq.test/live/postsales'
        && $request[0]['Date'] === Carbon::today()->format('Y-m-d')
        && (int) $request[0]['Revenue'] === 1250);
});

it('syncs a session that is already closed', function (): void {
    ($this->grant)();
    Http::fake(['moq.test/*' => Http::response(['status' => 'Posted'], 200)]);
    $this->session->update(['status' => 'closed', 'closed_at' => now(), 'closing_amount' => 100]);

    Livewire::test(MoqSync::class, ['sessionId' => $this->session->id])
        ->set('amount', 900)
        ->call('sync')
        ->assertDispatched('success');

    expect((float) $this->session->refresh()->sync_amount)->toBe(900.0);
});

it('leaves the session unsynced when MOQ rejects the post', function (): void {
    ($this->grant)();
    Http::fake(['moq.test/*' => Http::response(['status' => 'Duplicate'], 200)]);

    Livewire::test(MoqSync::class, ['sessionId' => $this->session->id])
        ->set('amount', 500)
        ->call('sync')
        ->assertDispatched('error');

    $this->session->refresh();

    expect($this->session->synced_at)->toBeNull()
        ->and((float) $this->session->sync_amount)->toBe(0.0)
        ->and(ApiLog::query()->where('service_name', 'Moq DayClose Sync')->value('status'))->toBe('failed');
});

it('rejects an invalid amount without calling MOQ', function (mixed $amount): void {
    ($this->grant)();
    Http::fake();

    Livewire::test(MoqSync::class, ['sessionId' => $this->session->id])
        ->set('amount', $amount)
        ->call('sync')
        ->assertHasErrors('amount');

    Http::assertNothingSent();
})->with(['negative' => -5, 'text' => 'abc', 'empty' => '', 'decimal' => '12.5']);

it('refuses to sync when the branch has MOQ sync off', function (): void {
    ($this->grant)();
    Http::fake();
    $this->world->branch->update(['moq_sync' => false]);

    Livewire::test(MoqSync::class, ['sessionId' => $this->session->id])
        ->set('amount', 500)
        ->call('sync')
        ->assertDispatched('error');

    Http::assertNothingSent();
});

it('pre-fills the last synced amount when the modal opens', function (): void {
    $this->session->update(['sync_amount' => 4200, 'synced_at' => now(), 'synced_by' => $this->world->user->id]);

    Livewire::test(MoqSync::class, ['sessionId' => $this->session->id])
        ->call('prepare')
        ->assertSet('amount', 4200)
        ->assertSee('Re-sync MOQ');
});

it('offers whole-number quick-fill amounts and drops empty ones', function (): void {
    Livewire::test(MoqSync::class, [
        'sessionId' => $this->session->id,
        'suggestions' => ['Total sales' => 5750.75, 'Collected' => 5700, 'Counted' => null],
    ])->assertSet('suggestions', ['Total sales' => 5750.0, 'Collected' => 5700.0]);
});

it('forbids syncing without the permission', function (): void {
    Http::fake();

    Livewire::test(MoqSync::class, ['sessionId' => $this->session->id])
        ->set('amount', 500)
        ->call('sync')
        ->assertForbidden();

    Http::assertNothingSent();
});

it('shows the sync button on the session page only with the permission', function (): void {
    Permission::findOrCreate('day session.view', 'web');
    $this->world->user->givePermissionTo('day session.view');

    $url = $this->world->url(route('sale::day-session', $this->session->id, absolute: false));

    $this->get($url)->assertOk()->assertDontSee('Sync to MOQ');

    ($this->grant)();

    $this->get($url)->assertOk()->assertSee('Sync to MOQ');
});

it('does not post to MOQ when the day is closed from Day Management', function (): void {
    Http::fake();
    session(['branch_id' => $this->world->branch->id]);

    Livewire::test(BranchSaleDaySessionManager::class)
        ->set('closing_amount', 100)
        ->call('closeDay');

    expect($this->session->refresh()->status)->toBe('closed');
    Http::assertNothingSent();
});
