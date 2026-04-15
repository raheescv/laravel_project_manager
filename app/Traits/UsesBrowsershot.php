<?php

namespace App\Traits;

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
                '--user-data-dir=/tmp/chrome-browsershot',
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
}
