<?php

namespace App\Actions\Account\Vendor;

use App\Actions\Account\CreateAction as AccountCreateAction;
use App\Actions\Account\UpdateAction as AccountUpdateAction;
use App\Models\Account;
use App\Models\AccountCategory;
use App\Services\TenantService;
use Exception;

class SaveAction
{
    /** The vendor detail columns the form may write, all existing `accounts` columns. */
    public const FIELDS = [
        'name', 'alias_name', 'vendor_type', 'credit_period_days', 'cr_number', 'tax_no',
        'mobile', 'whatsapp_mobile', 'emergency_contact_no', 'email', 'po_box', 'contact_person', 'place',
        'id_no', 'id_expiry_date', 'passport_no', 'occupation', 'nationality', 'company',
    ];

    /**
     * Create or update a vendor as an `accounts` row (model Vendor, liability,
     * in the Vendors category) through the shared account actions.
     *
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string, data?: Account}
     */
    public function execute(array $data, ?int $id = null): array
    {
        try {
            $fields = array_intersect_key($data, array_flip(self::FIELDS));
            $fields = array_map(fn ($value) => $value === '' ? null : $value, $fields);
            $fields['whatsapp_mobile'] ??= $fields['mobile'] ?? null;

            if ($id) {
                $vendor = Account::vendor()->findOrFail($id);
                $response = (new AccountUpdateAction())->execute($fields + [
                    'account_type' => $vendor->account_type,
                    'model' => 'Vendor',
                ], $vendor->id);
            } else {
                $category = AccountCategory::firstOrCreate([
                    'tenant_id' => app(TenantService::class)->getCurrentTenantId(),
                    'name' => 'Vendors',
                ]);
                $response = (new AccountCreateAction())->execute($fields + [
                    'account_type' => 'liability',
                    'model' => 'Vendor',
                    'account_category_id' => $category->id,
                ]);
            }

            if (! $response['success']) {
                throw new Exception($response['message']);
            }

            return ['success' => true, 'message' => $id ? 'Vendor updated.' : 'Vendor created.', 'data' => $response['data']];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
