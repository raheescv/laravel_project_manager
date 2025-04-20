<?php

namespace App\Console\Commands\SingleUse;

use App\Actions\User\BranchAction;
use App\Models\Branch;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeBranchAssignCommand extends Command
{
    protected $signature = 'app:employee-branch-assign-command';

    protected $description = 'Assign branches to employees based on Excel data';

    public function handle()
    {
        $filePath = public_path('employees/employees_branches.xlsx');

        if (! file_exists($filePath)) {
            $this->error("File not found: $filePath");

            return Command::FAILURE;
        }

        $sheets = Excel::toArray([], $filePath);
        $rows = $sheets[0] ?? [];

        foreach ($rows as $index => $row) {
            if ($index === 0 || count($row) < 3) {
                continue; // Skip header or invalid rows
            }

            [$_, $employeeName, $branchName] = $row;

            $employee = User::employee()->whereRaw('LOWER(name) = ?', [Str::lower($employeeName)])->first();

            if (! $employee) {
                Log::info("Employee not found: $employeeName");

                continue;
            }

            $branch = Branch::whereRaw('LOWER(name) = ?', [Str::lower($branchName)])->first();

            if (! $branch) {
                Log::info("Branch not found for employee {$employee->name}: $branchName");

                continue;
            }

            $existingBranchIds = $employee->branches->pluck('branch_id')->toArray();

            // Avoid duplicate IDs
            $branchIds = array_unique([...$existingBranchIds, $branch->id]);

            $response = (new BranchAction())->execute($employee->id, $branchIds);
            if (! $response['result']) {
                throw new Exception("Failed to assign branch for {$employee->name}: {$response['message']}");
            }

            Log::info("Assigned {$branch->name} to {$employee->name}");
        }

        $this->info('Employee-branch assignment completed.');

        return Command::SUCCESS;
    }
}
