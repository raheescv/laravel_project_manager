<?php

namespace App\Livewire\General;

use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class BranchSelection extends Component
{
    public $branch_id;

    public function mount()
    {
        $this->branch_id = session('branch_id');
    }

    public function save()
    {
        Session::put('branch_id', $this->branch_id);

        $branch = Branch::find($this->branch_id);
        if ($branch) {
            Session::put('branch_code', $branch->code);
            Session::put('branch_name', $branch->name);
        }

        return redirect(url('/'));
    }

    public function render()
    {
        $branch_ids = Auth::user()->branches()->pluck('branch_id', 'branch_id')->toArray();
        $assigned_branches = Branch::whereIn('id', $branch_ids)->pluck('name', 'id')->toArray();

        return view('livewire.general.branch-selection')->with('assigned_branches', $assigned_branches);
    }
}
