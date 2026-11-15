<?php

namespace App\Livewire\Settings;

use App\Actions\Student\EnsureAccountsAction;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\User;
use App\Support\Student\StudentSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

    /** '1' = the cut-off falls the evening before the meal; a string for the radio pair. */
    public $pre_order_cutoff_day_before = '0';

    public $pre_order_summary_emails = '';

    public $pre_order_category_ids = [];

    /** Where a paid pre-order's sale is booked, and who it is recorded as. */
    public $pre_order_branch_id = '';

    public $pre_order_user_id = '';

    public $registration_auto_approve = true;

    /** The public registration page takes forms; its schools are the showcase branches. */
    public $registration_enabled = false;

    public $registration_classes = '';

    public $registration_divisions = '';

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
        $this->pre_order_cutoff_day_before = $settings->preOrderCutoffDayBefore ? '1' : '0';
        $this->pre_order_summary_emails = implode("\n", $settings->preOrderSummaryEmails);
        $this->pre_order_category_ids = array_map('strval', $settings->preOrderCategoryIds);
        $this->pre_order_branch_id = (string) ($settings->preOrderBranchId ?? '');
        $this->pre_order_user_id = (string) ($settings->preOrderUserId ?? '');
        $this->registration_auto_approve = $settings->registrationAutoApprove;
        $this->registration_enabled = $settings->registrationOpen;
        $this->registration_classes = implode("\n", $settings->registrationClasses);
        $this->registration_divisions = implode("\n", $settings->registrationDivisions);
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
            'pre_order_cutoff_day_before' => ['required', 'in:0,1'],
            'pre_order_summary_emails' => ['nullable', 'string', 'max:1000', function (string $attribute, mixed $value, \Closure $fail): void {
                foreach (preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY) as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                        $fail("\"{$email}\" is not an email address.");

                        return;
                    }
                }
            }],
            'pre_order_category_ids' => ['array', $this->pre_orders_enabled ? 'min:1' : 'min:0'],
            'pre_order_category_ids.*' => ['integer'],
            'pre_order_branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('tenant_id', auth()->user()?->tenant_id)],
            'pre_order_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('tenant_id', auth()->user()?->tenant_id)],
            'registration_enabled' => ['boolean'],
            'registration_classes' => ['required', 'string', 'max:1000'],
            'registration_divisions' => ['required', 'string', 'max:500'],
            'registration_auto_approve' => ['boolean'],
        ], [
            'registration_classes.required' => 'List at least one class.',
            'registration_divisions.required' => 'List at least one division.',
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
                'pre_order_cutoff_day_before' => $this->pre_order_cutoff_day_before === '1',
                'pre_order_summary_emails' => (string) $this->pre_order_summary_emails,
                'pre_order_category_ids' => $this->pre_order_category_ids,
                'pre_order_branch_id' => $this->pre_order_branch_id,
                'pre_order_user_id' => $this->pre_order_user_id,
                'registration_enabled' => (bool) $this->registration_enabled,
                'registration_classes' => (string) $this->registration_classes,
                'registration_divisions' => (string) $this->registration_divisions,
                'registration_auto_approve' => (bool) $this->registration_auto_approve,
            ]);
            Configuration::updateOrCreate(['key' => StudentSettings::KEY], ['value' => json_encode($settings->toArray())]);
            DB::commit();

            $this->dispatch('success', ['message' => 'Student card settings saved']);
            $this->dispatch('student-status', statuses: self::railStatus($settings));
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    /**
     * What the Student Cards tab rail shows for the Card and Canteen panes.
     *
     * @return array<string, array{text: string, tone: string}>
     */
    public static function railStatus(?StudentSettings $settings = null): array
    {
        $settings ??= StudentSettings::current();
        $amount = fn (float $value): string => rtrim(rtrim(number_format($value, 2), '0'), '.');

        return [
            'card' => ['text' => 'Top-up '.$amount($settings->topupMin).'–'.$amount($settings->topupMax), 'tone' => 'info'],
            'canteen' => $settings->preOrdersEnabled
                ? ['text' => 'On · '.count($settings->schoolDays).' days', 'tone' => 'live']
                : ['text' => 'Off', 'tone' => 'off'],
            'registration' => $settings->registrationAvailable()
                ? ['text' => 'Open · '.StudentSettings::registrationBranches()->count().' schools', 'tone' => 'live']
                : ['text' => 'Closed', 'tone' => 'off'],
        ];
    }

    public function render()
    {
        return view('livewire.settings.student-configuration', [
            'weekdays' => self::WEEKDAYS,
            'registrationBranches' => StudentSettings::registrationBranches(),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->where('is_active', 1)->orderBy('name')->get(['id', 'name']),
            // "Drinks" or "Food › Sandwiches", so a sub category is recognisable.
            'categories' => Category::with('parent:id,name')->orderBy('name')->get(['id', 'name', 'parent_id'])
                ->mapWithKeys(fn (Category $category) => [$category->id => $category->parent ? $category->parent->name.' › '.$category->name : $category->name])
                ->sort(),
        ]);
    }
}
