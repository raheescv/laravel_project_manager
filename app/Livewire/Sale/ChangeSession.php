<?php

namespace App\Livewire\Sale;

use App\Models\Sale;
use App\Models\SaleDaySession;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ChangeSession extends Component
{
    public $table_id;

    public $availableSessions = [];

    public $selectedSessionId = null;

    public $sale;

    public function mount($table_id)
    {
        $this->table_id = $table_id;
        $this->sale = Sale::find($this->table_id);

        // Load sessions for this sale's branch and build id => label for select options
        $this->availableSessions = SaleDaySession::query()
            ->where('branch_id', $this->sale->branch_id)
            ->orderBy('opened_at', 'desc')
            ->get()
            ->mapWithKeys(function ($s) {
                $label = ($s->opened_at->format('d-m-Y')).' - #'.$s->id;

                return [$s->id => $label];
            })
            ->toArray();
        $this->selectedSessionId = $this->sale->sale_day_session_id;

    }

    public function save()
    {
        try {
            if (! $this->selectedSessionId) {
                throw new Exception('Please select a day session.');
            }

            $newSession = SaleDaySession::where('id', $this->selectedSessionId)
                ->where('branch_id', $this->sale->branch_id)
                ->first();

            if (! $newSession) {
                throw new Exception('Invalid session selected.');
            }

            DB::beginTransaction();

            $data = [
                'sale_day_session_id' => $this->selectedSessionId,
                'date' => $newSession->opened_at->format('Y-m-d'),
            ];
            $oldSession = $this->sale->saleDaySession;

            if ($newSession->id == $oldSession->id) {
                // throw new Exception('Please Select Different session.');
            }
            $this->sale->update($data);
            $this->sale->journals()->update(['date' => $data['date']]);
            $this->sale->payments()->update(['date' => $data['date']]);

            foreach ($this->sale->payments as $payment) {
                foreach ($payment->journalEntries as $value) {
                    $value->update(['date' => $data['date']]);
                }
            }
            if ($newSession->status == 'closed') {
                $newData = [
                    'closing_amount' => $newSession->closing_amount + $this->sale->paid,
                    'expected_amount' => $newSession->expected_amount + $this->sale->paid,
                ];
                $newSession->update($newData);
            }

            if ($oldSession->status == 'closed') {
                $oldData = [
                    'closing_amount' => $oldSession->closing_amount - $this->sale->paid,
                    'expected_amount' => $oldSession->expected_amount - $this->sale->paid,
                ];
                $oldSession->update($oldData);
            }

            DB::commit();

            $this->dispatch('ToggleChangeSessionModal');
            $this->dispatch('success', ['message' => 'Sale day session updated successfully.']);

            return redirect()->route('sale::view', ['id' => $this->table_id]);

        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.sale.change-session');
    }
}
