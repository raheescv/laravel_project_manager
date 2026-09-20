<?php

namespace App\Livewire\Student;

use App\Actions\Student\GetBalanceAction;
use App\Actions\Student\ManualEntryAction;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Student view → "Record top-up": money taken at (or paid out from) the office.
 *
 * Mounted by the student view page beside the tabs, not inside them — a modal
 * rendered inside a tab is laid out against that panel and gets clipped by it,
 * and it would also be thrown away every time the tab re-renders.
 *
 * The Top-ups tab only holds the button that opens it; both components refresh
 * on Student-View-Refresh once an entry is posted.
 */
class TopupModal extends Component
{
    public $account_id;

    public $direction = ManualEntryAction::ADD;

    public $amount = '';

    public $payment_account_id = '';

    public $date;

    public $reason = '';

    protected $listeners = [
        'Student-View-Refresh' => '$refresh',
    ];

    public function mount($account_id)
    {
        abort_unless(auth()->user()?->can('student topup.view'), 403);
        $this->account_id = $account_id;
        $this->date = date('Y-m-d');
    }

    /** Record money taken at (or paid out from) the office. */
    public function record()
    {
        $deducting = $this->direction === ManualEntryAction::DEDUCT;
        abort_unless(auth()->user()?->can($deducting ? 'student topup.refund' : 'student topup.create'), 403);

        $response = (new ManualEntryAction())->execute(
            (int) $this->account_id,
            (float) $this->amount,
            (int) $this->payment_account_id,
            $this->direction,
            (string) $this->reason,
            $this->date,
            Auth::id(),
        );

        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }

        $this->reset(['amount', 'reason']);
        $this->direction = ManualEntryAction::ADD;
        $this->date = date('Y-m-d');
        // Closes the modal in the browser; the tabs and the header pick the entry up.
        $this->dispatch('student-topup-saved');
        $this->dispatch('success', ['message' => $response['message']]);
        $this->dispatch('Student-View-Refresh');
    }

    public function render()
    {
        return view('livewire.student.topup-modal', [
            'balance' => (new GetBalanceAction())->execute((int) $this->account_id),
            'paymentMethods' => Account::query()
                ->whereIn('id', tenant_cache('payment_methods', []) ?: [])
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
