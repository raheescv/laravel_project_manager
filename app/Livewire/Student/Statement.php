<?php

namespace App\Livewire\Student;

use App\Actions\Student\GetStatementAction;
use Livewire\Component;

/** Student view → Statement: the card's ledger with a running balance. */
class Statement extends Component
{
    public $account_id;

    public $from_date;

    public $to_date;

    /** The quick period button that matches the dates, or null once the dates are typed by hand. */
    public $preset = 'month';

    public function mount($account_id)
    {
        $this->account_id = $account_id;
        $this->applyPreset('month');
    }

    public function applyPreset($preset)
    {
        [$from, $to] = match ($preset) {
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'three_months' => [now()->subMonthsNoOverflow(2)->startOfMonth(), now()],
            'year' => [now()->startOfYear(), now()],
            default => [now()->startOfMonth(), now()],
        };

        $this->preset = in_array($preset, ['last_month', 'three_months', 'year'], true) ? $preset : 'month';
        $this->from_date = $from->toDateString();
        $this->to_date = $to->toDateString();
    }

    public function updatedFromDate()
    {
        $this->preset = null;
    }

    public function updatedToDate()
    {
        $this->preset = null;
    }

    public function render()
    {
        return view('livewire.student.statement', [
            'statement' => (new GetStatementAction())->execute((int) $this->account_id, $this->from_date ?: null, $this->to_date ?: null),
        ]);
    }
}
