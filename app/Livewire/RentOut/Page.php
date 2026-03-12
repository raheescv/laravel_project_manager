<?php

namespace App\Livewire\RentOut;

use App\Actions\RentOut\BookAction;
use App\Actions\RentOut\ConfirmBookingAction;
use App\Actions\RentOut\CreateAction;
use App\Actions\RentOut\UpdateAction;
use App\Enums\RentOut\RentOutStatus;
use App\Models\Property;
use App\Models\RentOut;
use App\Support\RentOutConfig;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    public $type = 'RentOut';

    public $table_id;

    public $agreementType = 'lease';

    public $preFilledDropDowns;

    public $rent_outs;

    public $rentOut;

    public $months = 0;

    public $days = 0;

    public $vacant_only = true;

    public function mount($type = 'RentOut', $table_id = null, $agreementType = 'lease')
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
            $this->rentOut = RentOut::with(['property.building.group', 'property.type', 'customer', 'salesman'])->find($this->table_id);
            if (! $this->rentOut) {
                session()->flash('error', 'Record not found');

                return redirect()->route($this->config->indexRoute);
            }
            $this->rent_outs = $this->rentOut->toArray();
            $this->rent_outs['agreement_type'] = $this->rentOut->agreement_type?->value ?? $this->agreementType;
            $this->rent_outs['status'] = $this->rentOut->status?->value ?? '';
            $this->rent_outs['collection_payment_mode'] = $this->rentOut->collection_payment_mode?->value ?? '';
            $property_name = $this->rentOut->property ? $this->rentOut->property->number.($this->rentOut->property->building ? ' - '.$this->rentOut->property->building->name : '') : '';
            $this->preFilledDropDowns = [
                'group' => [
                    $this->rentOut->property_group_id => $this->rentOut->property?->group?->name,
                ],
                'building' => [
                    $this->rentOut->property_building_id => $this->rentOut->property?->building?->name,
                ],
                'type' => [
                    $this->rentOut->property_type_id => $this->rentOut->property?->type?->name,
                ],
                'property' => [
                    $this->rentOut->property_id => $property_name,
                ],
                'account' => [
                    $this->rentOut->account_id => $this->rentOut->customer?->name,
                ],
            ];

            if (! empty($this->rentOut->salesman_id)) {
                $this->preFilledDropDowns['salesman'] = [$this->rentOut->salesman_id => $this->rentOut->salesman?->name];
            }

            $this->dispatch('RentOutSelectValues', [
                'property_group_id' => $this->rentOut->property_group_id,
                'group_name' => $this->rentOut->property?->building?->group?->name,
                'property_building_id' => $this->rentOut->property_building_id,
                'building_name' => $this->rentOut->property?->building?->name,
                'property_type_id' => $this->rentOut->property_type_id,
                'type_name' => $this->rentOut->property?->type?->name,
                'property_id' => $this->rentOut->property_id,
                'property_name' => $property_name,
                'account_id' => $this->rentOut->account_id,
                'customer_name' => $this->rentOut->customer?->name,
                'salesman_id' => $this->rentOut->salesman_id,
                'salesman_name' => $this->rentOut->salesman?->name,
            ]);
        } else {
            $this->rent_outs = [
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
                'collection_payment_mode' => 'cash',
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
        if (in_array($key, ['rent_outs.start_date', 'rent_outs.end_date'])) {
            $this->monthCalculator();
        }
        if ($key === 'rent_outs.property_id' && $value) {
            $this->propertyCheck();
        }
        if (in_array($key, ['rent_outs.rent', 'rent_outs.discount', 'rent_outs.no_of_terms'])) {
            $this->rentCalculator();
        }
    }

    public function monthCalculator()
    {
        $this->days = 0;
        $this->months = 0;
        try {
            $startDate = Carbon::parse($this->rent_outs['start_date']);
            $endDate = Carbon::parse($this->rent_outs['end_date']);
            $this->months = count(CarbonPeriod::create($startDate, '1 month', $endDate));
            $this->days = $startDate->diffInDays($endDate);
        } catch (\Exception $e) {
            $this->days = 0;
            $this->months = 0;
        }
    }

    public function propertyCheck()
    {
        $property = Property::with(['building.group', 'type'])->find($this->rent_outs['property_id']);
        if ($property) {
            $this->rent_outs['rent'] = $property->rent ?? 0;
            $this->rent_outs['property_building_id'] = $property->property_building_id;
            $this->rent_outs['property_group_id'] = $property->property_group_id ?? $property->building?->property_group_id;
            $this->rent_outs['property_type_id'] = $property->property_type_id;
            $this->dispatch('PropertyAutoFill', [
                'property_group_id' => $this->rent_outs['property_group_id'],
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
        $noOfTerms = is_numeric($this->rent_outs['no_of_terms']) ? $this->rent_outs['no_of_terms'] : 0;
        $rent = is_numeric($this->rent_outs['rent']) ? $this->rent_outs['rent'] : 0;
        $discount = is_numeric($this->rent_outs['discount']) ? $this->rent_outs['discount'] : 0;
        $this->rent_outs['total'] = $noOfTerms * $rent - $discount;
    }

    public function cancel()
    {
        try {
            DB::beginTransaction();
            $this->rentOut->update(['status' => RentOutStatus::Cancelled->value]);
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
            $response = (new ConfirmBookingAction())->execute($this->table_id, Auth::id());
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }
            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);

            $redirectRoute = $this->type === 'Booking' ? $this->config->bookingViewRoute : $this->config->viewRoute;

            return redirect()->route($redirectRoute, $this->table_id);
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
                    $response = (new BookAction())->execute($this->rent_outs, Auth::id());
                } else {
                    $response = (new UpdateAction())->execute($this->rent_outs, $this->table_id, Auth::id());
                }
            } else {
                if (! $this->table_id) {
                    $response = (new CreateAction())->execute($this->rent_outs, Auth::id());
                } else {
                    $response = (new UpdateAction())->execute($this->rent_outs, $this->table_id, Auth::id());
                }
            }
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }
            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
            $rentOut = $response['data'];
            $this->table_id = $rentOut->id;

            $redirectRoute = $this->type === 'Booking' ? $this->config->bookingEditRoute : $this->config->editRoute;

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
