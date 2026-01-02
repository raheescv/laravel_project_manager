<?php

namespace App\Actions\EmployeeInventory;

use App\Actions\Product\Inventory\CreateAction;
use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;
use App\Models\User;
use Exception;

class TransferAction
{
    public function execute($data, $userId)
    {
        try {
            // Validate required fields
            if (! isset($data['inventory_id'])) {
                throw new Exception('Inventory ID is required', 1);
            }
            if (! isset($data['quantity']) || $data['quantity'] <= 0) {
                throw new Exception('Valid quantity is required', 1);
            }
            if (! isset($data['reason']) || empty(trim($data['reason']))) {
                throw new Exception('Reason is required', 1);
            }

            // Get source inventory
            $sourceInventory = Inventory::find($data['inventory_id']);
            if (! $sourceInventory) {
                throw new Exception('Source inventory not found', 1);
            }

            // Determine operation based on employee_id presence
            $isTransfer = isset($data['employee_id']) && ! empty($data['employee_id']);

            if ($isTransfer) {
                // TRANSFER: Branch to Employee
                // Ensure source inventory is branch inventory (not already assigned to employee)
                if ($sourceInventory->employee_id !== null) {
                    // throw new Exception('Source inventory is already assigned to an employee. Please select branch inventory.', 1);
                }
                // Ensure source inventory is branch inventory (not already assigned to employee)
                if ($sourceInventory->employee_id == $data['employee_id']) {
                    throw new Exception('Please select different Employee.', 1);
                }

                // Validate sufficient quantity available
                if ($sourceInventory->quantity < $data['quantity']) {
                    throw new Exception('Insufficient quantity available. Available: '.$sourceInventory->quantity, 1);
                }

                // Get employee details
                $employee = User::find($data['employee_id']);
                if (! $employee) {
                    // throw new Exception('Employee not found', 1);
                }

                // Get branch name for remarks
                $branchName = $sourceInventory->branch->name ?? 'Branch';

                // Step 1: Reduce quantity from source inventory (branch inventory)
                $sourceData = $sourceInventory->toArray();
                $sourceData['quantity'] -= $data['quantity'];
                $sourceData['model'] = 'EmployeeInventory';
                $sourceData['model_id'] = $data['employee_id'];
                $sourceData['remarks'] = 'Employee Transfer: '.trim($data['reason']).' - To: '.$employee->name;
                $sourceData['updated_by'] = $userId;

                $response = (new UpdateAction())->execute($sourceData, $sourceInventory->id);
                if (! $response['success']) {
                    throw new Exception('Failed to update source inventory: '.$response['message'], 1);
                }

                // Step 2: Find or create employee inventory
                $employeeInventory = Inventory::withoutGlobalScopes()
                    ->where('product_id', $sourceInventory->product_id)
                    ->where('branch_id', $sourceInventory->branch_id)
                    ->where('employee_id', $data['employee_id'])
                    ->first();

                if (! $employeeInventory) {
                    // Create new employee inventory
                    $employeeData = $sourceInventory->toArray();
                    $employeeData['employee_id'] = $data['employee_id'];
                    $employeeData['quantity'] = $data['quantity'];
                    $employeeData['model'] = 'EmployeeInventory';
                    $employeeData['model_id'] = $data['employee_id'];
                    $employeeData['remarks'] = 'Employee Transfer: '.trim($data['reason']).' - From: '.$branchName;
                    $employeeData['created_by'] = $employeeData['updated_by'] = $userId;

                    $response = (new CreateAction())->execute($employeeData);
                    if (! $response['success']) {
                        throw new Exception('Failed to create employee inventory: '.$response['message'], 1);
                    }
                    $employeeInventory = $response['data'];
                } else {
                    // Update existing employee inventory
                    $employeeData = $employeeInventory->toArray();
                    $employeeData['quantity'] += $data['quantity'];
                    $employeeData['model'] = 'EmployeeInventory';
                    $employeeData['model_id'] = $data['employee_id'];
                    $employeeData['remarks'] = 'Employee Transfer: '.trim($data['reason']).' - From: '.$branchName;
                    $employeeData['updated_by'] = $userId;

                    $response = (new UpdateAction())->execute($employeeData, $employeeInventory->id);
                    if (! $response['success']) {
                        throw new Exception('Failed to update employee inventory: '.$response['message'], 1);
                    }
                }

                $return['success'] = true;
                $return['message'] = 'Successfully transferred '.$data['quantity'].' units to '.$employee->name;
                $return['data'] = [
                    'source_inventory' => $sourceInventory->fresh(),
                    'employee_inventory' => $employeeInventory->fresh(),
                ];
            } else {
                // RETURN: Employee to Branch
                // Ensure source inventory is employee inventory
                if ($sourceInventory->employee_id === null) {
                    throw new Exception('Source inventory is not assigned to an employee. Please select employee inventory.', 1);
                }

                // Validate sufficient quantity available
                if ($sourceInventory->quantity < $data['quantity']) {
                    throw new Exception('Insufficient quantity available. Available: '.$sourceInventory->quantity, 1);
                }

                // Get employee details
                $employee = User::find($sourceInventory->employee_id);
                if (! $employee) {
                    throw new Exception('Employee not found', 1);
                }

                // Get branch name for remarks
                $branchName = $sourceInventory->branch->name ?? 'Branch';

                // Step 1: Reduce quantity from employee inventory
                $sourceData = $sourceInventory->toArray();
                $sourceData['quantity'] -= $data['quantity'];
                $sourceData['model'] = 'EmployeeInventory';
                $sourceData['model_id'] = $sourceInventory->employee_id;
                $sourceData['remarks'] = 'Employee Return: '.trim($data['reason']).' - From: '.$employee->name;
                $sourceData['updated_by'] = $userId;

                $response = (new UpdateAction())->execute($sourceData, $sourceInventory->id);
                if (! $response['success']) {
                    throw new Exception('Failed to update employee inventory: '.$response['message'], 1);
                }

                // Step 2: Find or create branch inventory (employee_id = null)
                $branchInventory = Inventory::withoutGlobalScopes()
                    ->where('product_id', $sourceInventory->product_id)
                    ->where('branch_id', $sourceInventory->branch_id)
                    ->whereNull('employee_id')
                    ->first();

                if (! $branchInventory) {
                    // Create new branch inventory
                    $branchData = $sourceInventory->toArray();
                    $branchData['employee_id'] = null;
                    $branchData['quantity'] = $data['quantity'];
                    $branchData['model'] = 'EmployeeInventory';
                    $branchData['model_id'] = $sourceInventory->employee_id;
                    $branchData['remarks'] = 'Employee Return: '.trim($data['reason']).' - Returned by: '.$employee->name;
                    $branchData['created_by'] = $branchData['updated_by'] = $userId;

                    $response = (new CreateAction())->execute($branchData);
                    if (! $response['success']) {
                        throw new Exception('Failed to create branch inventory: '.$response['message'], 1);
                    }
                    $branchInventory = $response['data'];
                } else {
                    // Update existing branch inventory
                    $branchData = $branchInventory->toArray();
                    $branchData['quantity'] += $data['quantity'];
                    $branchData['model'] = 'EmployeeInventory';
                    $branchData['model_id'] = $sourceInventory->employee_id;
                    $branchData['remarks'] = 'Employee Return: '.trim($data['reason']).' - Returned by: '.$employee->name;
                    $branchData['updated_by'] = $userId;

                    $response = (new UpdateAction())->execute($branchData, $branchInventory->id);
                    if (! $response['success']) {
                        throw new Exception('Failed to update branch inventory: '.$response['message'], 1);
                    }
                }

                $return['success'] = true;
                $return['message'] = 'Successfully returned '.$data['quantity'].' units from '.$employee->name.' to branch inventory';
                $return['data'] = [
                    'source_inventory' => $sourceInventory->fresh(),
                    'branch_inventory' => $branchInventory->fresh(),
                ];
            }
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
