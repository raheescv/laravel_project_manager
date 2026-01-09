<?php

namespace App\Console\Commands\SingleUse;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

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
                            {--tenant= : Specific tenant ID to update (optional)}
                            {--all-tenants : Update across all tenants}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rename a permission from old name to new name';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $oldName = $this->argument('old_name');
        $newName = $this->argument('new_name');
        $tenantId = $this->option('tenant');
        $allTenants = $this->option('all-tenants');

        // Validate inputs
        if (empty($oldName) || empty($newName)) {
            $this->error('Both old_name and new_name are required.');

            return 1;
        }

        if ($oldName === $newName) {
            $this->error('Old name and new name cannot be the same.');

            return 1;
        }

        // Build query
        $query = Permission::withoutGlobalScopes()->where('name', $oldName);

        if ($allTenants) {
            // Update across all tenants
            $permissions = $query->get();
        } elseif ($tenantId) {
            // Update for specific tenant
            $permissions = $query->where('tenant_id', $tenantId)->get();
        } else {
            // Default: update for all tenants (same as --all-tenants)
            $permissions = $query->get();
        }

        if ($permissions->isEmpty()) {
            $this->warn("No permissions found with name '{$oldName}'");

            if ($tenantId) {
                $this->info("Checked for tenant ID: {$tenantId}");
            }

            return 0;
        }

        // Check if new name already exists for any of the tenants
        $conflictingTenants = [];
        foreach ($permissions as $permission) {
            $exists = Permission::withoutGlobalScopes()
                ->where('tenant_id', $permission->tenant_id)
                ->where('name', $newName)
                ->where('guard_name', $permission->guard_name)
                ->exists();

            if ($exists) {
                $conflictingTenants[] = $permission->tenant_id;
            }
        }

        if (! empty($conflictingTenants)) {
            $this->error("Permission '{$newName}' already exists for tenant(s): ".implode(', ', array_unique($conflictingTenants)));

            return 1;
        }

        // Show summary
        $this->info("Found {$permissions->count()} permission(s) to rename:");
        $this->table(
            ['ID', 'Tenant ID', 'Old Name', 'New Name', 'Guard Name'],
            $permissions->map(function ($permission) use ($newName) {
                return [
                    $permission->id,
                    $permission->tenant_id,
                    $permission->name,
                    $newName,
                    $permission->guard_name,
                ];
            })->toArray()
        );

        // Confirm before proceeding
        if (! $this->option('force')) {
            if (! $this->confirm('Do you want to proceed with renaming these permissions?')) {
                $this->info('Operation cancelled.');

                return 0;
            }
        }

        // Perform the rename
        $updated = 0;
        foreach ($permissions as $permission) {
            try {
                $permission->name = $newName;
                $permission->save();
                $updated++;
            } catch (\Exception $e) {
                $this->error("Failed to update permission ID {$permission->id}: {$e->getMessage()}");
            }
        }

        if ($updated > 0) {
            $this->info("Successfully renamed {$updated} permission(s) from '{$oldName}' to '{$newName}'");
        }

        return 0;
    }
}

