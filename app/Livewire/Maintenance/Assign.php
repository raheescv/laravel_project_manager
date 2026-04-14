<?php

namespace App\Livewire\Maintenance;

use App\Actions\Maintenance\Complaint\AssignAction;
use App\Actions\Maintenance\Complaint\CompleteAction;
use App\Actions\Maintenance\CompleteAction as MaintenanceCompleteAction;
use App\Models\Maintenance;
use App\Models\User;
use Livewire\Component;

class Assign extends Component
{
    public $maintenance_id;

    public $maintenance;

    public $complaintData = [];

    public function mount($id)
    {
        $this->maintenance_id = $id;
        $this->loadData();
    }

    public function loadData()
    {
        $this->maintenance = Maintenance::with([
            'property', 'building', 'group', 'customer', 'creator',
            'maintenanceComplaints.complaint.category',
            'maintenanceComplaints.technician',
        ])->find($this->maintenance_id);

        if (! $this->maintenance) {
            $this->dispatch('error', ['message' => 'Maintenance request not found.']);

            return;
        }

        $this->complaintData = $this->maintenance->maintenanceComplaints->map(function ($mc) {
            return [
                'id' => $mc->id,
                'complaint_name' => $mc->complaint?->name ?? 'N/A',
                'category_name' => $mc->complaint?->category?->name ?? 'N/A',
                'status' => $mc->status->value,
                'status_label' => $mc->status->label(),
                'status_color' => $mc->status->color(),
                'technician_id' => $mc->technician_id ?? '',
                'technician_name' => $mc->technician?->name ?? '',
                'technician_remark' => $mc->technician_remark ?? '',
            ];
        })->toArray();
    }

    public function assignTechnician($index)
    {
        try {
            $complaint = $this->complaintData[$index];
            if (empty($complaint['technician_id'])) {
                throw new \Exception('Please select a technician.', 1);
            }
            $response = (new AssignAction())->execute(
                $complaint['id'],
                $complaint['technician_id'],
                auth()->id()
            );
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->loadData();
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function completeComplaint($index)
    {
        try {
            $complaint = $this->complaintData[$index];
            $response = (new CompleteAction())->execute(
                $complaint['id'],
                auth()->id(),
                $complaint['technician_remark'] ?? null
            );
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->loadData();
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function completeMaintenance()
    {
        try {
            $response = (new MaintenanceCompleteAction())->execute($this->maintenance_id, auth()->id());
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->loadData();
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $technicians = User::orderBy('name')->get(['id', 'name']);

        return view('livewire.maintenance.assign', [
            'technicians' => $technicians,
        ]);
    }
}
