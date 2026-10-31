<?php

use App\Helpers\MoqSolutionsHelper;
use App\Livewire\ApiLog\MOQSettings;
use App\Models\ApiLog;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The MOQ "Test Connection" button posts a 1-riyal sale. It must only ever hit
 * a real sandbox, and every call must leave an api_logs row behind.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);

    config([
        'moq.username' => 'moq-user',
        'moq.password' => 'moq-pass',
        'moq.token' => 'moq-token',
        'moq.endpoint' => 'https://moq.test/live/postsales',
        'moq.endpoint_sandbox' => 'https://moq.test/sandbox/postsales',
    ]);
});

it('logs a successful test connection as Moq Test Connection', function (): void {
    Http::fake(['moq.test/sandbox/*' => Http::response(['status' => 'Posted'], 200)]);

    $result = (new MoqSolutionsHelper())->testConnection();

    $log = ApiLog::query()->find($result['api_log_id']);

    expect($result['success'])->toBeTrue()
        ->and($log)->not->toBeNull()
        ->and($log->service_name)->toBe('Moq Test Connection')
        ->and($log->endpoint)->toBe('https://moq.test/sandbox/postsales')
        ->and($log->status)->toBe('success')
        ->and($log->user_id)->toBe($this->world->user->id);

    Http::assertSent(fn ($request) => $request->url() === 'https://moq.test/sandbox/postsales');
});

it('logs a failed test connection with the HTTP status', function (): void {
    Http::fake(['moq.test/sandbox/*' => Http::response(['message' => 'Unauthorized'], 401)]);

    $result = (new MoqSolutionsHelper())->testConnection();

    $log = ApiLog::query()->find($result['api_log_id']);

    expect($result['success'])->toBeFalse()
        ->and($log->status)->toBe('failed')
        ->and($log->description)->toBe('HTTP 401');
});

it('refuses to test when the sandbox endpoint is the live endpoint', function (string $sandbox): void {
    config(['moq.endpoint_sandbox' => $sandbox]);
    Http::fake();
    $before = ApiLog::query()->count();

    $result = (new MoqSolutionsHelper())->testConnection();

    expect($result['success'])->toBeFalse()
        ->and(ApiLog::query()->count())->toBe($before);

    Http::assertNothingSent();
})->with([
    'same url' => 'https://moq.test/live/postsales',
    'same url with trailing slash' => 'https://moq.test/live/postsales/',
    'empty' => '',
]);

it('disables the Test Connection button when the sandbox is the live endpoint', function (): void {
    config(['moq.endpoint_sandbox' => 'https://moq.test/live/postsales']);

    Livewire::test(MOQSettings::class)
        ->assertSet('canTestConnection', false)
        ->assertSee('Test Connection is disabled');
});

it('enables the Test Connection button when a separate sandbox is configured', function (): void {
    Livewire::test(MOQSettings::class)
        ->assertSet('canTestConnection', true)
        ->assertDontSee('Test Connection is disabled');
});
