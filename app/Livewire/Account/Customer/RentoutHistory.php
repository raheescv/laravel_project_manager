<?php

namespace App\Livewire\Account\Customer;

use App\Enums\RentOut\RentOutStatus;
use App\Models\RentOut;
use Livewire\Component;

class RentoutHistory extends Component
{
    public $account_id;

    public $rentout_agreement_type = '';

    public $status = '';

    public $search = '';

    public function mount($account_id = null)
    {
        $this->account_id = $account_id;
    }

    public function render()
    {
        $rentouts = collect();
        if ($this->account_id) {
            $rentouts = RentOut::with(['property', 'building', 'group', 'type', 'salesman'])
                ->where('account_id', $this->account_id)
                ->when($this->rentout_agreement_type, fn ($q, $value) => $q->where('agreement_type', $value))
                ->when($this->status, fn ($q, $value) => $q->where('status', $value))
                ->latest('start_date')
                ->latest('id')
                ->get();

            // agreement_no is a computed accessor (BASL/26-001), not a column, so the
            // search runs over the loaded set — a single customer has few agreements.
            if (filled($this->search)) {
                $needle = mb_strtolower(trim($this->search));
                $rentouts = $rentouts->filter(function ($rentout) use ($needle) {
                    $haystack = [
                        $rentout->agreement_no,
                        $rentout->building?->name,
                        $rentout->property?->number,
                        $rentout->group?->name,
                        $rentout->type?->name,
                    ];

                    foreach ($haystack as $value) {
                        if (filled($value) && str_contains(mb_strtolower((string) $value), $needle)) {
                            return true;
                        }
                    }

                    return false;
                })->values();
            }
        }

        $activeStatuses = [RentOutStatus::Occupied->value, RentOutStatus::Booked->value];

        return view('livewire.account.customer.rentout-history', [
            'rentouts' => $rentouts,
            'active_count' => $rentouts->filter(fn ($r) => in_array($r->status?->value ?? (string) $r->status, $activeStatuses, true))->count(),
        ]);
    }
}
