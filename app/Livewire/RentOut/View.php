<?php

namespace App\Livewire\RentOut;

use App\Actions\RentOut\ConfirmBookingAction;
use App\Actions\RentOut\UpdateBookingStatusAction;
use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\RentOutStatus;
use App\Livewire\RentOut\Concerns\HasPaymentTermManagement;
use App\Models\RentOut;
use App\Support\RentOutConfig;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class View extends Component
{
    use HasPaymentTermManagement;

    public $agreementType = 'lease';

    public $isBooking = false;

    // Management Fee form fields
    public $management_fee;

    public $management_fee_payment_mode;

    public $management_fee_remarks;

    // Overlap data for confirmation modal
    public $overlappingRentouts = [];

    public $showOverlapModal = false;

    public function mount($id, $agreementType = 'lease', $isBooking = false)
    {
        $this->agreementType = $agreementType;
        $this->isBooking = $isBooking;
        $this->loadRentOut($id);
        $this->fillManagementFeeForm();
    }

    public function getConfigProperty(): RentOutConfig
    {
        return RentOutConfig::make($this->agreementType);
    }

    protected function fillManagementFeeForm(): void
    {
        if ($this->rentOut) {
            $this->management_fee = $this->rentOut->management_fee;
            $this->management_fee_payment_mode = $this->rentOut->management_fee_payment_mode;
            $this->management_fee_remarks = $this->rentOut->management_fee_remarks;
        }
    }

    public function saveManagementFee()
    {
        try {
            $this->rentOut->update([
                'management_fee' => $this->management_fee,
                'management_fee_payment_mode' => $this->management_fee_payment_mode,
                'management_fee_remarks' => $this->management_fee_remarks,
            ]);

            $this->loadRentOut();
            $this->dispatch('success', message: 'Management fee saved successfully.');
        } catch (\Throwable $th) {
            $this->dispatch('error', message: $th->getMessage());
        }
    }

    public function statusChange($status)
    {
        try {
            DB::beginTransaction();
            $response = (new UpdateBookingStatusAction())->execute($this->rentOut->id, $status);
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            $this->loadRentOut();
            $this->fillManagementFeeForm();
            DB::commit();

            if ($status === 'financial approved') {
                return redirect()->route(
                    $this->config->bookingViewRoute,
                    $this->rentOut->id
                );
            }

            $this->dispatch('success', message: $response['message']);
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function cancelBooking()
    {
        try {
            $this->rentOut->update(['status' => RentOutStatus::Cancelled->value]);
            $this->loadRentOut();
            $this->dispatch('success', message: 'Booking cancelled successfully.');
        } catch (\Throwable $th) {
            $this->dispatch('error', message: $th->getMessage());
        }
    }

    public function confirm()
    {
        // For rental bookings, check for overlapping agreements
        if ($this->rentOut->agreement_type !== AgreementType::Lease) {
            $overlaps = RentOut::getOverlapping(
                $this->rentOut->property_id,
                $this->rentOut->start_date,
                $this->rentOut->end_date,
                $this->rentOut->id
            );

            if ($overlaps->isNotEmpty()) {
                $this->overlappingRentouts = $overlaps->map(fn ($r) => [
                    'id' => $r->id,
                    'customer' => $r->customer?->name ?? 'N/A',
                    'start_date' => $r->start_date?->format('d M Y'),
                    'end_date' => $r->end_date?->format('d M Y'),
                    'status' => $r->status?->label(),
                ])->toArray();

                $this->showOverlapModal = true;

                return;
            }
        }

        $this->confirmBooking();
    }

    #[On('proceedWithBooking')]
    public function confirmBooking()
    {
        try {
            DB::beginTransaction();
            $this->showOverlapModal = false;
            $this->overlappingRentouts = [];

            $response = (new ConfirmBookingAction())->execute($this->rentOut->id);
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            DB::commit();

            return redirect()->route($this->config->viewRoute, $this->rentOut->id)
                ->with('message', 'Booking confirmed successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function closeOverlapModal()
    {
        $this->showOverlapModal = false;
        $this->overlappingRentouts = [];
    }

    public function render()
    {
        return view('livewire.rent-out.view', [
            'config' => $this->config,
        ]);
    }
}
