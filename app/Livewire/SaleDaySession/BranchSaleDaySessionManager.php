<?php

namespace App\Livewire\SaleDaySession;

use App\Helpers\Facades\MoqSolutionsHelper;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\SaleDaySession;
use App\Models\TailoringOrder;
use App\Models\TailoringPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class BranchSaleDaySessionManager extends Component
{
    public $branch_id;

    public $date;

    /** Wall-clock time (H:i) paired with $date when a session is opened. Defaults to now. */
    public $opening_time;

    /** Wall-clock time (H:i) paired with $date when a session is closed. Defaults to now. */
    public $closing_time;

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
        $this->resetTimeDefaults();
        $this->loadOpenSessions();
        $this->loadCurrentSession();
    }

    /** Both time pickers start at the current clock time (the business date already starts at today). */
    protected function resetTimeDefaults(): void
    {
        $this->opening_time = now()->format('H:i');
        $this->closing_time = now()->format('H:i');
    }

    /** Combine the business date with a wall-clock time into one moment. */
    protected function momentFor(string $time): Carbon
    {
        return Carbon::parse($this->date.' '.$time);
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
        $tailoringOrders = TailoringOrder::where('sale_day_session_id', $this->currentSession->id)->get();
        $tailoringPaymentsAmount = TailoringPayment::whereHas('order', function ($query) {
            $query->where('sale_day_session_id', $this->currentSession->id);
        })->sum('amount');
        $salesAmount = $sales->sum('paid');
        $totalAmount = $salesAmount + $tailoringPaymentsAmount;

        $this->sessionStats = [
            'total_sales' => $sales->count(),
            'total_tailoring_orders' => $tailoringOrders->count(),
            'total_tailoring_amount' => $tailoringPaymentsAmount,
            'total_amount' => $totalAmount,
            'opened_at' => $this->currentSession->opened_at->format('Y-m-d H:i:s'),
            'opened_by' => $this->currentSession->opener->name ?? 'Unknown',
            'opening_amount' => $this->currentSession->opening_amount,
            'expected_amount' => $this->currentSession->opening_amount + $totalAmount,
        ];

        // Set the default closing amount to the expected amount
        $this->closing_amount = $this->sessionStats['expected_amount'];
        // $this->sync_amount = $this->closing_amount;
        // $this->sync_amount = 0;
    }

    public function openDay()
    {
        $this->validate([
            'date' => 'required|date',
            'opening_time' => 'required|date_format:H:i',
            'opening_amount' => 'required|numeric|min:0',
        ]);

        if (! $this->branch_id) {
            session()->flash('error', 'No branch selected.');

            return;
        }

        // Same tolerance as the mobile ToggleRequest: a few minutes of latency / clock skew is fine, hours ahead is not.
        $openedAt = $this->momentFor($this->opening_time);
        if ($openedAt->gt(now()->addMinutes(5))) {
            $this->addError('opening_time', 'The opening time cannot be in the future.');

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
            $data = [
                'opening_amount' => $this->opening_amount,
                'closed_at' => null,
                'closed_by' => null,
                'status' => 'open',
            ];
            $existsForDate->update($data);
        } else {
            // Create new day session
            SaleDaySession::create([
                'branch_id' => $this->branch_id,
                'opened_by' => Auth::id(),
                'opened_at' => $openedAt->toDateTimeString(),
                'opening_amount' => $this->opening_amount,
                'status' => 'open',
            ]);
        }

        session()->flash('success', 'Day opened successfully.');

        // Reset the form and reload sessions
        $this->reset(['opening_amount']);
        $this->resetTimeDefaults();
        $this->loadOpenSessions();
        $this->loadCurrentSession();
    }

    public function closeDay()
    {
        if (! $this->currentSession) {
            session()->flash('error', 'No open day session found for this branch.');

            return;
        }

        $moqSync = (bool) $this->currentSession->branch?->moq_sync;

        $this->validate([
            'date' => 'required|date',
            'closing_time' => 'required|date_format:H:i',
            'closing_amount' => 'required|numeric|min:0',
            'sync_amount' => $moqSync ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
        ]);

        $closedAt = $this->momentFor($this->closing_time);
        if ($closedAt->gt(now()->addMinutes(5))) {
            $this->addError('closing_time', 'The closing time cannot be in the future.');

            return;
        }
        if ($closedAt->lt($this->currentSession->opened_at)) {
            $this->addError('closing_time', 'The closing time must be on or after the opening ('.$this->currentSession->opened_at->format('d M Y, g:i A').').');

            return;
        }

        try {
            DB::beginTransaction();

            // Close the day session at the chosen moment
            $this->currentSession->close($this->closing_amount, $this->sync_amount, Auth::id(), $this->notes, $closedAt);

            if ($moqSync) {
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

            // Reset the form and reload sessions. The business date goes back to today so the
            // "not started" step doesn't keep showing the date the session was just closed against.
            $this->reset(['closing_amount', 'notes']);
            $this->date = now()->toDateString();
            $this->resetTimeDefaults();
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
