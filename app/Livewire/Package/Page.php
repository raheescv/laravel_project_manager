<?php

namespace App\Livewire\Package;

use App\Actions\Package\CreateAction;
use App\Actions\Package\UpdateAction;
use App\Models\Package;
use App\Models\PackageCategory;
use Carbon\Carbon;
use Exception;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'package-payment-updated' => 'refreshPackage',
    ];

    public $packages;

    public $table_id;

    public $package;

    public function refreshPackage()
    {
        if ($this->table_id) {
            $this->package = Package::with(['packageCategory', 'account', 'items', 'payments'])->find($this->table_id);
            $this->packages = $this->package->toArray();
        }
    }

    public function updatedPackagesPackageCategoryId($value)
    {
        if ($value) {
            $category = PackageCategory::find($value);
            if ($category) {
                // Auto-fill amount from category price
                if ($category->price) {
                    $this->packages['amount'] = $category->price;
                }

                // Calculate end_date based on frequency and no_of_visits
                if ($category->frequency && $category->no_of_visits && isset($this->packages['start_date'])) {
                    $startDate = Carbon::parse($this->packages['start_date']);
                    $endDate = $this->calculateEndDate($startDate, $category->frequency, $category->no_of_visits);
                    $this->packages['end_date'] = $endDate->format('Y-m-d');
                }
            }
        }
    }

    public function updatedPackagesStartDate($value)
    {
        // Recalculate end_date if category is selected and has frequency/no_of_visits
        if ($value && isset($this->packages['package_category_id']) && $this->packages['package_category_id']) {
            $category = PackageCategory::find($this->packages['package_category_id']);
            if ($category && $category->frequency && $category->no_of_visits) {
                $startDate = Carbon::parse($value);
                $endDate = $this->calculateEndDate($startDate, $category->frequency, $category->no_of_visits);
                $this->packages['end_date'] = $endDate->format('Y-m-d');
            }
        }
    }

    private function calculateEndDate($startDate, $frequency, $noOfVisits)
    {
        $endDate = Carbon::parse($startDate);

        // Calculate end date based on frequency and number of visits
        switch ($frequency) {
            case 'daily':
                $endDate->addDays($noOfVisits - 1);
                break;
            case 'weekly':
                $endDate->addWeeks($noOfVisits - 1);
                break;
            case 'bi_weekly':
                $endDate->addWeeks(($noOfVisits - 1) * 2);
                break;
            case 'monthly':
                $endDate->addMonths($noOfVisits - 1);
                break;
            case 'yearly':
                $endDate->addYears($noOfVisits - 1);
                break;
            default:
                // Default to 30 days if frequency is not set
                $endDate->addDays(30);
                break;
        }

        return $endDate;
    }

    protected function validationAttributes()
    {
        return [
            'packages.package_category_id' => 'package category',
            'packages.account_id' => 'account',
            'packages.start_date' => 'start date',
            'packages.end_date' => 'end date',
            'packages.amount' => 'amount',
            'packages.paid' => 'paid',
            'packages.status' => 'status',
        ];
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $this->packages = [
                'package_category_id' => '',
                'account_id' => '',
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(30)->format('Y-m-d'),
                'remarks' => '',
                'amount' => '',
                'paid' => 0,
                'status' => 'in_progress',
            ];
        } else {
            $this->package = Package::with(['packageCategory', 'account', 'items', 'payments'])->find($this->table_id);
            $this->packages = $this->package->toArray();
        }
        $this->dispatch('SelectDropDownValues', $this->packages);
    }

    protected function rules()
    {
        return [
            'packages.package_category_id' => ['required', 'exists:package_categories,id'],
            'packages.account_id' => ['required', 'exists:accounts,id'],
            'packages.start_date' => ['required', 'date'],
            'packages.end_date' => ['required', 'date', 'after_or_equal:packages.start_date'],
            'packages.amount' => ['required', 'numeric', 'min:0'],
            'packages.paid' => ['nullable', 'numeric', 'min:0'],
            'packages.status' => ['required', 'in:in_progress,completed,cancelled'],
            'packages.remarks' => ['nullable', 'string'],
        ];
    }

    protected $messages = [
        'packages.package_category_id.required' => 'The package category field is required',
        'packages.account_id.required' => 'The account field is required',
        'packages.start_date.required' => 'The start date field is required',
        'packages.end_date.required' => 'The end date field is required',
        'packages.end_date.after_or_equal' => 'The end date must be after or equal to start date',
        'packages.amount.required' => 'The amount field is required',
        'packages.amount.numeric' => 'The amount must be a number',
        'packages.amount.min' => 'The amount must be at least 0',
        'packages.paid.numeric' => 'The paid amount must be a number',
        'packages.paid.min' => 'The paid amount must be at least 0',
        'packages.status.required' => 'The status field is required',
    ];

    public function save()
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->packages);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
                $this->dispatch('success', ['message' => $response['message']]);

                return redirect()->route('package::edit', $response['data']->id);
            } else {
                $response = (new UpdateAction())->execute($this->packages, $this->table_id);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
                $this->dispatch('success', ['message' => $response['message']]);
                $this->mount($this->table_id);
                $this->dispatch('$refresh'); // Refresh to update balance
            }
        } catch (Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $packageCategories = PackageCategory::orderBy('name')->pluck('name', 'id')->toArray();

        return view('livewire.package.page', [
            'packageCategories' => $packageCategories,
        ]);
    }
}
