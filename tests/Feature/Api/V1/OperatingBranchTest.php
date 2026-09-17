<?php

use App\Http\Resources\V1\Auth\AuthUserResource;
use App\Models\Sale;
use App\Models\User;
use App\Models\UserHasBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PosWorld;

/**
 * The mobile app asks a user with more than one assigned branch which one they
 * are working as, and books their sales there. The server honours that choice
 * only for a branch the cashier is actually assigned to.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->second = $this->world->addBranch('Market City', 'MC');
    Sanctum::actingAs($this->world->user);
});

function assignOperatingBranches(User $user, array $branchIds): void
{
    foreach ($branchIds as $branchId) {
        UserHasBranch::create(['user_id' => $user->id, 'branch_id' => $branchId]);
    }
}

it('hands the app the assigned branches at sign-in', function (): void {
    assignOperatingBranches($this->world->user, [$this->world->branch->id, $this->second->id]);

    $payload = (new AuthUserResource($this->world->user))->toArray(Request::create('/'));

    expect(collect($payload['branches'])->pluck('name')->all())->toBe(['Main Branch', 'Market City'])
        ->and($payload['branches'][0])->toHaveKeys(['id', 'name', 'code', 'location']);
});

it('offers only the default branch to an account with none assigned', function (): void {
    $payload = (new AuthUserResource($this->world->user))->toArray(Request::create('/'));

    expect(collect($payload['branches'])->pluck('id')->all())->toBe([$this->world->branch->id]);
});

it('books a sale to the branch the app is working as', function (): void {
    assignOperatingBranches($this->world->user, [$this->world->branch->id, $this->second->id]);

    $this->postJson($this->world->url('/api/v1/sale?branch_id='.$this->second->id), $this->world->salePayload())
        ->assertSuccessful();

    expect(Sale::withoutGlobalScopes()->value('branch_id'))->toBe($this->second->id);
});

it('falls back to the default branch for a branch the cashier is not assigned to', function (): void {
    assignOperatingBranches($this->world->user, [$this->world->branch->id]);

    $this->postJson($this->world->url('/api/v1/sale?branch_id='.$this->second->id), $this->world->salePayload())
        ->assertSuccessful();

    expect(Sale::withoutGlobalScopes()->value('branch_id'))->toBe($this->world->branch->id);
});

it('books a queued sale to the branch it was rung up under, not the one the till moved to', function (): void {
    assignOperatingBranches($this->world->user, [$this->world->branch->id, $this->second->id]);

    $this->postJson($this->world->url('/api/v1/sale?branch_id='.$this->world->branch->id), $this->world->salePayload([
        'clientUuid' => (string) Str::uuid(),
        'clientBranchId' => $this->second->id,
    ]))->assertSuccessful();

    expect(Sale::withoutGlobalScopes()->value('branch_id'))->toBe($this->second->id);
});

it('resolves the operating branch against the user assignments', function (): void {
    assignOperatingBranches($this->world->user, [$this->world->branch->id, $this->second->id]);
    $user = $this->world->user->fresh();

    expect($user->operatingBranchId($this->second->id))->toBe($this->second->id)
        ->and($user->operatingBranchId((string) $this->second->id))->toBe($this->second->id)
        ->and($user->operatingBranchId(999999))->toBe($this->world->branch->id)
        ->and($user->operatingBranchId(null))->toBe($this->world->branch->id);
});
