<?php

namespace App\Livewire\Report;

use App\Models\Configuration;
use Livewire\Component;

/**
 * Column toggles for the day book report, the day book counterpart of the lead
 * list's column visibility panel. Choices are saved for the tenant and the
 * report redraws as soon as one changes. The entry ID is always shown.
 */
class DayBookColumnVisibility extends Component
{
    public const CONFIG_KEY = 'day_book_visible_column';

    public array $columns = [];

    /** @return array<string, array{label: string, visible: bool}> in report order */
    public static function definitions(): array
    {
        return [
            'date' => ['label' => 'Date', 'visible' => true],
            'account_name' => ['label' => 'Account Name', 'visible' => true],
            'description' => ['label' => 'Description', 'visible' => true],
            'reference_number' => ['label' => 'Reference No', 'visible' => true],
            'remarks' => ['label' => 'Remarks', 'visible' => true],
            'journal_remarks' => ['label' => 'Journal Remarks', 'visible' => true],
            'debit' => ['label' => 'Debit', 'visible' => true],
            'credit' => ['label' => 'Credit', 'visible' => true],
        ];
    }

    /**
     * Saved choices merged over the defaults, so a column added later is still
     * offered (and shown or hidden by its default) to a tenant who saved before.
     *
     * @return array<string, bool>
     */
    public static function current(): array
    {
        $saved = json_decode((string) Configuration::where('key', self::CONFIG_KEY)->value('value'), true) ?: [];
        $defaults = array_map(fn (array $column) => $column['visible'], self::definitions());

        return array_intersect_key(array_merge($defaults, $saved), $defaults);
    }

    public function mount(): void
    {
        $this->columns = self::current();
    }

    public function toggleColumn(string $column): void
    {
        if (! array_key_exists($column, $this->columns)) {
            return;
        }

        $this->columns[$column] = ! $this->columns[$column];
        $this->save();
    }

    public function resetToDefaults(): void
    {
        $this->columns = array_map(fn (array $column) => $column['visible'], self::definitions());
        $this->save();
    }

    private function save(): void
    {
        Configuration::updateOrCreate(['key' => self::CONFIG_KEY], ['value' => json_encode($this->columns)]);
        $this->dispatch('DayBook-Refresh-Component');
    }

    public function render()
    {
        return view('livewire.report.day-book-column-visibility', [
            'definitions' => self::definitions(),
        ]);
    }
}
