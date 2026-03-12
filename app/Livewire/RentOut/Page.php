<?php

namespace App\Livewire\RentOut;

use App\Actions\RentOut\BookAction;
use App\Actions\RentOut\ConfirmBookingAction;
use App\Actions\RentOut\CreateAction;
use App\Actions\RentOut\UpdateAction;
use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\RentOutStatus;
use App\Models\Property;
use App\Models\RentOut;
use App\Support\RentOutConfig;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    public $type = 'Rentout';

    public $table_id;

    public $agreementType = 'lease';

    public $rentouts;

    public $months = 0;

    public $days = 0;

    public $vacant_only = true;

    public function mount($type = 'Rentout', $table_id = null, $agreementType = 'lease')
    {
        $this->type = $type;
        $this->table_id = $table_id;
        $this->agreementType = $agreementType;
        $this->loadData();
    }

    public function getConfigProperty(): RentOutConfig
    {
        return RentOutConfig::make($this->agreementType);
    }

    public function loadData()
    {
        if ($this->table_id) {
            $item = RentOut::with(['property.building.group', 'property.type', 'customer', 'salesman'])->find($this->table_id);
            if (! $item) {
                session()->flash('error', 'Record not found');

                return redirect()->route($this->config->indexRoute);
            }
            $this->rentouts = $item->toArray();
            $this->rentouts['agreement_type'] = $item->agreement_type?->value ?? $this->agreementType;
            $this->rentouts['status'] = $item->status?->value ?? '';
            $this->rentouts['collection_payment_mode'] = $item->collection_payment_mode?->value ?? '';

            $this->dispatch('RentOutSelectValues', [
                'property_group_id' => $item->property_group_id,
                'group_name' => $item->property?->building?->group?->name,
                'property_building_id' => $item->property_building_id,
                'building_name' => $item->property?->building?->name,
                'property_type_id' => $item->property_type_id,
                'type_name' => $item->property?->type?->name,
                'property_id' => $item->property_id,
                'property_name' => $item->property ? $item->property->number . ($item->property->building ? ' - ' . $item->property->building->name : '') : '',
                'account_id' => $item->account_id,
                'customer_name' => $item->customer?->name,
                'salesman_id' => $item->salesman_id,
                'salesman_name' => $item->salesman?->name,
            ]);
        } else {
            $this->rentouts = [
                'agreement_type' => $this->agreementType,
                'account_id' => '',
                'property_group_id' => '',
                'property_building_id' => '',
                'property_type_id' => '',
                'property_id' => '',
                'salesman_id' => '',
                'booking_type' => 'Long Term',
                'status' => $this->type === 'Booking' ? RentOutStatus::Booked->value : RentOutStatus::Occupied->value,
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+1 year -1 day')),
                'rent' => 0,
                'no_of_terms' => 12,
                'payment_frequency' => 'Monthly',
                'discount' => 0,
                'free_month' => 0,
                'total' => 0,
                'collection_starting_day' => 1,
                'collection_payment_mode' => '',
                'collection_bank_name' => '',
                'collection_cheque_no' => '',
                'management_fee' => 0,
                'management_fee_payment_mode' => '',
                'management_fee_remarks' => '',
                'down_payment' => 0,
                'down_payment_mode' => '',
                'down_payment_remarks' => '',
                'include_electricity_water' => 'Included',
                'include_ac' => 'Included',
                'include_wifi' => 'Included',
                'remark' => '',
                'cancellation_policy_en' => '',
                'cancellation_policy_ar' => '',
                'payment_terms_en' => '',
                'payment_terms_ar' => '',
                'payment_terms_extended_en' => '',
                'payment_terms_extended_ar' => '',
            ];
        }
        $this->monthCalculator();
    }

    public function updated($key, $value)
    {
        if (in_array($key, ['rentouts.start_date', 'rentouts.end_date'])) {
            $this->monthCalculator();
        }
        if ($key === 'rentouts.property_id' && $value) {
            $this->propertyCheck();
        }
        if (in_array($key, ['rentouts.rent', 'rentouts.discount', 'rentouts.no_of_terms'])) {
            $this->rentCalculator();
        }
    }

    public function monthCalculator()
    {
        $this->days = 0;
        $this->months = 0;
        try {
            $startDate = Carbon::parse($this->rentouts['start_date']);
            $endDate = Carbon::parse($this->rentouts['end_date']);
            $this->months = count(CarbonPeriod::create($startDate, '1 month', $endDate));
            $this->days = $startDate->diffInDays($endDate);
        } catch (\Exception $e) {
            $this->days = 0;
            $this->months = 0;
        }
    }

    public function propertyCheck()
    {
        $property = Property::with(['building.group', 'type'])->find($this->rentouts['property_id']);
        if ($property) {
            $this->rentouts['rent'] = $property->rent ?? 0;
            $this->rentouts['property_building_id'] = $property->property_building_id;
            $this->rentouts['property_group_id'] = $property->property_group_id ?? $property->building?->property_group_id;
            $this->rentouts['property_type_id'] = $property->property_type_id;
            $this->dispatch('PropertyAutoFill', [
                'property_group_id' => $this->rentouts['property_group_id'],
                'group_name' => $property->building?->group?->name,
                'property_building_id' => $property->property_building_id,
                'building_name' => $property->building?->name,
                'property_type_id' => $property->property_type_id,
                'type_name' => $property->type?->name,
            ]);
            $this->rentCalculator();
        }
    }

    public function rentCalculator()
    {
        $noOfTerms = is_numeric($this->rentouts['no_of_terms']) ? $this->rentouts['no_of_terms'] : 0;
        $rent = is_numeric($this->rentouts['rent']) ? $this->rentouts['rent'] : 0;
        $discount = is_numeric($this->rentouts['discount']) ? $this->rentouts['discount'] : 0;
        $this->rentouts['total'] = ($noOfTerms * $rent) - $discount;
    }

    public function cancel()
    {
        try {
            DB::beginTransaction();
            $rentOut = RentOut::find($this->table_id);
            $rentOut->update(['status' => RentOutStatus::Cancelled->value]);
            DB::commit();
            $this->dispatch('success', ['message' => 'Successfully cancelled the booking']);
            $this->loadData();
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function confirm()
    {
        try {
            DB::beginTransaction();
            $response = (new ConfirmBookingAction())->execute($this->table_id, auth()->id());
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }
            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);

            return redirect()->route($this->config->viewRoute, $this->table_id);
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function save()
    {
        try {
            DB::beginTransaction();
            if ($this->type === 'Booking') {
                if (! $this->table_id) {
                    $response = (new BookAction())->execute($this->rentouts, auth()->id());
                } else {
                    $response = (new UpdateAction())->execute($this->rentouts, $this->table_id, auth()->id());
                }
            } else {
                if (! $this->table_id) {
                    $response = (new CreateAction())->execute($this->rentouts, auth()->id());
                } else {
                    $response = (new UpdateAction())->execute($this->rentouts, $this->table_id, auth()->id());
                }
            }
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }
            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
            $rentOut = $response['data'];
            $this->table_id = $rentOut->id;

            $redirectRoute = $this->type === 'Booking'
                ? $this->config->bookingCreateRoute
                : $this->config->createRoute;

            return redirect()->route($redirectRoute, $this->table_id);
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.rent-out.page', [
            'config' => $this->config,
        ]);
    }
}
