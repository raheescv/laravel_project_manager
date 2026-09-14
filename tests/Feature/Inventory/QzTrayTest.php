<?php

use App\Support\QzTray;
use Illuminate\Support\Facades\File;
use Tests\Support\PosWorld;

/**
 * QZ Tray prints labels without the browser dialog only for requests signed by a
 * certificate it trusts. The server keeps the key and signs on demand.
 */
beforeEach(function (): void {
    $this->qzDir = sys_get_temp_dir().'/qz-tray-test-'.uniqid();
    config([
        'services.qz.certificate' => $this->qzDir.'/digital-certificate.txt',
        'services.qz.private_key' => $this->qzDir.'/private-key.pem',
    ]);

    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
});

afterEach(function (): void {
    File::deleteDirectory($this->qzDir);
});

it('signs a request so it verifies against the certificate it serves', function (): void {
    QzTray::generate('Test Shop');

    $certificate = $this->get(route('inventory::barcode::qz::certificate'))->assertOk()->getContent();
    $signature = $this->postJson(route('inventory::barcode::qz::sign'), ['request' => 'a1b2c3'])->assertOk()->getContent();

    expect(openssl_verify('a1b2c3', base64_decode($signature), openssl_pkey_get_public($certificate), OPENSSL_ALGO_SHA512))->toBe(1);
});

it('generates a CA certificate QZ Tray can use as its override root', function (): void {
    QzTray::generate('Test Shop');

    $parsed = openssl_x509_parse(QzTray::certificate());

    expect($parsed['subject']['O'])->toBe('Test Shop')
        ->and($parsed['extensions']['basicConstraints'])->toContain('CA:TRUE')
        ->and(substr(sprintf('%o', fileperms(QzTray::privateKeyPath())), -4))->toBe('0600');
});

it('offers the certificate as override.crt for the printing PCs', function (): void {
    QzTray::generate('Test Shop');

    $this->get(route('inventory::barcode::qz::certificate', ['download' => 1]))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="override.crt"');
});

it('answers 404 until the certificate has been generated', function (): void {
    $this->get(route('inventory::barcode::qz::certificate'))->assertNotFound();
    $this->postJson(route('inventory::barcode::qz::sign'), ['request' => 'a1b2c3'])->assertNotFound();
});

it('signs only for signed-in users', function (): void {
    QzTray::generate('Test Shop');
    auth()->logout();

    $this->postJson(route('inventory::barcode::qz::sign'), ['request' => 'a1b2c3'])->assertUnauthorized();
});
