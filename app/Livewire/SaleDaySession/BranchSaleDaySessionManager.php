<?php

namespace App\Livewire\SaleDaySession;

use App\Helpers\Facades\MoqSolutionsHelper;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\SaleDaySession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class BranchSaleDaySessionManager extends Component
{
    public $branch_id;

    public $date;

    public $opening_amount = 0;

    public $closing_amount = 0;

    public $sync_amount = 0;

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
        $this->branch_id = session('branch_id');
        $this->date = now()->toDateString();
        $this->loadOpenSessions();
        $this->loadCurrentSession();
    }

    public function updatedClosingAmount()
    {
        // $this->sync_amount = $this->closing_amount;
        // $this->sync_amount = 0;
    }

    public function loadOpenSessions()
    {
        $this->openSessions = SaleDaySession::with(['branch', 'opener'])->open()->get();
    }

    public function loadCurrentSession()
    {
        if ($this->branch_id) {
            $this->currentSession = SaleDaySession::with(['branch', 'opener'])
                ->where('branch_id', $this->branch_id)
                ->open()
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

        $sales = Sale::completed()->where('sale_day_session_id', $this->currentSession->id)->get();

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
        // $this->sync_amount = $this->closing_amount;
        // $this->sync_amount = 0;
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
        // Ensure no open session already exists for the selected date
        $existsForDate = SaleDaySession::where('branch_id', $this->branch_id)
            ->open()
            ->whereDate('opened_at', $this->date)
            ->exists();
        if ($existsForDate) {
            session()->flash('error', 'This branch already has an opened a session for the selected date.');

            return;
        }
        $existsForDate = SaleDaySession::where('branch_id', $this->branch_id)
            ->whereDate('opened_at', $this->date)
            ->first();
        if (! empty($existsForDate)) {
            $existsForDate->update(['closed_at' => null, 'closed_by' => null, 'status' => 'open']);
        } else {
            // Create new day session
            SaleDaySession::create([
                'branch_id' => $this->branch_id,
                'opened_by' => Auth::id(),
                'opened_at' => date('Y-m-d H:i:s', strtotime($this->date)),
                'opening_amount' => $this->opening_amount,
                'status' => 'open',
            ]);
        }

        session()->flash('success', 'Day opened successfully.');

        // Reset the form and reload sessions
        $this->reset(['opening_amount']);
        $this->loadOpenSessions();
        $this->loadCurrentSession();
    }

    public function closeDay()
    {
        try {
            DB::beginTransaction();
            $this->validate([
                'closing_amount' => 'required|numeric|min:0',
                'sync_amount' => 'required|numeric|min:0',
            ]);

            if (! $this->currentSession) {
                session()->flash('error', 'No open day session found for this branch.');

                return;
            }

            // Close the day session
            $this->currentSession->close($this->closing_amount, $this->sync_amount, Auth::id(), $this->notes);

            if ($this->currentSession->branch->moq_sync) {
                $syncData = [
                    'Date' => $this->currentSession->opened_at->format('Y-m-d'),
                    'Revenue' => floatval($this->sync_amount),
                    'Outlet' => config('app.name').' '.$this->currentSession->branch->name,
                ];
                $result = MoqSolutionsHelper::syncDayCloseAmount($syncData);
                if (! $result['success']) {
                    throw new \Exception('Failed to close day: '.$result['error']);
                }
            }

            // Reset the form and reload sessions
            $this->reset(['closing_amount', 'notes']);
            $this->loadOpenSessions();
            $this->loadCurrentSession(); // code...
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollback();
            session()->flash('error', 'Failed to close day: '.$th->getMessage());
        }
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
