<?php

namespace App\Livewire\Sale;

use App\Actions\Sale\Package\DeleteAction;
use App\Models\ServicePackage;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Package extends Component
{
    public $selectedPackages = [];

    public $packageItems = [];

    public $package;

    public $selectedPackageId;

    public $selectedServices = [];

    protected $listeners = [
        'Open-Sale-Package-Component' => 'open',
        'Remove-Sale-Package-Component' => 'remove',
    ];

    public function mount() {}

    public function open($items, $packages)
    {
        $this->packageItems = $items;
        $this->selectedPackages = $packages;
        $this->dispatch('ToggleManagePackageModal');
    }

    public function updated($key, $value)
    {
        if ($key == 'selectedPackageId') {
            $this->onPackageSelected($value);
        }
    }

    public function onPackageSelected($packageId)
    {
        $this->package = ServicePackage::find($packageId);
        $this->selectedPackageId = $packageId;
        $this->selectedServices = [];
        $this->dispatch('$refresh');
    }

    public function add()
    {
        if (! $this->selectedPackageId) {
            $this->dispatch('error', ['message' => 'Please select a package first']);

            return;
        }

        if (! $this->package || count($this->selectedServices) !== $this->package['service_count']) {
            $this->dispatch('error', ['message' => 'Please select '.$this->package['service_count'].' services for this package']);

            return;
        }

        // Check if services are already in another package
        $existingPackagedServices = collect($this->selectedPackages)->pluck('items')->flatten()->toArray();
        if (array_intersect($this->selectedServices, $existingPackagedServices)) {
            $this->dispatch('error', ['message' => 'Some services are already in another package']);

            return;
        }
        $packagePrices = $this->calculatePackagePrices($this->selectedServices, $this->selectedPackageId);

        $item = [
            'service_package_id' => $this->selectedPackageId,
            'package_name' => $this->package->name,
            'amount' => $this->package->amount,
            'items' => $packagePrices->toArray(),
        ];

        $this->selectedPackages[] = $item;

        $this->selectedPackageId = null;
        $this->selectedServices = [];

        $this->dispatch('success', ['message' => 'Package added successfully']);
        $this->dispatch('OpenPackageBox');
        $this->dispatch('$refresh');
    }

    protected function calculatePackagePrices($selectedServices, $packageId)
    {
        $services = collect($this->packageItems)->only($selectedServices);
        $totalOriginalPrice = $services->sum('unit_price');
        $packageAmount = $this->package->amount;

        return $services->map(function ($item) use ($totalOriginalPrice, $packageAmount, $packageId) {
            $packagePrice = round(($item['unit_price'] / $totalOriginalPrice) * $packageAmount, 2);
            $item['package_price'] = $packagePrice;
            $item['discount'] = round($item['unit_price'] - $packagePrice, 2);
            $item['service_package_id'] = $packageId;

            $this->packageItems[$item['key']] = $item;

            return $item;
        });
    }

    public function remove($index)
    {
        try {
            DB::beginTransaction();
            if (! isset($this->selectedPackages[$index])) {
                throw new Exception('Invalid Package Id', 1);
            }
            $id = $this->selectedPackages[$index]['id'] ?? '';
            if ($id) {
                $response = (new DeleteAction())->execute($id);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }
            // Reset prices for items in the removed package
            foreach ($this->selectedPackages[$index]['items'] as $item) {
                if (isset($this->packageItems[$item['key']])) {
                    $this->packageItems[$item['key']]['package_price'] = 0;
                    $this->packageItems[$item['key']]['discount'] = 0;
                    $this->packageItems[$item['key']]['sale_package_id'] = null;
                }
            }
            unset($this->selectedPackages[$index]);
            DB::commit();
            $this->dispatch('success', ['message' => 'Package removed successfully']);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function save()
    {
        $this->dispatch('Sale-Package-Update-Price', $this->packageItems, $this->selectedPackages);
        $this->dispatch('ToggleManagePackageModal');
    }

    // for filteredItems
    public function getFilteredItemsProperty()
    {
        $existingPackagedServices = collect($this->selectedPackages)->pluck('items')->flatten(1)->pluck('key')->toArray();

        return collect($this->packageItems)
            ->filter(function ($item) use ($existingPackagedServices) {
                return ! in_array($item['key'], $existingPackagedServices);
                // && //    $item['unit_price'] > 0
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.sale.package', [
            'filtered_package_items' => $this->filteredItems,
        ]);
    }
}
