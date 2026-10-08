<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    config(['app.debug' => false]);

    Route::get('/__server-error-test', fn () => throw new RuntimeException('Boom'));
});

it('renders the premium 500 page with a support reference', function (): void {
    $response = $this->get('/__server-error-test');

    $response->assertStatus(500)
        ->assertSee('Something Went Wrong')
        ->assertSee('Support Details')
        ->assertSee('Try Again')
        ->assertSee(errorReference())
        ->assertSee('/__server-error-test')
        ->assertDontSee('Boom');
});

it('logs the exception with the same reference the page shows', function (): void {
    Log::spy();

    $this->get('/__server-error-test')->assertStatus(500);

    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $message, array $context): bool => $message === 'Boom' && $context['reference'] === errorReference())
        ->once();
});
