<?php

namespace App\Livewire\Student;

use App\Actions\QPay\InquireAction;
use App\Actions\QPay\RefundAction;
use App\Actions\QPay\ReleaseAction;
use App\Actions\Student\ListTopupsAction;
use App\Models\QpayTransaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Student view → Top-ups: money on and off the card outside a purchase — office
 * entries (recorded in the TopupModal beside the page) and QPay payments and refunds.
 */
class Topups extends Component
{
    public $account_id;

    public $sortField = 'date';

    public $sortDirection = 'desc';

    /** Sortable columns, so a crafted sortBy() cannot reach the rows. */
    private const SORTABLE = ['date', 'channel', 'method', 'note', 'by', 'amount', 'status'];

    protected $listeners = [
        'Student-View-Refresh' => '$refresh',
    ];

    public function mount($account_id)
    {
        abort_unless(auth()->user()?->can('student topup.view'), 403);
        $this->account_id = $account_id;
    }

    public function sortBy($field)
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            // Newest and largest first; the wordy columns A-Z.
            $this->sortDirection = in_array($field, ['date', 'amount'], true) ? 'desc' : 'asc';
        }
    }

    /**
     * Order the rows for display. They come from two places (the ledger and
     * qpay_transactions), so the sort happens here rather than in a query.
     *
     * @param  array<int, array>  $rows
     * @return array<int, array>
     */
    private function sorted(array $rows): array
    {
        $field = $this->sortField;

        return collect($rows)
            ->sortBy(fn (array $row) => match ($field) {
                'amount' => (float) $row['amount'],
                'status' => mb_strtolower((string) $row['status_label']),
                'channel', 'method', 'note', 'by' => mb_strtolower((string) ($row[$field] ?? '')),
                default => (int) ($row['at']?->getTimestamp() ?? 0),
            }, SORT_REGULAR, $this->sortDirection === 'desc')
            ->values()
            ->all();
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
            'rows' => $this->sorted((new ListTopupsAction())->execute((int) $this->account_id)),
        ]);
    }
}
