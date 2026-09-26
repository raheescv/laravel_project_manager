<?php

namespace App\Console\Commands\SingleUse;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RenamePermissionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:rename
                            {old_name : The old permission name to rename}
                            {new_name : The new permission name}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rename a permission (shared by every tenant) from old name to new name';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $oldName = $this->argument('old_name');
        $newName = $this->argument('new_name');

        if ($oldName === $newName) {
            $this->error('Old name and new name cannot be the same.');

            return 1;
        }

        $permissions = Permission::where('name', $oldName)->get();

        if ($permissions->isEmpty()) {
            $this->warn("No permissions found with name '{$oldName}'");

            return 0;
        }

        $conflicts = Permission::where('name', $newName)->whereIn('guard_name', $permissions->pluck('guard_name'))->exists();
        if ($conflicts) {
            $this->error("Permission '{$newName}' already exists.");

            return 1;
        }

        $this->table(
            ['ID', 'Old Name', 'New Name', 'Guard Name'],
            $permissions->map(fn (Permission $permission) => [$permission->id, $permission->name, $newName, $permission->guard_name])->toArray()
        );

        if (! $this->option('force') && ! $this->confirm('Do you want to proceed with renaming these permissions?')) {
            $this->info('Operation cancelled.');

            return 0;
        }

        $permissions->each(fn (Permission $permission) => $permission->update(['name' => $newName]));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Successfully renamed {$permissions->count()} permission(s) from '{$oldName}' to '{$newName}'");

        return 0;
    }
}
