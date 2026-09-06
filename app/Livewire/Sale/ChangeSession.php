<?php

namespace App\Livewire\Sale;

use App\Actions\Sale\ChangeDaySessionAction;
use App\Models\Sale;
use App\Models\SaleDaySession;
use Exception;
use Illuminate\Support\Facades\Auth;
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
                $label = '#'.$s->id.' - '.($s->opened_at->format('d-m-Y'));

                return [$s->id => $label];
            })
            ->toArray();
        $this->selectedSessionId = $this->sale->sale_day_session_id;

    }

    public function save()
    {
        abort_unless(auth()->user()?->can('sale.change day session'), 403);
        try {
            DB::beginTransaction();

            $response = (new ChangeDaySessionAction())->execute($this->sale, $this->selectedSessionId, Auth::id());
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }

            DB::commit();

            $this->dispatch('ToggleChangeSessionModal');
            $this->dispatch('success', ['message' => $response['message']]);

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
