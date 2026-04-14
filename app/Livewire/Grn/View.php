<?php

namespace App\Livewire\Grn;

use App\Actions\Grn\JournalEntryAction;
use App\Actions\Grn\StockUpdateAction;
use App\Enums\Grn\GrnStatus;
use App\Models\Grn;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class View extends Component
{
    public Grn $grn;

    public bool $is_approvable = false;

    public string $remarks = '';

    public function mount(int $grn_id, bool $is_approvable = false)
    {
        $this->grn = Grn::with([
            'localPurchaseOrder.vendor',
            'creator',
            'branch',
            'decisionMaker',
            'items.product.brand',
            'items.product.mainCategory',
            'items.product.subCategory',
            'items.product.unit',
            'items.localPurchaseOrderItem',
            'journals.entries.account',
            'journals.entries.counterAccount',
        ])->findOrFail($grn_id);

        $this->is_approvable = $is_approvable;
    }

    public function accept()
    {
        try {
            DB::beginTransaction();

            $this->grn->update([
                'status' => GrnStatus::ACCEPTED,
                'decision_by' => Auth::id(),
                'decision_at' => now(),
                'decision_note' => $this->remarks,
            ]);

            // Stock update
            $this->grn->load(['items.product', 'items.localPurchaseOrderItem']);
            $stockResponse = (new StockUpdateAction())->execute($this->grn, Auth::id(), 'receive');
            if (! $stockResponse['success']) {
                throw new Exception('GRN accepted but stock update failed: '.$stockResponse['message']);
            }

            // Journal entry
            $this->grn->load(['vendor', 'localPurchaseOrder.vendor']);
            $journalResponse = (new JournalEntryAction())->execute($this->grn, Auth::id());
            if (! $journalResponse['success']) {
                throw new Exception('GRN accepted but journal entry failed: '.$journalResponse['message']);
            }

            DB::commit();
            $this->dispatch('success', ['message' => 'GRN accepted successfully. Stock and journal entries updated.']);
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

            $this->grn->update([
                'status' => GrnStatus::REJECTED,
                'decision_by' => Auth::id(),
                'decision_at' => now(),
                'decision_note' => $this->remarks,
            ]);

            DB::commit();
            $this->dispatch('success', ['message' => 'GRN rejected successfully']);
            $this->navigateBack();
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    private function navigateBack()
    {
        return $this->redirect(route('grn::view', $this->grn->id), true);
    }

    public function render()
    {
        return view('livewire.grn.view');
    }
}
