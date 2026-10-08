<?php

namespace App\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

trait UsesBrowsershot
{
    private function makeBrowsershot(string $html): Browsershot
    {
        putenv('HOME=/tmp');
        putenv('CHROME_CRASHPAD_PIPE_NAME=');
        putenv('BREAKPAD_DUMP_LOCATION=/tmp');

        $detect = fn (string $cmd) => trim((string) shell_exec($cmd)) ?: null;

        $node = config('browsershot.node_binary') ?: $detect('which node');
        $npm = config('browsershot.npm_binary') ?: $detect('which npm');
        $chrome = config('browsershot.chrome_path') ?: $detect('which google-chrome || which chromium-browser || which chromium');

        $instance = Browsershot::html($html)
            ->noSandbox()
            ->ignoreHttpsErrors()
            ->disableJavascript()
            ->blockDomains(['*'])
            ->setOption('args', [
                '--disable-web-security',
                '--no-sandbox',
                '--disable-gpu',
                '--disable-dev-shm-usage',
                '--disable-software-rasterizer',
                '--disable-breakpad',
                '--crash-dumps-dir=/tmp',
                '--no-zygote',
                '--user-data-dir='.$this->browsershotProfileDirectory(),
            ])
            ->margins(0, 0, 0, 0)
            ->deviceScaleFactor(1);

        if ($node) {
            $instance->setNodeBinary($node);
        }
        if ($npm) {
            $instance->setNpmBinary($npm);
        }
        if ($chrome) {
            $instance->setChromePath($chrome);
        }

        return $instance;
    }

    /**
     * A fresh Chrome profile per render: a shared one makes concurrent renders
     * fail on the profile's SingletonLock. Profiles older than ten minutes are
     * leftovers of finished renders and are pruned.
     */
    private function browsershotProfileDirectory(): string
    {
        foreach (File::glob('/tmp/browsershot-profile-*', GLOB_ONLYDIR) as $directory) {
            if (@filemtime($directory) < now()->subMinutes(10)->getTimestamp()) {
                File::deleteDirectory($directory);
            }
        }

        $directory = '/tmp/browsershot-profile-'.Str::uuid();
        File::ensureDirectoryExists($directory);

        return $directory;
    }
}
