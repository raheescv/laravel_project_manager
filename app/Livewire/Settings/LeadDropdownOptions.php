<?php

namespace App\Livewire\Settings;

use App\Models\PropertyLead;
use App\Support\LeadOptions;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Settings → Lead Settings: the lead source and status dropdowns with their
 * sub sources / sub statuses, and an order number per status. Same model as
 * accounts' Dropdown Values screen, scoped to the lead lists.
 *
 * Renaming a value also renames it on the leads that carry it, so a rename
 * never strands leads under a value the dropdown no longer offers.
 */
class LeadDropdownOptions extends Component
{
    /** sources | statuses */
    public string $list = 'sources';

    public string $newValue = '';

    public $newOrder = '';

    public ?string $editing = null;

    public string $editValue = '';

    public $editOrder = '';

    /** Parent value whose sub options are open. */
    public ?string $openParent = null;

    public string $newSub = '';

    public ?int $editingSub = null;

    public string $editSubValue = '';

    public string $search = '';

    private const LISTS = [
        'sources' => ['key' => LeadOptions::SOURCES, 'sub' => LeadOptions::SUB_SOURCES, 'column' => 'source', 'sub_column' => 'sub_source', 'noun' => 'source'],
        'statuses' => ['key' => LeadOptions::STATUSES, 'sub' => LeadOptions::SUB_STATUSES, 'column' => 'status', 'sub_column' => 'sub_status', 'noun' => 'status'],
    ];

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function setList(string $list): void
    {
        if (isset(self::LISTS[$list])) {
            $this->list = $list;
            $this->reset(['newValue', 'newOrder', 'editing', 'editValue', 'editOrder', 'openParent', 'newSub', 'editingSub', 'editSubValue', 'search']);
        }
    }

    public function add(): void
    {
        $this->authorizeAccess();
        $value = trim($this->newValue);
        $this->validate([
            'newValue' => 'required|string|max:'.($this->list === 'statuses' ? 30 : 255),
            'newOrder' => 'nullable|integer|min:0',
        ], [], ['newValue' => $this->config('noun'), 'newOrder' => 'order no']);

        $values = $this->values();
        if ($this->exists($value, $values)) {
            $this->addError('newValue', 'That '.$this->config('noun').' already exists.');

            return;
        }

        $values[] = $value;
        $this->saveValues($values);
        if ($this->list === 'statuses' && filled($this->newOrder)) {
            $this->saveOrders([...LeadOptions::statusOrders(), $value => (int) $this->newOrder]);
        }

        $this->reset(['newValue', 'newOrder']);
        $this->dispatch('success', message: ucfirst($this->config('noun'))." \"{$value}\" added.");
    }

    public function edit(string $value): void
    {
        $this->editing = $value;
        $this->editValue = $value;
        $this->editOrder = LeadOptions::statusOrders()[$value] ?? '';
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->reset(['editing', 'editValue', 'editOrder']);
        $this->resetErrorBag();
    }

    public function update(): void
    {
        $this->authorizeAccess();
        if ($this->editing === null) {
            return;
        }
        $this->validate([
            'editValue' => 'required|string|max:'.($this->list === 'statuses' ? 30 : 255),
            'editOrder' => 'nullable|integer|min:0',
        ], [], ['editValue' => $this->config('noun'), 'editOrder' => 'order no']);

        $old = $this->editing;
        $new = trim($this->editValue);
        $values = $this->values();
        if ($new !== $old && $this->exists($new, array_diff($values, [$old]))) {
            $this->addError('editValue', 'That '.$this->config('noun').' already exists.');

            return;
        }

        $moved = DB::transaction(function () use ($old, $new, $values): int {
            $this->saveValues(array_map(fn ($v) => $v === $old ? $new : $v, $values));

            $subs = LeadOptions::subOptions($this->config('sub'));
            if ($new !== $old && isset($subs[$old])) {
                $subs[$new] = $subs[$old];
                unset($subs[$old]);
                LeadOptions::save($this->config('sub'), $subs);
            }

            if ($this->list === 'statuses') {
                $orders = LeadOptions::statusOrders();
                unset($orders[$old]);
                if (filled($this->editOrder)) {
                    $orders[$new] = (int) $this->editOrder;
                }
                $this->saveOrders($orders);
            }

            return $new === $old ? 0 : $this->leadsUsing($old)->update([$this->config('column') => $new]);
        });

        if ($this->openParent === $old) {
            $this->openParent = $new;
        }
        $this->cancelEdit();
        $this->dispatch('success', message: 'Saved.'.($moved ? " {$moved} ".str('lead')->plural($moved).' moved to "'.$new.'".' : ''));
    }

    public function delete(string $value): void
    {
        $this->authorizeAccess();
        $this->saveValues(array_values(array_diff($this->values(), [$value])));

        $subs = LeadOptions::subOptions($this->config('sub'));
        unset($subs[$value]);
        LeadOptions::save($this->config('sub'), $subs);

        if ($this->list === 'statuses') {
            $orders = LeadOptions::statusOrders();
            unset($orders[$value]);
            $this->saveOrders($orders);
        }

        if ($this->openParent === $value) {
            $this->openParent = null;
        }
        $this->dispatch('success', message: ucfirst($this->config('noun'))." \"{$value}\" removed. Leads that have it keep it.");
    }

