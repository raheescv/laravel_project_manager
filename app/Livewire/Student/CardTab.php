<?php

namespace App\Livewire\Student;

use App\Actions\Student\Card\AssignAction;
use App\Actions\Student\Card\BlockAction;
use App\Actions\Student\Card\UnblockAction;
use App\Actions\Student\GetBalanceAction;
use App\Models\Guardian;
use App\Models\StudentDetail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Student view → Card: link or replace the NFC card, block and unblock it, and the
 * card's history (from the audit trail on student_details).
 */
class CardTab extends Component
{
    public $account_id;

    public $card_uid = '';

    public $block_reason = '';

    public function mount($account_id)
    {
        $this->account_id = $account_id;
    }

    public function assign()
    {
        abort_unless(auth()->user()?->can('student card.assign'), 403);
        $this->run(fn () => (new AssignAction())->execute((int) $this->account_id, $this->card_uid, Auth::id()));
        $this->card_uid = '';
    }

    public function block()
    {
        abort_unless(auth()->user()?->can('student card.block'), 403);
        $this->run(fn () => (new BlockAction())->execute((int) $this->account_id, 'user', Auth::id(), $this->block_reason));
        $this->block_reason = '';
    }

    public function unblock()
    {
        abort_unless(auth()->user()?->can('student card.unblock'), 403);
        $this->run(fn () => (new UnblockAction())->execute((int) $this->account_id, Auth::id()));
    }

    private function run(callable $call): void
    {
        try {
            DB::beginTransaction();
            $response = $call();
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
            $this->dispatch('Student-View-Refresh');
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function render()
    {
        $detail = StudentDetail::with('account')->where('account_id', $this->account_id)->firstOrFail();

        $history = $detail->audits()
            ->latest()
            ->limit(30)
            ->get()
            ->filter(fn ($audit) => array_intersect(array_keys($audit->new_values ?? []), ['card_uid', 'card_status']))
            ->map(function ($audit) {
                $new = $audit->new_values;
                [$event, $kind] = match (true) {
                    ($new['card_status'] ?? null) === StudentDetail::CARD_BLOCKED => ['Blocked'.(filled($new['card_block_reason'] ?? null) ? ': '.$new['card_block_reason'] : ''), 'block'],
                    array_key_exists('card_uid', $new) && $audit->event === 'created' => [$new['card_uid'] ? 'Card linked '.$new['card_uid'] : null, 'link'],
                    array_key_exists('card_uid', $new) => $new['card_uid'] ? ['Card linked '.$new['card_uid'], 'link'] : ['Card unlinked', 'unlink'],
                    ($new['card_status'] ?? null) === StudentDetail::CARD_ACTIVE => ['Unblocked', 'unblock'],
                    default => [null, null],
                };

                return $event ? ['at' => $audit->created_at, 'event' => $event, 'kind' => $kind, 'by' => $audit->user?->name] : null;
            })
            ->filter()
            ->values();

        $blockedBy = null;
        if ($detail->isCardBlocked() && $detail->card_blocked_by_id) {
            $blockedBy = $detail->card_blocked_by_type === 'guardian'
                ? Guardian::withTrashed()->find($detail->card_blocked_by_id)?->name.' (parent)'
                : User::find($detail->card_blocked_by_id)?->name;
        }

        return view('livewire.student.card-tab', [
            'detail' => $detail,
            'account' => $detail->account,
            'balance' => (new GetBalanceAction())->execute((int) $this->account_id),
            'history' => $history,
            'blockedBy' => $blockedBy,
        ]);
    }
}
