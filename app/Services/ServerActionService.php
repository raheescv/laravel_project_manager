<?php

namespace App\Services;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Deploy buttons on the Tenant Control Server tab: a fixed list of commands
 * run on this server as the web user, one at a time. Each run is launched
 * detached (nohup), so `npm run build` outlives the request that started it,
 * and writes its output to one log the Server tab polls.
 *
 * The one-time setup needs root, so it runs through `sudo -n`; without a
 * NOPASSWD sudoers rule for exactly that command it fails straight away and
 * the tab shows the line to add (sudoersLine()).
 */
class ServerActionService
{
    /** Appended by the shell once the command ends, followed by its exit code. */
    public const EXIT_MARKER = '__SERVER_ACTION_EXIT__:';

    /** Git never waits for a password or host-key prompt nobody can answer. */
    private const GIT_ENV = ['GIT_TERMINAL_PROMPT' => '0', 'GIT_SSH_COMMAND' => 'ssh -o StrictHostKeyChecking=no -o BatchMode=yes'];

    /** A run with no exit code after this long is treated as dead. */
    private const STALE_AFTER_MINUTES = 30;

    /**
     * @return array<string, array{label: string, icon: string, command: list<string>}>
     */
    public function actions(): array
    {
        $php = config('tenant_server.php_binary');

        return [
            'pull' => ['label' => 'Git pull', 'icon' => 'fa-code-fork', 'command' => ['git', 'pull', '--ff-only']],
            'build' => ['label' => 'npm run build', 'icon' => 'fa-cubes', 'command' => [config('tenant_server.npm_binary'), 'run', 'build']],
            'migrate' => ['label' => 'Migrate', 'icon' => 'fa-database', 'command' => [$php, 'artisan', 'migrate', '--force']],
            'setup' => ['label' => 'Server setup', 'icon' => 'fa-terminal', 'command' => ['sudo', '-n', $php, config('tenant_server.app_path').'/artisan', 'tenant:server-sync', '--shared']],
        ];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function start(string $key, string $startedBy): array
    {
        $action = $this->actions()[$key] ?? null;
        if (! $action) {
            return ['success' => false, 'message' => 'Unknown server action'];
        }

        $current = $this->current();
        if ($current && $current['running']) {
            return ['success' => false, 'message' => "{$current['label']} is still running — wait for it to finish"];
        }

        $path = config('tenant_server.app_path');
        $command = $this->commandLine($action['command']);

        File::ensureDirectoryExists($this->directory());
        File::put($this->logPath(), "$ {$command}\n\n");
        File::put($this->statePath(), json_encode([
            'key' => $key,
            'label' => $action['label'],
            'command' => $command,
            'started_at' => now()->toIso8601String(),
            'started_by' => $startedBy,
        ]));

        $script = 'cd '.escapeshellarg($path).' && '.$command.'; echo "'.self::EXIT_MARKER.'$?"';
        $launch = Process::path($path)
            ->env(self::GIT_ENV)
            ->run(['sh', '-c', 'nohup sh -c '.escapeshellarg($script).' >> '.escapeshellarg($this->logPath()).' 2>&1 < /dev/null &']);

        if ($launch->failed()) {
            File::append($this->logPath(), trim($launch->errorOutput())."\n".self::EXIT_MARKER.$launch->exitCode()."\n");

            return ['success' => false, 'message' => "Could not start {$action['label']}"];
        }

        return ['success' => true, 'message' => "{$action['label']} started"];
    }

    /**
     * The latest run, or null when nothing has run yet.
     *
     * @return array{key: string, label: string, command: string, started_at: Carbon, started_by: string, running: bool, exit_code: ?int, output: string}|null
     */
    public function current(): ?array
    {
        if (! File::exists($this->statePath())) {
            return null;
        }

        $state = json_decode(File::get($this->statePath()), true);
        if (! is_array($state) || ! isset($state['key'], $state['started_at'])) {
            return null;
        }

        $log = File::exists($this->logPath()) ? File::get($this->logPath()) : '';
        $exitCode = preg_match_all('/^'.preg_quote(self::EXIT_MARKER, '/').'(\d+)$/m', $log, $matches) ? (int) end($matches[1]) : null;
        $startedAt = Carbon::parse($state['started_at']);
        $output = (string) preg_replace(['/\e\[[\d;]*[A-Za-z]/', '/^'.preg_quote(self::EXIT_MARKER, '/').'\d+\R?/m'], '', $log);

        return [
            'key' => $state['key'],
            'label' => $state['label'] ?? $state['key'],
            'command' => $state['command'] ?? '',
            'started_at' => $startedAt,
            'started_by' => $state['started_by'] ?? '',
            'running' => $exitCode === null && $startedAt->gt(now()->subMinutes(self::STALE_AFTER_MINUTES)),
            'exit_code' => $exitCode,
            'output' => rtrim(mb_substr($output, -20000)),
        ];
    }

    /**
     * Fetch the upstream so pendingPull() can count what a pull would bring in.
     *
     * @return array{success: bool, message: string}
     */
    public function fetch(): array
    {
        try {
            $result = $this->git(['fetch', '--quiet'], 60);
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => 'git fetch failed: '.$th->getMessage()];
        }
        if ($result->failed()) {
            return ['success' => false, 'message' => 'git fetch failed: '.trim(mb_substr(trim($result->errorOutput() ?: $result->output()), -300))];
        }

        $pending = $this->pendingPull();

        return ['success' => true, 'message' => $pending['behind'] ? "{$pending['behind']} commit(s) to pull" : 'Already up to date'];
    }

