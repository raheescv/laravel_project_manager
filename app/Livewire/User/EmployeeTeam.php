<?php

namespace App\Livewire\User;

use App\Actions\User\Team\CreateAction;
use App\Actions\User\Team\DeleteAction;
use App\Models\EmployeeTeam as EmployeeTeamModel;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * The employee view's Team tab: the employees whose leads this one sees on top
 * of their own while they lack 'property lead.view all'.
 */
class EmployeeTeam extends Component
{
    public $userId;

    public $team_member_id = '';

    public function mount($userId): void
    {
        $this->userId = $userId;
    }

    public function add(): void
    {
        abort_unless(auth()->user()?->can('employee.edit'), 403);
        try {
            DB::beginTransaction();
            $response = (new CreateAction())->execute([
                'employee_id' => (int) $this->userId,
                'team_member_id' => $this->team_member_id ?: null,
            ]);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            DB::commit();
            $this->team_member_id = '';
            $this->dispatch('EmployeeTeam-Reset-Select');
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function remove($id): void
    {
        abort_unless(auth()->user()?->can('employee.edit'), 403);
        try {
            DB::beginTransaction();
            $team = EmployeeTeamModel::where('employee_id', $this->userId)->find($id);
            if (! $team) {
                throw new Exception('Team member not found.', 1);
            }
            $response = (new DeleteAction())->execute($team->id);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function render()
    {
        $user = User::findOrFail($this->userId);
        $members = $user->teams()->with('member:id,name,code,mobile,designation_id', 'member.designation:id,name')->get();

        return view('livewire.user.employee-team', [
            'user' => $user,
            'members' => $members,
            'seesAllLeads' => ! $user->seesOnlyTeamLeads(),
        ]);
    }
}
