<?php

use App\Livewire\Settings\OnlinePayments;
use App\Models\Configuration;
use App\Support\Storefront\TapSettings;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Settings → Online Payments. The Tap secret key is the one value on this tab that
 * must never be readable from the database or rendered back to the browser.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->tapAccountId = $this->world->addPaymentMethod('Tap Payments');

    // `permissions` carries a tenant_id, so the row is built with one (see PermissionSeeder).
    $this->world->user->givePermissionTo(Permission::firstOrCreate([
        'tenant_id' => $this->world->tenant->id,
        'name' => 'configuration.settings',
        'guard_name' => 'web',
    ]));

    $this->actingAs($this->world->user);
});

it('stores the secret key encrypted and never renders it back', function (): void {
    Livewire::test(OnlinePayments::class)
        ->assertOk()
        ->assertSee('Online Payments')
        ->set('enabled', true)
        ->set('secret_key', 'sk_test_abcDEF1234567890wxyz')
        ->set('payment_account_id', (string) $this->tapAccountId)
        ->set('user_id', (string) $this->world->user->id)
        ->call('save')
        ->assertDispatched('success')
        ->assertSet('secret_key', '')
        ->assertSet('saved_key_hint', 'sk_test_…wxyz')
        ->assertSet('live_mode', false)
        ->assertDontSee('sk_test_abcDEF1234567890wxyz');

    $stored = Configuration::where('key', TapSettings::SECRET_KEY)->value('value');
    $settings = TapSettings::current();

    expect($stored)->not->toContain('abcDEF1234567890')
        ->and($settings->secretKey)->toBe('sk_test_abcDEF1234567890wxyz')
        ->and($settings->isReady())->toBeTrue();
});

it('keeps the saved key when the field is left blank', function (): void {
    Configuration::create([
        'tenant_id' => $this->world->tenant->id,
        'key' => TapSettings::SECRET_KEY,
        'value' => TapSettings::encryptSecret('sk_live_savedKey9876'),
    ]);

    Livewire::test(OnlinePayments::class)
        ->assertSet('live_mode', true)
        ->set('enabled', true)
        ->set('payment_account_id', (string) $this->tapAccountId)
        ->set('user_id', (string) $this->world->user->id)
        ->call('save')
        ->assertDispatched('success');

    expect(TapSettings::current()->secretKey)->toBe('sk_live_savedKey9876')
        ->and(TapSettings::current()->enabled)->toBeTrue();
});

it('will not switch on without a key', function (): void {
    Livewire::test(OnlinePayments::class)
        ->set('enabled', true)
        ->set('payment_account_id', (string) $this->tapAccountId)
        ->set('user_id', (string) $this->world->user->id)
        ->call('save')
        ->assertDispatched('error');

    expect(Configuration::where('key', TapSettings::KEY)->exists())->toBeFalse();
});

it('refuses a public key typed into the secret field', function (): void {
    Livewire::test(OnlinePayments::class)
        ->set('secret_key', 'pk_test_publicKey123')
        ->call('save')
        ->assertDispatched('error');

    expect(Configuration::where('key', TapSettings::SECRET_KEY)->exists())->toBeFalse();
});

it('switches online payments off when the key is removed', function (): void {
    Configuration::create([
        'tenant_id' => $this->world->tenant->id,
        'key' => TapSettings::SECRET_KEY,
        'value' => TapSettings::encryptSecret('sk_test_savedKey1234'),
    ]);
    Configuration::create([
        'tenant_id' => $this->world->tenant->id,
        'key' => TapSettings::KEY,
        'value' => json_encode(['enabled' => true, 'payment_account_id' => $this->tapAccountId, 'user_id' => $this->world->user->id]),
    ]);

    Livewire::test(OnlinePayments::class)
        ->assertSet('enabled', true)
        ->call('removeSecretKey')
        ->assertSet('enabled', false)
        ->assertSet('saved_key_hint', null);

    expect(TapSettings::current()->secretKey)->toBeNull()
        ->and(TapSettings::current()->enabled)->toBeFalse()
        ->and(TapSettings::current()->paymentAccountId)->toBe($this->tapAccountId);
});
