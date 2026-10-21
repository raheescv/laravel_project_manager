<?php

use App\Models\Configuration;
use App\Support\TenantCache;
use Illuminate\Support\Facades\DB;
use Tests\Support\PosWorld;

/**
 * Cache::remember() never stores null, so an unset setting used to re-query on
 * every read — currency() reads two of them per money cell on every list.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    Configuration::where('key', 'currencies')->delete();
    TenantCache::forget('currencies');
});

it('remembers an unset setting instead of re-querying it on every read', function (): void {
    DB::enableQueryLog();
    $reads = collect(range(1, 5))->map(fn () => tenant_cache('currencies', []));
    $queries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'configurations'));
    DB::disableQueryLog();

    expect($reads->unique()->all())->toBe([[]])
        ->and($queries)->toHaveCount(1);
});

it('serves the new value once a setting is saved and forgotten', function (): void {
    expect(tenant_cache('currencies', []))->toBe([]);

    Configuration::updateOrCreate(['key' => 'currencies'], ['value' => json_encode([['code' => 'QAR']])]);
    TenantCache::forget('currencies');

    expect(tenant_cache('currencies', []))->toBe([['code' => 'QAR']]);
});
