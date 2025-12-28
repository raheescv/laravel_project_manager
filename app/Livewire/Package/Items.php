<?php

namespace App\Livewire\Package;

use App\Actions\Package\Item\CreateAction;
use App\Actions\Package\Item\DeleteAction;
use App\Actions\Package\Item\UpdateAction;
use App\Models\Package;
use App\Models\PackageItem;
use Livewire\Component;

class Items extends Component
{
    public $package_id;

    public $items = [];

    public $item = [];

    public $showModal = false;

    public $editingId = null;

    public function mount($package_id)
    {
        $this->package_id = $package_id;
        $this->loadItems();
    }

    public function loadItems()
    {
        $package = Package::with('items')->find($this->package_id);
        $this->items = $package ? $package->items->toArray() : [];
    }

    public function openModal($id = null)
    {
        $this->editingId = $id;
        if ($id) {
            $item = PackageItem::find($id);
            $this->item = $item->toArray();
        } else {
            $this->item = [
                'package_id' => $this->package_id,
                'date' => now()->format('Y-m-d'),
                'rescheduled_date' => null,
                'notes' => '',
                'status' => 'pending',
            ];
        }
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->item = [];
        $this->editingId = null;
    }

    public function save()
    {
        $this->validate([
            'item.date' => 'required|date',
            'item.rescheduled_date' => 'nullable|date',
            'item.status' => 'required|in:visited,rescheduled,pending',
            'item.notes' => 'nullable|string',
        ]);

        try {
            if ($this->editingId) {
                $response = (new UpdateAction())->execute($this->item, $this->editingId);
            } else {
                $response = (new CreateAction())->execute($this->item);
            }

            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            $this->dispatch('success', ['message' => $response['message']]);
            $this->loadItems();
            $this->closeModal();
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        try {
            $response = (new DeleteAction())->execute($id);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->loadItems();
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.package.items');
    }
}
