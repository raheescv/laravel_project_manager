<?php

namespace App\Livewire\Student;

use App\Actions\QPay\InquireAction;
use App\Actions\QPay\RefundAction;
use App\Actions\QPay\ReleaseAction;
use App\Actions\Student\ListTopupsAction;
use App\Actions\Student\ManualEntryAction;
use App\Models\Account;
use App\Models\QpayTransaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Student view → Top-ups: money on and off the card outside a purchase — office
 * entries recorded here, and QPay payments and refunds.
 */
class Topups extends Component
{
    public $account_id;

    /** The office entry form. */
    public $direction = ManualEntryAction::ADD;

    public $amount = '';

    public $payment_account_id = '';

    public $date;

    public $reason = '';

    public $show_form = false;

    public function mount($account_id)
    {
        abort_unless(auth()->user()?->can('student topup.view'), 403);
        $this->account_id = $account_id;
        $this->date = date('Y-m-d');
    }

    public function toggleForm()
    {
        $this->show_form = ! $this->show_form;
        $this->reset(['amount', 'reason']);
        $this->direction = ManualEntryAction::ADD;
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
        $this->show_form = false;
        $this->dispatch('success', ['message' => $response['message']]);
        $this->dispatch('Student-View-Refresh');
    }

    /** Ask QPay now for a payment that is still pending (a broken transaction). */
    public function inquire($id)
    {
        abort_unless(auth()->user()?->can('student topup.view'), 403);
        $transaction = QpayTransaction::where('account_id', $this->account_id)->findOrFail($id);

        $response = (new InquireAction())->execute($transaction);
        $this->dispatch($response['success'] ? 'success' : 'error', ['message' => $response['message']]);
        $this->dispatch('Student-View-Refresh');
    }

    /** Free the card from a payment QPay will not answer for, so the parent can top up again. */
    public function release($id)
    {
        abort_unless(auth()->user()?->can('student topup.release'), 403);
        $transaction = QpayTransaction::where('account_id', $this->account_id)->findOrFail($id);

        $response = (new ReleaseAction())->execute($transaction, Auth::id());
        $this->dispatch($response['success'] ? 'success' : 'error', ['message' => $response['message']]);
        $this->dispatch('Student-View-Refresh');
    }

    /** Refund a successful top-up in full through QPay (the action manages its own transactions around the gateway call). */
    public function refund($id)
    {
        abort_unless(auth()->user()?->can('student topup.refund'), 403);
        QpayTransaction::where('account_id', $this->account_id)->findOrFail($id);

        $response = (new RefundAction())->execute((int) $id, Auth::id());
        $this->dispatch($response['success'] ? 'success' : 'error', ['message' => $response['message']]);
        $this->dispatch('Student-View-Refresh');
    }

    public function render()
    {
        return view('livewire.student.topups', [
            'rows' => (new ListTopupsAction())->execute((int) $this->account_id),
            'paymentMethods' => Account::query()
                ->whereIn('id', tenant_cache('payment_methods', []) ?: [])
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
