<?php

namespace App\Livewire\RentOut\Tabs;

use App\Models\RentOut;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class AgreementPointsTab extends Component
{
    public $rentOutId;

    public $points_en = [];

    public $points_ar = [];

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
        $this->loadData();
    }

    #[On('rent-out-updated')]
    public function refresh()
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $rentOut = RentOut::withTrashed()->find($this->rentOutId);

        $this->points_en = $rentOut->reservation_fees_disclaimer_en ?? [];
        $this->points_ar = $rentOut->reservation_fees_disclaimer_ar ?? [];

        // Ensure we have at least one empty point
        if (empty($this->points_en)) {
            $this->points_en = [''];
        }
        if (empty($this->points_ar)) {
            $this->points_ar = [''];
        }
    }

    public function addPoint(): void
    {
        $this->points_en[] = '';
        $this->points_ar[] = '';
    }

    public function removePoint($index): void
    {
        unset($this->points_en[$index]);
        unset($this->points_ar[$index]);

        // Re-index arrays
        $this->points_en = array_values($this->points_en);
        $this->points_ar = array_values($this->points_ar);

        // Ensure we have at least one empty point
        if (empty($this->points_en)) {
            $this->points_en = [''];
        }
        if (empty($this->points_ar)) {
            $this->points_ar = [''];
        }
    }

    public function save(): void
    {
        try {
            DB::beginTransaction();

            // Filter out empty points
            $pointsEn = array_values(array_filter($this->points_en, fn ($p) => ! empty(trim($p))));
            $pointsAr = array_values(array_filter($this->points_ar, fn ($p) => ! empty(trim($p))));

            $rentOut = RentOut::withTrashed()->find($this->rentOutId);
            $rentOut->update([
                'reservation_fees_disclaimer_en' => $pointsEn,
                'reservation_fees_disclaimer_ar' => $pointsAr,
            ]);

            $this->loadData();
            DB::commit();

            $this->dispatch('success', message: 'Agreement points saved successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.agreement-points-tab');
    }
}
