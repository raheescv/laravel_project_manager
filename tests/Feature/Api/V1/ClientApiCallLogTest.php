<?php

use App\Models\ApiLog;
use Tests\Support\PosWorld;

/**
 * First-party apps identify themselves with X-App-Name / X-App-Version on
 * every call; each such call lands in api_logs so support can tell which
 * build a shop is running. Untagged traffic is never logged.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
});

it('logs a tagged call with the app version and platform', function (): void {
    $this->withHeaders([
        'X-App-Name' => 'showcase',
        'X-App-Version' => '1.2.0',
        'X-App-Platform' => 'android',
    ])->getJson($this->world->url('/api/v1/branches?foo=bar'))->assertSuccessful();

    $log = ApiLog::query()->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->tenant_id)->toBe($this->world->tenant->id)
        ->and($log->endpoint)->toBe('api/v1/branches')
        ->and($log->method)->toBe('GET')
        ->and($log->service_name)->toBe('showcase')
        ->and($log->app_version)->toBe('1.2.0')
        ->and($log->app_platform)->toBe('android')
        ->and($log->status)->toBe('success')
        ->and($log->request)->toMatchArray(['foo' => 'bar'])
        ->and($log->response)->toBeNull();
});

it('marks a failed tagged call and keeps its response body', function (): void {
    $this->withHeaders(['X-App-Name' => 'showcase', 'X-App-Version' => '1.2.0'])
        ->getJson($this->world->url('/api/v1/products/999999999'));

    $log = ApiLog::query()->latest('id')->first();

    expect($log->status)->toBe('failed')
        ->and($log->response)->toBeArray();
});

it('does not log calls without an app name', function (): void {
    $before = ApiLog::query()->count();

    $this->getJson($this->world->url('/api/v1/branches'))->assertSuccessful();

    expect(ApiLog::query()->count())->toBe($before);
});
