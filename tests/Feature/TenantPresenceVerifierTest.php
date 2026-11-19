<?php

use App\Services\TenantService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\Support\PosWorld;

/**
 * exists/unique validation rules are narrowed to the current tenant, so a
 * crafted id from another tenant is rejected and values used by another
 * tenant stay available.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->other = PosWorld::create();
    $this->world = PosWorld::create();
});

it('rejects an id that belongs to another tenant', function (string|object $rule): void {
    expect(Validator::make(['id' => $this->other->product->id], ['id' => [$rule]])->fails())->toBeTrue()
        ->and(Validator::make(['id' => $this->world->product->id], ['id' => [$rule]])->passes())->toBeTrue();
})->with([
    'string rule' => ['exists:products,id'],
    'Rule object' => [Rule::exists('products', 'id')],
]);

it('allows a unique value that only another tenant uses', function (): void {
    $otherEmail = $this->other->user->email;

    expect(Validator::make(['email' => $otherEmail], ['email' => 'unique:users,email'])->passes())->toBeTrue()
        ->and(Validator::make(['email' => $this->world->user->email], ['email' => 'unique:users,email'])->fails())->toBeTrue();
});

it('checks every tenant when no tenant is resolved', function (): void {
    app(TenantService::class)->clearCurrentTenant();

    expect(Validator::make(['id' => $this->other->product->id], ['id' => 'exists:products,id'])->passes())->toBeTrue();
});
