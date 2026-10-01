<?php

namespace App\Livewire\Property\PropertyLead;

use App\Models\Configuration;
use Livewire\Component;

/**
 * Column toggles for the lead list, the lead counterpart of the sale list's
 * column visibility panel. Choices are saved for the tenant and the list
 * redraws as soon as one changes. ID, name and actions are always shown.
 */
class ColumnVisibility extends Component
{
    public const CONFIG_KEY = 'property_lead_visible_column';

    public array $columns = [];

    /** @return array<string, array{label: string, visible: bool}> in list order */
    public static function definitions(): array
    {
        return [
            'mobile' => ['label' => 'Mobile', 'visible' => true],
            'email' => ['label' => 'Email', 'visible' => true],
            'property_group' => ['label' => 'Project / Group', 'visible' => true],
            'property_type' => ['label' => 'Property Type', 'visible' => false],
            'budget' => ['label' => 'Budget', 'visible' => false],
            'source' => ['label' => 'Source', 'visible' => true],
            'sub_source' => ['label' => 'Sub Source', 'visible' => false],
            'type' => ['label' => 'Type', 'visible' => true],
            'status' => ['label' => 'Status', 'visible' => true],
            'sub_status' => ['label' => 'Sub Status', 'visible' => false],
            'assigned_to' => ['label' => 'Assigned To', 'visible' => true],
            'nationality' => ['label' => 'Nationality', 'visible' => false],
            'meeting' => ['label' => 'Meeting', 'visible' => false],
            'location' => ['label' => 'Location', 'visible' => false],
            'created_at' => ['label' => 'Created', 'visible' => true],
            'reassigned_at' => ['label' => 'Reassigned', 'visible' => true],
            'updated_at' => ['label' => 'Updated', 'visible' => true],
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
        $this->dispatch('PropertyLead-Refresh-Component');
    }

    public function render()
    {
        return view('livewire.property.property-lead.column-visibility', [
            'definitions' => self::definitions(),
        ]);
    }
}