    /**
     * What Git pull would bring in, read from the last fetch (no network).
     *
     * @return array{branch: string, upstream: string, behind: int, commits: list<array{hash: string, subject: string, author: string, when: string}>, fetched_at: ?Carbon, error: ?string}
     */
    public function pendingPull(): array
    {
        $pending = ['branch' => '', 'upstream' => '', 'behind' => 0, 'commits' => [], 'fetched_at' => null, 'error' => null];

        try {
            $upstream = $this->git(['rev-parse', '--abbrev-ref', 'HEAD', '@{u}']);
        } catch (\Throwable $th) {
            return [...$pending, 'error' => $th->getMessage()];
        }
        if ($upstream->failed()) {
            return [...$pending, 'error' => trim(mb_substr(trim($upstream->errorOutput()), -300)) ?: 'Not a git checkout with an upstream branch'];
        }
        [$branch, $tracking] = array_pad(preg_split('/\R/', trim($upstream->output())), 2, '');

        $log = $this->git(['log', '--format=%h%x09%s%x09%an%x09%cr', 'HEAD..@{u}'])->output();
        $commits = array_map(function (string $line): array {
            [$hash, $subject, $author, $when] = array_pad(explode("\t", $line, 4), 4, '');

            return ['hash' => $hash, 'subject' => $subject, 'author' => $author, 'when' => $when];
        }, array_values(array_filter(preg_split('/\R/', trim($log)))));

        $fetchHead = config('tenant_server.app_path').'/.git/FETCH_HEAD';

        return [
            ...$pending,
            'branch' => $branch,
            'upstream' => $tracking,
            'behind' => count($commits),
            'commits' => array_slice($commits, 0, 30),
            'fetched_at' => File::exists($fetchHead) ? Carbon::createFromTimestamp(File::lastModified($fetchHead)) : null,
        ];
    }

    /**
     * What to add with `visudo` so the web user may run the setup as root.
     */
    public function sudoersLine(): string
    {
        $setup = $this->actions()['setup']['command'];

        return config('tenant_server.web_user').' ALL=(root) NOPASSWD: '.implode(' ', array_slice($setup, 2));
    }

    public function logPath(): string
    {
        return $this->directory().'/current.log';
    }

    private function statePath(): string
    {
        return $this->directory().'/current.json';
    }

    private function directory(): string
    {
        return storage_path('app/server-actions');
    }

    /**
     * @param  list<string>  $arguments
     */
    private function git(array $arguments, int $timeout = 15): ProcessResult
    {
        return Process::path(config('tenant_server.app_path'))
            ->env(self::GIT_ENV)
            ->timeout($timeout)
            ->run(['git', ...$arguments]);
    }

    /**
     * @param  list<string>  $command
     */
    private function commandLine(array $command): string
    {
        return implode(' ', array_map(fn (string $part): string => preg_match('/^[\w.\/:=-]+$/', $part) ? $part : escapeshellarg($part), $command));
    }
}
