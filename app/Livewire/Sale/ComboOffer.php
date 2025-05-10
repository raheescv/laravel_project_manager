<?php

namespace App\Livewire\Sale;

use App\Actions\Sale\ComboOffer\DeleteAction;
use App\Models\ComboOffer as ModelComboOffer;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ComboOffer extends Component
{
    public $selectedComboOffers = [];

    public $comboOfferItems = [];

    public $comboOffer;

    public $selectedComboOfferId;

    public $selectedServices = [];

    protected $listeners = [
        'Open-Sale-ComboOffer-Component' => 'open',
        'Remove-Sale-ComboOffer-Component' => 'remove',
    ];

    public function mount() {}

    public function open($items, $combos)
    {
        $this->comboOfferItems = $items;
        $this->selectedComboOffers = $combos;
        $this->dispatch('ToggleManageSaleComboOfferModal');
    }

    public function updated($key, $value)
    {
        if ($key == 'selectedComboOfferId') {
            $this->onComboOfferSelected($value);
        }
    }

    public function onComboOfferSelected($comboOfferId)
    {
        $this->comboOffer = ModelComboOffer::find($comboOfferId);
        $this->selectedComboOfferId = $comboOfferId;
        $this->selectedServices = [];
        $this->dispatch('$refresh');
    }

    public function add()
    {
        if (! $this->selectedComboOfferId) {
            $this->dispatch('error', ['message' => 'Please select a combo offer first']);

            return;
        }

        if (! $this->comboOffer || count($this->selectedServices) !== $this->comboOffer['count']) {
            $this->dispatch('error', ['message' => 'Please select '.$this->comboOffer['count'].' services for this comboOffer']);

            return;
        }

        // Check if services are already in another comboOffer
        $existingComboOfferServices = collect($this->selectedComboOffers)->pluck('items')->flatten()->toArray();
        if (array_intersect($this->selectedServices, $existingComboOfferServices)) {
            $this->dispatch('error', ['message' => 'Some services are already in another comboOffer']);

            return;
        }
        $comboOfferPrices = $this->calculateComboOfferPrices($this->selectedServices, $this->selectedComboOfferId);

        $item = [
            'combo_offer_id' => $this->selectedComboOfferId,
            'combo_offer_name' => $this->comboOffer->name,
            'amount' => $this->comboOffer->amount,
            'items' => $comboOfferPrices->toArray(),
        ];

        $this->selectedComboOffers[] = $item;

        $this->selectedComboOfferId = null;
        $this->selectedServices = [];

        $this->dispatch('success', ['message' => 'Combo Offer added successfully']);
        $this->dispatch('OpenComboOfferBox');
        $this->dispatch('$refresh');
    }

    protected function calculateComboOfferPrices($selectedServices, $comboOfferId)
    {
        $services = collect($this->comboOfferItems)->only($selectedServices);
        $totalOriginalPrice = $services->sum('unit_price');
        $comboOfferAmount = $this->comboOffer->amount;

        return $services->map(function ($item) use ($totalOriginalPrice, $comboOfferAmount, $comboOfferId) {
            $comboOfferPrice = round(($item['unit_price'] / $totalOriginalPrice) * $comboOfferAmount, 2);
            $item['combo_offer_price'] = $comboOfferPrice;
            $item['discount'] = round($item['unit_price'] - $comboOfferPrice, 2);
            $item['combo_offer_id'] = $comboOfferId;

            $this->comboOfferItems[$item['key']] = $item;

            return $item;
        });
    }

    public function remove($index)
    {
        try {
            DB::beginTransaction();
            if (! isset($this->selectedComboOffers[$index])) {
                throw new Exception('Invalid ComboOffer Id', 1);
            }
            $id = $this->selectedComboOffers[$index]['id'] ?? '';
            if ($id) {
                $response = (new DeleteAction())->execute($id);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }
            // Reset prices for items in the removed comboOffer
            foreach ($this->selectedComboOffers[$index]['items'] as $item) {
                if (isset($this->comboOfferItems[$item['key']])) {
                    $this->comboOfferItems[$item['key']]['combo_offer_price'] = 0;
                    $this->comboOfferItems[$item['key']]['discount'] = 0;
                    $this->comboOfferItems[$item['key']]['sale_combo_offer_id'] = null;
                }
            }
            unset($this->selectedComboOffers[$index]);
            DB::commit();
            $this->dispatch('success', ['message' => 'Combo Offer removed successfully']);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function save()
    {
        $this->dispatch('Sale-ComboOffer-Update-Price', $this->comboOfferItems, $this->selectedComboOffers);
        $this->dispatch('ToggleManageSaleComboOfferModal');
    }

    // for filteredItems
    public function getFilteredItemsProperty()
    {
        $existingComboOfferServices = collect($this->selectedComboOffers)->pluck('items')->flatten(1)->pluck('key')->toArray();

        return collect($this->comboOfferItems)
            ->filter(function ($item) use ($existingComboOfferServices) {
                return ! in_array($item['key'], $existingComboOfferServices);
                // && //    $item['unit_price'] > 0
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.sale.combo-offer', [
            'filtered_combo_offer_items' => $this->filteredItems,
        ]);
    }
}
