<?php

namespace App\Livewire\SaleDaySession;

use App\Models\Branch;
use App\Models\Sale;
use App\Models\SaleDaySession;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BranchSaleDaySessionManager extends Component
{
    public $branch_id;

    public $opening_amount = 0;

    public $closing_amount = 0;

    public $notes;

    public $openSessions = [];

    public $currentSession;

    public $sessionStats = [];

    protected $rules = [
        'opening_amount' => 'required|numeric|min:0',
        'closing_amount' => 'required|numeric|min:0',
        'notes' => 'nullable|string',
    ];

    public function mount()
    {
        $this->branch_id = Auth::user()->default_branch_id;
        $this->loadOpenSessions();
        $this->loadCurrentSession();
    }

    public function loadOpenSessions()
    {
        $this->openSessions = SaleDaySession::with(['branch', 'opener'])
            ->where('status', 'open')
            ->get();
    }

    public function loadCurrentSession()
    {
        if ($this->branch_id) {
            $this->currentSession = SaleDaySession::with(['branch', 'opener'])
                ->where('branch_id', $this->branch_id)
                ->where('status', 'open')
                ->first();

            if ($this->currentSession) {
                $this->calculateSessionStats();
            }
        }
    }

    public function calculateSessionStats()
    {
        if (! $this->currentSession) {
            return;
        }

        $sales = Sale::where('sale_day_session_id', $this->currentSession->id)->get();

        $this->sessionStats = [
            'total_sales' => $sales->count(),
            'total_amount' => $sales->sum('paid'),
            'opened_at' => $this->currentSession->opened_at->format('Y-m-d H:i:s'),
            'opened_by' => $this->currentSession->opener->name ?? 'Unknown',
            'opening_amount' => $this->currentSession->opening_amount,
            'expected_amount' => $this->currentSession->opening_amount + $sales->sum('paid'),
        ];

        // Set the default closing amount to the expected amount
        $this->closing_amount = $this->sessionStats['expected_amount'];
    }

    public function openDay()
    {
        $this->validate([
            'opening_amount' => 'required|numeric|min:0',
        ]);

        if (! $this->branch_id) {
            session()->flash('error', 'No branch selected.');

            return;
        }

        // Check if an open day session already exists for this branch
        if (SaleDaySession::hasOpenSession($this->branch_id)) {
            session()->flash('error', 'This branch already has an open day session.');

            return;
        }

        // Create new day session
        $session = SaleDaySession::create([
            'branch_id' => $this->branch_id,
            'opened_by' => Auth::id(),
            'opened_at' => now(),
            'opening_amount' => $this->opening_amount,
            'status' => 'open',
        ]);

        session()->flash('success', 'Day opened successfully.');

        // Reset the form and reload sessions
        $this->reset(['opening_amount']);
        $this->loadOpenSessions();
        $this->loadCurrentSession();
    }

    public function closeDay()
    {
        $this->validate([
            'closing_amount' => 'required|numeric|min:0',
        ]);

        if (! $this->currentSession) {
            session()->flash('error', 'No open day session found for this branch.');

            return;
        }

        // Close the day session
        $this->currentSession->close(
            $this->closing_amount,
            Auth::id(),
            $this->notes
        );

        session()->flash('success', 'Day closed successfully.');

        // Reset the form and reload sessions
        $this->reset(['closing_amount', 'notes']);
        $this->loadOpenSessions();
        $this->loadCurrentSession();
    }

    public function changeBranch($branchId)
    {
        $this->branch_id = $branchId;
        $this->loadCurrentSession();
    }

    public function render()
    {
        $branches = Auth::user()->branches()->with('branch')->get()->pluck('branch');

        return view('livewire.sale-day-session.branch-sale-day-session-manager', [
            'branches' => $branches,
        ]);
    }
}
