<?php

namespace App\Livewire\LpoPurchase;

use App\Actions\LpoPurchase\DecisionAction;
use App\Models\Purchase;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class View extends Component
{
    public Purchase $purchase;

    public bool $is_approvable = false;

    public string $remarks = '';

    public function mount(int $purchase_id, bool $is_approvable = false)
    {
        $this->purchase = Purchase::with([
            'localPurchaseOrder.vendor',
            'createdUser',
            'branch',
            'decisionMaker',
            'account',
            'items.product.brand',
            'items.product.mainCategory',
            'items.product.subCategory',
            'items.product.unit',
            'items.unit',
            'journals.entries.account',
            'journals.entries.counterAccount',
        ])->findOrFail($purchase_id);

        $this->is_approvable = $is_approvable;
    }

    public function accept()
    {
        try {
            DB::beginTransaction();

            $response = (new DecisionAction())->execute($this->purchase, 'accept', Auth::id(), $this->remarks);
            if (! $response['success']) {
                throw new Exception($response['message']);
            }

            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
            $this->navigateBack();
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function reject()
    {
        $this->validate([
            'remarks' => 'required|string|min:3',
        ]);

        try {
            DB::beginTransaction();

            $response = (new DecisionAction())->execute($this->purchase, 'reject', Auth::id(), $this->remarks);

            if (! $response['success']) {
                throw new Exception($response['message']);
            }

            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
            $this->navigateBack();
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    private function navigateBack()
    {
        return $this->redirect(route('lpo-purchase::view', $this->purchase->id), true);
    }

    public function render()
    {
        return view('livewire.lpo-purchase.view');
    }
}
