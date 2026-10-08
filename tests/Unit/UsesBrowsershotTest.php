<?php

use App\Traits\UsesBrowsershot;
use Illuminate\Support\Facades\File;
use Spatie\Browsershot\Browsershot;

uses(Tests\TestCase::class);

/**
 * Chrome locks its profile directory, so every render needs its own — a shared
 * one makes concurrent renders fail with "SingletonLock: File exists".
 */
function browsershotProfileOf(Browsershot $browsershot): string
{
    $argument = collect($browsershot->createPdfCommand()['options']['args'])
        ->first(fn (string $argument) => str_starts_with($argument, '--user-data-dir='));

    return substr($argument, strlen('--user-data-dir='));
}

function browsershotRenderer(): object
{
    return new class()
    {
        use UsesBrowsershot;

        public function make(): Browsershot
        {
            return $this->makeBrowsershot('<p>pdf</p>');
        }
    };
}

it('gives every render its own chrome profile directory', function (): void {
    $renderer = browsershotRenderer();

    $first = browsershotProfileOf($renderer->make());
    $second = browsershotProfileOf($renderer->make());

    expect($first)->not->toBe($second)
        ->and($first)->toStartWith('/tmp/browsershot-profile-')
        ->and(File::isDirectory($first))->toBeTrue()
        ->and(File::isDirectory($second))->toBeTrue();

    File::deleteDirectory($first);
    File::deleteDirectory($second);
});

it('prunes profile directories left behind by earlier renders', function (): void {
    $stale = '/tmp/browsershot-profile-stale-test';
    $recent = '/tmp/browsershot-profile-recent-test';
    File::ensureDirectoryExists($stale);
    File::ensureDirectoryExists($recent);
    touch($stale, now()->subMinutes(11)->getTimestamp());

    $profile = browsershotProfileOf(browsershotRenderer()->make());

    expect(File::isDirectory($stale))->toBeFalse()
        ->and(File::isDirectory($recent))->toBeTrue();

    File::deleteDirectory($recent);
    File::deleteDirectory($profile);
});
