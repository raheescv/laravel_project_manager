<?php

use App\Models\Sale;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use OwenIt\Auditing\Models\Audit;
use Tests\Support\PosWorld;

/**
 * Changes made through the mobile API authenticate with a Sanctum token, not
 * the web session, so the audit trail must resolve the user from the
 * `sanctum` guard too — otherwise every API change reads as "System".
 */
it('attributes audits of an API request to the token user', function (): void {
    $world = PosWorld::create();
    Sanctum::actingAs($world->user);

    $saleId = $this->postJson($world->url('/api/v1/sale'), $world->salePayload())
        ->assertSuccessful()
        ->json('data.id');

    $audit = Audit::query()
        ->where('auditable_type', Sale::class)
        ->where('auditable_id', $saleId)
        ->where('event', 'created')
        ->sole();

    expect($audit->user_id)->toBe($world->user->id)
        ->and($audit->user_type)->toBe((new User())->getMorphClass());
});
