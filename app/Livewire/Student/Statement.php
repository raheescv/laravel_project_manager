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

    public $sortField = 'date';

    public $sortDirection = 'asc';

    /** Sortable columns, so a crafted sortBy() cannot reach the rows. */
    private const SORTABLE = ['date', 'type', 'description', 'credit', 'debit', 'balance'];

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

    public function sortBy($field)
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            // A ledger reads oldest first; amounts read biggest first.
            $this->sortDirection = in_array($field, ['credit', 'debit', 'balance'], true) ? 'desc' : 'asc';
        }
    }

    /**
     * Order the rows for display only. The running balance is worked out in date
     * order inside the action first, so every row keeps the balance it really had.
     *
     * @param  array<int, array>  $rows
     * @return array<int, array>
     */
    private function sorted(array $rows): array
    {
        $field = $this->sortField;

        return collect($rows)
            ->sortBy(fn (array $row) => match ($field) {
                'credit', 'debit', 'balance' => (float) $row[$field],
                'type', 'description' => mb_strtolower((string) $row[$field]),
                default => [(string) $row['date'], (int) $row['id']],
            }, SORT_REGULAR, $this->sortDirection === 'desc')
            ->values()
            ->all();
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
        $statement = (new GetStatementAction())->execute((int) $this->account_id, $this->from_date ?: null, $this->to_date ?: null);
        $statement['rows'] = $this->sorted($statement['rows']);

        return view('livewire.student.statement', [
            'statement' => $statement,
            // The brought-forward line only means anything while the rows run oldest first.
            'chronological' => $this->sortField === 'date' && $this->sortDirection === 'asc',
        ]);
    }
}
