<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class EnsureDatabaseProceduresCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:ensure-procedures';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure all database stored procedures exist (useful after database restore)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Ensuring database procedures exist...');

        $migrationFiles = $this->getProcedureMigrationFiles();

        foreach ($migrationFiles as $migrationFile) {
            $this->runProcedureMigration($migrationFile);
        }

        $this->info('All procedures have been ensured.');
    }

    /**
     * Get all procedure migration files.
     *
     * @return array<string>
     */
    protected function getProcedureMigrationFiles(): array
    {
        $migrationsPath = database_path('migrations');
        $files = File::glob($migrationsPath.'/*procedure*.php');

        return $files;
    }

    /**
     * Run a procedure migration file.
     */
    protected function runProcedureMigration(string $migrationFile): void
    {
        try {
            $migration = require $migrationFile;

            if (! is_object($migration) || ! method_exists($migration, 'up')) {
                $this->warn("Skipping invalid migration: {$migrationFile}");

                return;
            }

            // Extract procedure name from migration for verification
            $procedureName = $this->extractProcedureName($migrationFile);

            // Run the migration's up() method which contains the procedure creation
            $migration->up();

            // Verify the procedure was created
            if ($procedureName) {
                $exists = DB::select(
                    'SHOW PROCEDURE STATUS WHERE Db = DATABASE() AND Name = ?',
                    [$procedureName]
                );

                if (count($exists) > 0) {
                    $this->info("✓ Procedure '{$procedureName}' ensured successfully.");
                } else {
                    $this->warn("⚠ Procedure '{$procedureName}' may not have been created.");
                }
            } else {
                $this->info('✓ Migration executed: '.basename($migrationFile));
            }
        } catch (\Exception $e) {
            $this->error("✗ Error running migration '{$migrationFile}': {$e->getMessage()}");
        }
    }

    /**
     * Extract procedure name from migration file content.
     */
    protected function extractProcedureName(string $migrationFile): ?string
    {
        $content = File::get($migrationFile);

        // Look for CREATE PROCEDURE pattern
        if (preg_match("/CREATE\s+PROCEDURE\s+(\w+)/i", $content, $matches)) {
            return $matches[1];
        }

        // Fallback: look for DROP PROCEDURE pattern
        if (preg_match("/DROP\s+PROCEDURE\s+IF\s+EXISTS\s+(\w+)/i", $content, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