    public function toggleSubs(string $parent): void
    {
        $this->openParent = $this->openParent === $parent ? null : $parent;
        $this->reset(['newSub', 'editingSub', 'editSubValue']);
        $this->resetErrorBag();
    }

    public function addSub(): void
    {
        $this->authorizeAccess();
        if ($this->openParent === null) {
            return;
        }
        $this->validate(['newSub' => 'required|string|max:255'], [], ['newSub' => 'sub '.$this->config('noun')]);

        $value = trim($this->newSub);
        $subs = LeadOptions::subOptions($this->config('sub'));
        $current = $subs[$this->openParent] ?? [];
        if ($this->exists($value, $current)) {
            $this->addError('newSub', 'Already listed under '.$this->openParent.'.');

            return;
        }

        $subs[$this->openParent] = [...$current, $value];
        LeadOptions::save($this->config('sub'), $subs);
        $this->reset('newSub');
    }

    public function editSub(int $index): void
    {
        $this->editingSub = $index;
        $this->editSubValue = LeadOptions::subOptions($this->config('sub'))[$this->openParent][$index] ?? '';
    }

    public function updateSub(): void
    {
        $this->authorizeAccess();
        if ($this->openParent === null || $this->editingSub === null) {
            return;
        }
        $this->validate(['editSubValue' => 'required|string|max:255'], [], ['editSubValue' => 'sub '.$this->config('noun')]);

        $subs = LeadOptions::subOptions($this->config('sub'));
        $current = $subs[$this->openParent] ?? [];
        $old = $current[$this->editingSub] ?? null;
        $new = trim($this->editSubValue);
        if ($old === null) {
            return;
        }
        if ($new !== $old && $this->exists($new, array_diff($current, [$old]))) {
            $this->addError('editSubValue', 'Already listed under '.$this->openParent.'.');

            return;
        }

        $current[$this->editingSub] = $new;
        $subs[$this->openParent] = $current;
        DB::transaction(function () use ($subs, $old, $new): void {
            LeadOptions::save($this->config('sub'), $subs);
            if ($new !== $old) {
                $this->leadsUsing($this->openParent)->where($this->config('sub_column'), $old)->update([$this->config('sub_column') => $new]);
            }
        });
        $this->reset(['editingSub', 'editSubValue']);
    }

    public function deleteSub(int $index): void
    {
        $this->authorizeAccess();
        $subs = LeadOptions::subOptions($this->config('sub'));
        if ($this->openParent === null || ! isset($subs[$this->openParent][$index])) {
            return;
        }

        unset($subs[$this->openParent][$index]);
        $subs[$this->openParent] = array_values($subs[$this->openParent]);
        if (! $subs[$this->openParent]) {
            unset($subs[$this->openParent]);
        }
        LeadOptions::save($this->config('sub'), $subs);
    }

    public function render()
    {
        $values = $this->list === 'statuses' ? LeadOptions::statuses() : LeadOptions::sources();
        $column = $this->config('column');
        $usage = PropertyLead::query()
            ->selectRaw("TRIM({$column}) as value, count(*) as total")
            ->groupByRaw("TRIM({$column})")
            ->pluck('total', 'value')
            ->all();

        $rows = collect($values)
            ->when(filled($this->search), fn ($rows) => $rows->filter(fn ($v) => str_contains(mb_strtolower($v), mb_strtolower(trim($this->search)))))
            ->map(fn ($value) => [
                'value' => $value,
                'order' => LeadOptions::statusOrders()[$value] ?? null,
                'subs' => LeadOptions::subOptions($this->config('sub'))[$value] ?? [],
                'leads' => (int) collect($usage)->filter(fn ($n, $k) => mb_strtolower((string) $k) === mb_strtolower($value))->sum(),
            ])
            ->values();

        return view('livewire.settings.lead-dropdown-options', [
            'rows' => $rows,
            'total' => count($values),
            'noun' => $this->config('noun'),
            'counts' => ['sources' => count(LeadOptions::sources()), 'statuses' => count(LeadOptions::statuses())],
        ]);
    }

    private function config(string $field): string
    {
        return self::LISTS[$this->list][$field];
    }

    /** @return list<string> the current list, defaults included until first saved */
    private function values(): array
    {
        return array_values($this->list === 'statuses' ? LeadOptions::statuses() : LeadOptions::sources());
    }

    private function saveValues(array $values): void
    {
        $values = array_values(array_unique($values));
        LeadOptions::save($this->config('key'), $values ? array_combine($values, $values) : []);
    }

    private function saveOrders(array $orders): void
    {
        LeadOptions::save(LeadOptions::STATUS_ORDER, $orders);
    }

    private function exists(string $value, array $values): bool
    {
        return in_array(mb_strtolower($value), array_map(fn ($v) => mb_strtolower(trim((string) $v)), $values), true);
    }

    /** Leads holding a value, matched the way stored values drift (case, stray spaces). */
    private function leadsUsing(string $value)
    {
        $column = $this->config('column');

        return PropertyLead::query()->whereRaw("LOWER(TRIM({$column})) = ?", [mb_strtolower($value)]);
    }

    private function authorizeAccess(): void
    {
        abort_unless(auth()->user()?->can('property lead.settings'), 403);
    }
}
