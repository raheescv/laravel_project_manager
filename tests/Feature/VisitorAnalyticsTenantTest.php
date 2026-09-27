<?php

use App\Livewire\Analytics\VisitorAnalytics;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visitor;
use App\Services\TenantService;
use Livewire\Livewire;

function recordVisitorHit(int $tenantId, string $url, ?int $userId = null): void
{
    Visitor::insert([
        'tenant_id' => $tenantId,
        'user_id' => $userId,
        'ip_address' => '127.0.0.1',
        'url' => $url,
        'visited_at' => now(),
        'device_type' => 'desktop',
    ]);
}

it('only shows visitor analytics for the current tenant', function (): void {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();

    recordVisitorHit($tenant->id, 'https://app.test/sale');
    recordVisitorHit($tenant->id, 'https://app.test/sale');
    recordVisitorHit($otherTenant->id, 'https://app.test/other-tenant-page');

    app(TenantService::class)->setCurrentTenant($tenant);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $component = Livewire::actingAs($user)->test(VisitorAnalytics::class);

    expect($component->get('stats')['page_views'])->toBe(2)
        ->and(collect($component->get('popularPages'))->pluck('url')->all())->toBe(['https://app.test/sale']);
});

it('keeps visitor rows of other tenants out of model queries', function (): void {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();

    recordVisitorHit($tenant->id, 'https://app.test/mine');
    recordVisitorHit($otherTenant->id, 'https://app.test/theirs');

    app(TenantService::class)->setCurrentTenant($tenant);

    expect(Visitor::pluck('url')->all())->toBe(['https://app.test/mine'])
        ->and(Visitor::withoutTenant()->count())->toBe(2);
});
