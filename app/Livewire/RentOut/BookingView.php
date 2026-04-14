<?php

namespace App\Livewire\RentOut;

use App\Actions\RentOut\ConfirmBookingAction;
use App\Actions\RentOut\UpdateBookingStatusAction;
use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\RentOutStatus;
use App\Livewire\RentOut\Concerns\HasPaymentTermManagement;
use App\Models\RentOut;
use App\Support\RentOutConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class BookingView extends Component
{
    use HasPaymentTermManagement;

    public $agreementType = 'lease';

    // Management Fee form fields
    public $management_fee;

    public $management_fee_payment_method_id;

    public $management_fee_remarks;

    // Overlap data for confirmation modal
    public $overlappingRentOuts = [];

    public $showOverlapModal = false;

    public function mount($id, $agreementType = 'lease')
    {
        $this->agreementType = $agreementType;
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
            $this->management_fee_payment_method_id = $this->rentOut->management_fee_payment_method_id;
            $this->management_fee_remarks = $this->rentOut->management_fee_remarks;
        }
    }

    public function saveManagementFee()
    {
        try {
            $this->rentOut->update([
                'management_fee' => $this->management_fee,
                'management_fee_payment_method_id' => $this->management_fee_payment_method_id,
                'management_fee_remarks' => $this->management_fee_remarks,
            ]);

            $this->loadRentOut();
            $this->fillManagementFeeForm();
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
                $this->overlappingRentOuts = $overlaps->map(fn ($r) => [
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
            $this->overlappingRentOuts = [];

            $response = (new ConfirmBookingAction())->execute($this->rentOut->id, Auth::id());
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
        $this->overlappingRentOuts = [];
    }

    public function render()
    {
        return view('livewire.rent-out.booking-view', [
            'config' => $this->config,
        ]);
    }
}
