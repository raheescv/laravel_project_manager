<?php

use App\Models\SaleDaySession;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The app attaches `branch_id` — the branch it is operating as — to every
 * request, and the dashboard already answers for that branch. The day-session
 * endpoints used to answer for the cashier's *default* branch regardless, so a
 * manager who had switched the app to another branch saw that branch's takings
 * beside the wrong branch's day, and the Day Session screen named one branch
 * while opening the other's day.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::firstOrCreate([
        'tenant_id' => $this->world->tenant->id,
        'name' => 'day session.create',
        'guard_name' => 'web',
    ]));
    Sanctum::actingAs($this->world->user);

    $this->other = $this->world->addBranch();

    $this->openDay = fn (int $branchId) => SaleDaySession::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $branchId,
        'opened_by' => $this->world->user->id,
        'opened_at' => now()->subHours(3),
        'status' => 'open',
    ]);

    $this->status = fn (array $query = []) => $this->getJson(
        $this->world->url('/api/v1/admin/day-status').($query ? '?'.http_build_query($query) : '')
    );
    $this->toggle = fn (array $query = []) => $this->postJson(
        $this->world->url('/api/v1/admin/day-status').($query ? '?'.http_build_query($query) : ''),
        ['date' => now()->toIso8601String()]
    );
});

it('reports the day of the branch the app is operating as', function (): void {
    ($this->openDay)($this->other->id);

    // No branch named: the cashier's own branch, which has no open day.
    ($this->status)()
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'closed');

    ($this->status)(['branch_id' => $this->other->id])
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.session.branch', $this->other->name);
});

it('opens the day for the operating branch, not the default one', function (): void {
    ($this->toggle)(['branch_id' => $this->other->id])
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'open');

    expect(SaleDaySession::hasOpenSession($this->other->id))->toBeTrue()
        ->and(SaleDaySession::hasOpenSession($this->world->branch->id))->toBeFalse();
});

it('closes the operating branch\'s day and leaves the default branch\'s day open', function (): void {
    $default = ($this->openDay)($this->world->branch->id);
    $other = ($this->openDay)($this->other->id);

    ($this->toggle)(['branch_id' => $this->other->id])
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'closed');

    expect($other->fresh()->status)->toBe('closed')
        ->and($default->fresh()->status)->toBe('open');
});

it('still runs the default branch when no branch is named', function (): void {
    ($this->toggle)()
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'open');

    expect(SaleDaySession::hasOpenSession($this->world->branch->id))->toBeTrue()
        ->and(SaleDaySession::hasOpenSession($this->other->id))->toBeFalse();
});

it('rejects a branch that does not exist', function (): void {
    ($this->status)(['branch_id' => 999999])->assertUnprocessable();
    ($this->toggle)(['branch_id' => 999999])->assertUnprocessable();

    expect(SaleDaySession::count())->toBe(0);
});

it('rejects a branch that belongs to another tenant', function (): void {
    // Inserted directly: BelongsToTenant would otherwise stamp the current
    // tenant onto it, which is the very case this guards against.
    $foreign = DB::table('branches')->insertGetId([
        'tenant_id' => Tenant::factory()->create()->id,
        'name' => 'Elsewhere',
        'code' => 'EW',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    ($this->status)(['branch_id' => $foreign])->assertUnprocessable();
    ($this->toggle)(['branch_id' => $foreign])->assertUnprocessable();

    expect(SaleDaySession::where('branch_id', $foreign)->count())->toBe(0);
});
