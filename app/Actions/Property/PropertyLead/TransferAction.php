<?php

namespace App\Actions\Property\PropertyLead;

use App\Actions\Account\CreateAction as AccountCreateAction;
use App\Models\Account;
use App\Models\CustomerType;
use App\Models\PropertyLead;

class TransferAction
{
    /**
     * Creates (or finds) a Customer Account for the lead, stores the
     * pre-fill payload in session('lead_booking_data'), and returns the
     * redirect URL for the Sale or Rent out booking create page.
     */
    public function execute($leadId): array
    {
        try {
            /** @var PropertyLead|null $lead */
            $lead = PropertyLead::with(['assignee:id,name', 'group:id,name', 'country:id,name'])->find($leadId);
            if (! $lead) {
                throw new \Exception('Lead not found.', 1);
            }
            if (! $lead->property_group_id) {
                throw new \Exception('Project / Group is required to transfer this lead to a booking.', 1);
            }
            if (empty($lead->name)) {
                throw new \Exception('Lead name is required.', 1);
            }

            // 1) Find or create the customer account (include soft-deleted to avoid unique constraint violation)
            $existing = Account::withTrashed()
                ->where('account_type', 'asset')
                ->where('name', $lead->name)
                ->when($lead->mobile, fn ($q) => $q->where('mobile', $lead->mobile))
                ->first();
            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $account = $existing;
            } else {
                $customerType = CustomerType::query()
                    ->where('name', $lead->type === 'Sales' ? 'Sale' : 'Rent')
                    ->first()
                    ?? CustomerType::query()->first();

                $accountPayload = [
                    'account_type' => 'asset',
                    'model' => 'customer',
                    'customer_type_id' => $customerType?->id,
                    'name' => $lead->name,
                    'mobile' => $lead->mobile,
                    'email' => $lead->email,
                    'nationality' => $lead->country?->name,
                    'company' => $lead->company_name,
                ];

                $response = (new AccountCreateAction())->execute($accountPayload);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
                $account = $response['data'];
            }

            // 2) Build the booking pre-fill payload
            $payload = [
                'lead_id' => $lead->id,
                'account_id' => $account->id,
                'customer_name' => $account->name,
                'customer_mobile' => $account->mobile,
                'customer_email' => $account->email,
                'salesman_id' => $lead->assigned_to,
                'salesman_name' => $lead->assignee?->name,
                'property_group_id' => $lead->property_group_id,
                'property_group_name' => $lead->group?->name,
                'type' => $lead->type,
            ];

            session(['lead_booking_data' => $payload]);

            $redirect = $lead->type === 'Sales'
                ? route('property::sale::booking.create')
                : route('property::rent::booking.create');

            return [
                'success' => true,
                'message' => 'Lead transferred. Customer '.($existing ? 'matched' : 'created').' successfully.',
                'data' => ['redirect' => $redirect, 'account_id' => $account->id],
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }
}
