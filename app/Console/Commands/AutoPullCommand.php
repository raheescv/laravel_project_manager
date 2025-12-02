<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class AutoPullCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'git:auto-pull
                            {branch=main : The branch to pull from}
                            {--build : Also build assets after pulling}
                            {--force : Force pull even if there are local changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically pull latest code from GitHub';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $branch = $this->argument('branch');
        $build = $this->option('build');
        $force = $this->option('force');

        $this->info("🔄 Auto-pulling code from branch: {$branch}");

        $projectDir = base_path();

        // Check if we're in a git repository
        if (! is_dir($projectDir.'/.git')) {
            $this->error('❌ Not a git repository');
            Log::error('Auto-pull: Not a git repository');

            return Command::FAILURE;
        }

        // Check for local changes
        $statusProcess = new Process(['git', 'status', '--porcelain'], $projectDir);
        $statusProcess->run();
        $hasChanges = ! empty(trim($statusProcess->getOutput()));

        if ($hasChanges && ! $force) {
            $this->warn('⚠️  Local changes detected. Use --force to pull anyway.');
            Log::warning('Auto-pull: Local changes detected, skipping pull');

            return Command::FAILURE;
        }

        if ($hasChanges && $force) {
            $this->warn('⚠️  Stashing local changes...');
            $stashProcess = new Process(['git', 'stash'], $projectDir);
            $stashProcess->run();
        }

        try {
            // Set up environment to bypass SSH passphrase prompt
            $env = [
                'GIT_SSH_COMMAND' => 'ssh -o StrictHostKeyChecking=no -o BatchMode=yes',
                'GIT_TERMINAL_PROMPT' => '0',
            ];

            // Try to use SSH agent if available
            $sshAuthSock = env('SSH_AUTH_SOCK');
            if ($sshAuthSock) {
                $env['SSH_AUTH_SOCK'] = $sshAuthSock;
            }

            // Fetch latest changes
            $this->info('📥 Fetching latest changes...');
            $fetchProcess = new Process(['git', 'fetch', 'origin'], $projectDir, $env);
            $fetchProcess->setTimeout(60);
            $fetchProcess->mustRun();

            // Get current branch
            $currentBranchProcess = new Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $projectDir);
            $currentBranchProcess->run();
            $currentBranch = trim($currentBranchProcess->getOutput());

            // Check if we need to switch branches
            if ($currentBranch !== $branch) {
                $this->info("🔄 Switching to branch: {$branch}");
                $checkoutProcess = new Process(['git', 'checkout', $branch], $projectDir, $env);
                $checkoutProcess->setTimeout(30);
                $checkoutProcess->mustRun();
            }

            // Pull the code
            $this->info("📥 Pulling code from origin/{$branch}...");
            $pullProcess = new Process(['git', 'pull', 'origin', $branch], $projectDir, $env);
            $pullProcess->setTimeout(120);
            $pullProcess->mustRun();

            // Show latest commit
            $logProcess = new Process(['git', 'log', '-1', '--oneline'], $projectDir);
            $logProcess->run();
            $latestCommit = trim($logProcess->getOutput());
            $this->info("✅ Latest commit: {$latestCommit}");

            // Build if requested
            if ($build) {
                $this->info('🔨 Building assets...');
                $buildScript = $projectDir.'/pull-and-build.sh';
                if (file_exists($buildScript)) {
                    $buildProcess = new Process(['bash', $buildScript, $branch, '--build'], $projectDir);
                    $buildProcess->setTimeout(300);
                    $buildProcess->mustRun();
                } else {

                    $this->warn('⚠️  Build script not found, running build manually...');
                    $this->runBuildCommands();
                }
            }

            $this->info('✅ Auto-pull completed successfully!');
            Log::info("Auto-pull: Successfully pulled code from branch {$branch}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());
            Log::error('Auto-pull failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Run build commands manually
     */
    private function runBuildCommands(): void
    {
        $projectDir = base_path();

        $this->info('📦 Installing npm dependencies...');
        $npmProcess = new Process(['npm', 'ci', '--prefer-offline', '--no-audit'], $projectDir);
        $npmProcess->setTimeout(300);
        if ($npmProcess->run() !== 0) {
            $npmProcess = new Process(['npm', 'install', '--prefer-offline', '--no-audit'], $projectDir);
            $npmProcess->setTimeout(300);
            $npmProcess->run();
        }

        $this->info('🔨 Building assets...');
        $buildProcess = new Process(['npm', 'run', 'build'], $projectDir);
        $buildProcess->setTimeout(300);
        $buildProcess->run();

        $this->info('🚀 Optimizing Laravel...');
        $commands = [
            ['php', 'artisan', 'optimize:clear'],
            ['php', 'artisan', 'config:cache'],
            ['php', 'artisan', 'route:cache'],
            ['php', 'artisan', 'view:cache'],
        ];

        foreach ($commands as $command) {
            $process = new Process($command, $projectDir);
            $process->setTimeout(60);
            $process->run();
        }
    }
}
