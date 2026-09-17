<?php

namespace App\Livewire\Settings;

use App\Actions\Student\EnsureAccountsAction;
use App\Models\Category;
use App\Models\Configuration;
use App\Support\Student\StudentSettings;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Settings → Student Cards: the school-wide overdraft limit, the top-up range
 * parents may pay online, where the parent portal app is published, and the
 * canteen pre-orders parents can set up there.
 */
class StudentConfiguration extends Component
{
    public const WEEKDAYS = [7 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];

    public $overdraft_limit = 0;

    public $topup_min = 10;

    public $topup_max = 1000;

    public $portal_url = '';

    public $pre_orders_enabled = false;

    public $school_days = [];

    public $pre_order_cutoff = StudentSettings::DEFAULT_PRE_ORDER_CUTOFF;

    public $pre_order_category_ids = [];

    public function mount(): void
    {
        $settings = StudentSettings::current();
        $this->overdraft_limit = $settings->overdraftLimit;
        $this->topup_min = $settings->topupMin;
        $this->topup_max = $settings->topupMax;
        $this->portal_url = (string) $settings->portalUrl;
        $this->pre_orders_enabled = $settings->preOrdersEnabled;
        $this->school_days = array_map('strval', $settings->schoolDays);
        $this->pre_order_cutoff = $settings->preOrderCutoff;
        $this->pre_order_category_ids = array_map('strval', $settings->preOrderCategoryIds);
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('student settings.edit'), 403);

        $this->validate([
            'overdraft_limit' => ['required', 'numeric', 'min:0', 'max:100000'],
            'topup_min' => ['required', 'numeric', 'min:1'],
            'topup_max' => ['required', 'numeric', 'gte:topup_min', 'max:1000000'],
            'portal_url' => ['nullable', 'url:http,https', 'max:255'],
            'pre_orders_enabled' => ['boolean'],
            'school_days' => ['required', 'array', 'min:1'],
            'school_days.*' => ['integer', 'between:1,7'],
            'pre_order_cutoff' => ['required', 'date_format:H:i'],
            'pre_order_category_ids' => ['array', $this->pre_orders_enabled ? 'min:1' : 'min:0'],
            'pre_order_category_ids.*' => ['integer'],
        ], [
            'topup_max.gte' => 'The largest top-up must be at least the smallest top-up.',
            'portal_url.url' => 'Enter the full address, starting with https://',
            'school_days.required' => 'Pick the days the canteen is open.',
            'school_days.min' => 'Pick the days the canteen is open.',
            'pre_order_cutoff.date_format' => 'Enter a time like 07:30.',
            'pre_order_category_ids.min' => 'Pick at least one category for the pre-order menu, or switch pre-orders off.',
        ]);

        try {
            DB::beginTransaction();
            (new EnsureAccountsAction())->execute();
            $settings = StudentSettings::fromArray([
                'overdraft_limit' => $this->overdraft_limit,
                'topup_min' => $this->topup_min,
                'topup_max' => $this->topup_max,
                'portal_url' => $this->portal_url,
                'pre_orders_enabled' => (bool) $this->pre_orders_enabled,
                'school_days' => $this->school_days,
                'pre_order_cutoff' => $this->pre_order_cutoff,
                'pre_order_category_ids' => $this->pre_order_category_ids,
            ]);
            Configuration::updateOrCreate(['key' => StudentSettings::KEY], ['value' => json_encode($settings->toArray())]);
            DB::commit();

            $this->dispatch('success', ['message' => 'Student card settings saved']);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.student-configuration', [
            'weekdays' => self::WEEKDAYS,
            // "Drinks" or "Food › Sandwiches", so a sub category is recognisable.
            'categories' => Category::with('parent:id,name')->orderBy('name')->get(['id', 'name', 'parent_id'])
                ->mapWithKeys(fn (Category $category) => [$category->id => $category->parent ? $category->parent->name.' › '.$category->name : $category->name])
                ->sort(),
        ]);
    }
}
