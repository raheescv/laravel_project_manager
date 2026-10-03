<?php

use App\Livewire\Settings\LoginPageSettings;
use App\Models\Configuration;
use App\Support\LoginScreen;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The Vue sign-in screen posts JSON to /login and follows the redirect it gets
 * back; Settings → Login Page picks its layout and live background per tenant.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->forceFill(['password' => bcrypt('secret-pass'), 'is_active' => true])->save();
});

function grantLoginSettings(): void
{
    $permission = config('permission.models.permission');
    test()->world->user->givePermissionTo($permission::firstOrCreate(['name' => 'configuration.settings', 'guard_name' => 'web']));
}

it('signs in over JSON and returns the dashboard as the redirect', function (): void {
    $this->postJson($this->world->url('/login'), ['login' => $this->world->user->email, 'password' => 'secret-pass'])
        ->assertSuccessful()
        ->assertJsonPath('redirect', route('dashboard'));

    $this->assertAuthenticatedAs($this->world->user);
    expect(session('branch_id'))->toBe($this->world->user->default_branch_id);
});

it('answers wrong credentials with a 422 on the login field', function (): void {
    $this->postJson($this->world->url('/login'), ['login' => $this->world->user->email, 'password' => 'wrong'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('login');

    $this->assertGuest();
});

it('refuses an inactive account over JSON', function (): void {
    $this->world->user->forceFill(['is_active' => false])->save();

    $this->postJson($this->world->url('/login'), ['login' => $this->world->user->email, 'password' => 'secret-pass'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['login' => 'inactive']);

    $this->assertGuest();
});

it('locks out after five misses with the seconds in the message', function (): void {
    RateLimiter::clear(strtolower($this->world->user->email).'|127.0.0.1');

    foreach (range(1, 5) as $attempt) {
        $this->postJson($this->world->url('/login'), ['login' => $this->world->user->email, 'password' => 'wrong']);
    }

    $this->postJson($this->world->url('/login'), ['login' => $this->world->user->email, 'password' => 'secret-pass'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['login' => 'seconds']);

    $this->assertGuest();
});

it('renders the configured layout and background', function (): void {
    Configuration::updateOrCreate(['key' => 'login_layout'], ['value' => 'frosted']);
    Configuration::updateOrCreate(['key' => 'login_background'], ['value' => 'grid']);

    $this->get($this->world->url('/login'))
        ->assertSuccessful()
        ->assertViewHas('screen', fn (array $screen): bool => $screen['layout'] === 'frosted'
            && $screen['background'] === 'grid'
            && $screen['preview'] === false);
});

it('resolves random to one of the real options', function (): void {
    $this->get($this->world->url('/login'))
        ->assertViewHas('screen', fn (array $screen): bool => array_key_exists($screen['layout'], LoginScreen::LAYOUTS)
            && array_key_exists($screen['background'], LoginScreen::BACKGROUNDS));
});

it('saves the choices from Settings → Login Page and ignores unknown values', function (): void {
    grantLoginSettings();
    $this->actingAs($this->world->user);

    Livewire::test(LoginPageSettings::class)
        ->assertSet('layout', 'random')
        ->call('setLayout', 'split')
        ->call('setBackground', 'network')
        ->assertSet('layout', 'split')
        ->assertSet('background', 'network')
        ->call('setBackground', 'aurora')
        ->assertSet('background', 'random');

    expect(LoginScreen::layoutSetting())->toBe('split')
        ->and(Configuration::where('key', 'login_background')->value('value'))->toBe('random');
});

it('forbids changing the login page without the settings permission', function (): void {
    $this->actingAs($this->world->user);

    Livewire::test(LoginPageSettings::class)
        ->call('setLayout', 'split')
        ->assertForbidden();
});

it('previews a chosen look for a signed-in admin with the form inert', function (): void {
    grantLoginSettings();

    $this->actingAs($this->world->user)
        ->get($this->world->url('/settings/login-preview?layout=split&background=globe'))
        ->assertSuccessful()
        ->assertViewHas('screen', fn (array $screen): bool => $screen['layout'] === 'split'
            && $screen['background'] === 'globe'
            && $screen['preview'] === true
            && $screen['prefill']['password'] === '');
});

it('uses the default copy when no system is chosen', function (): void {
    $this->get($this->world->url('/login'))
        ->assertViewHas('screen', fn (array $screen): bool => $screen['copy'] === config('modules.login.default'));
});

it('takes the sign-in copy from the tenant system type', function (): void {
    Configuration::updateOrCreate(['key' => 'active_module'], ['value' => 'Tailor Module']);

    $this->get($this->world->url('/login'))
        ->assertViewHas('screen', fn (array $screen): bool => $screen['copy']['headline'] === config('modules.login.Tailor Module.headline')
            && $screen['copy']['features'] === config('modules.login.Tailor Module.features'));
});

it('fills keys a system leaves out from the default copy', function (): void {
    config(['modules.login.POS Module' => ['headline' => 'Ring it up at']]);
    Configuration::updateOrCreate(['key' => 'active_module'], ['value' => 'POS Module']);

    expect(LoginScreen::copy())
        ->headline->toBe('Ring it up at')
        ->lede->toBe(config('modules.login.default.lede'))
        ->features->toBe(config('modules.login.default.features'));
});

it('has login copy for every system type', function (): void {
    foreach (array_keys(config('modules.systems')) as $system) {
        expect(config("modules.login.{$system}"))->toHaveKeys(['headline', 'highlight', 'lede', 'tagline', 'features'])
            ->and(config("modules.login.{$system}.features"))->toHaveCount(3);
    }
});
