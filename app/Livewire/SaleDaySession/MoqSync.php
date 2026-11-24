<?php

namespace App\Livewire\SaleDaySession;

use App\Actions\Sale\SyncDaySessionToMoqAction;
use App\Models\SaleDaySession;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * "Sync to MOQ" button + modal on the day session details page. Asks the user
 * for the revenue amount and posts it to MOQ; can be run on an open or a closed
 * session and repeated (each attempt is logged in api_logs).
 */
class MoqSync extends Component
{
    public int $sessionId;

    /**
     * One-tap amounts shown under the input, label => value.
     *
     * @var array<string, float>
     */
    public array $suggestions = [];

    public $amount = '';

    /**
     * @param  array<string, float|int|null>  $suggestions
     */
    public function mount(int $sessionId, array $suggestions = []): void
    {
        $this->sessionId = $sessionId;
        $this->suggestions = collect($suggestions)
            ->filter(fn ($value) => is_numeric($value) && $value > 0)
            ->map(fn ($value) => (float) floor($value))
            ->all();
    }

    /** Pre-fill the modal each time it opens: the last synced amount, else blank. */
    public function prepare(): void
    {
        $session = $this->session();
        $this->resetErrorBag();
        $this->amount = $session->synced_at ? (int) $session->sync_amount : '';
    }

    public function sync(): void
    {
        abort_unless(Auth::user()->can('day session.moq sync'), 403);

        $this->validate(
            ['amount' => 'required|integer|min:0'],
            [
                'amount.required' => 'Enter the revenue amount to submit.',
                'amount.integer' => 'MOQ accepts whole numbers only.',
                'amount.min' => 'The amount cannot be negative.',
            ],
        );

        $response = (new SyncDaySessionToMoqAction())->execute($this->session(), (float) $this->amount, Auth::id());

        if (! $response['success']) {
            $this->addError('amount', $response['message']);
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }

        $this->dispatch('success', ['message' => $response['message']]);
        $this->dispatch('moq-synced');
    }

    protected function session(): SaleDaySession
    {
        return SaleDaySession::with(['branch', 'syncer'])->findOrFail($this->sessionId);
    }

    public function render(): View
    {
        return view('livewire.sale-day-session.moq-sync', [
            'session' => $this->session(),
        ]);
    }
}
