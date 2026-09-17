<?php

namespace Tests\Support;

use App\Actions\Student\CreateAction as StudentCreateAction;
use App\Actions\Student\PostTopupJournalAction;
use App\Actions\V1\Parent\IssueTokenAction;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Guardian;
use App\Support\Student\StudentSettings;
use Illuminate\Support\Facades\DB;

/**
 * A POS world with student cards switched on: a student enrolled through the real
 * action, a way to put money on the card, and sale payloads paid by Student Card.
 */
class StudentWorld
{
    public const PORTAL_URL = 'https://parents.example.test';

    /**
     * Make the tenant a School Module tenant — student cards exist only there — with
     * the parent_portal app published at PORTAL_URL (invite links and the way back
     * from QPay point there).
     */
    public static function enableSchool(PosWorld $world): void
    {
        Configuration::updateOrCreate(
            ['tenant_id' => $world->tenant->id, 'key' => 'active_module'],
            ['value' => 'School Module'],
        );
        config(['services.parent_portal.url' => self::PORTAL_URL]);
    }

    /** A signed-in parent's bearer token, as the portal app holds it after login. */
    public static function parentToken(Guardian $guardian): string
    {
        return (new IssueTokenAction())->execute($guardian)['token'];
    }

    public static function enrol(PosWorld $world, array $overrides = []): Account
    {
        $response = (new StudentCreateAction())->execute(array_merge([
            'name' => 'Sara Ahmed',
            'admission_no' => 'ADM-'.random_int(1000, 99999),
            'grade' => 'Grade 5',
            'section' => 'B',
            'status' => 'active',
            'card_uid' => '04:a2:1b:9c',
            'guardians' => [['name' => 'Ahmed Saleh', 'mobile' => '55123456', 'relation' => 'father']],
        ], $overrides), $world->user->id);

        expect($response['success'])->toBeTrue($response['message']);

        return $response['data'];
    }

    public static function setOverdraft(PosWorld $world, float $limit): void
    {
        Configuration::updateOrCreate(
            ['tenant_id' => $world->tenant->id, 'key' => StudentSettings::KEY],
            ['value' => json_encode(['overdraft_limit' => $limit, 'topup_min' => 10, 'topup_max' => 1000])],
        );
    }

    /** Switch canteen pre-orders on with [$categoryIds] on the menu (school days Sun–Thu, cut-off 07:30). */
    public static function enablePreOrders(PosWorld $world, array $categoryIds, array $overrides = []): void
    {
        Configuration::updateOrCreate(
            ['tenant_id' => $world->tenant->id, 'key' => StudentSettings::KEY],
            ['value' => json_encode(array_merge([
                'overdraft_limit' => 0, 'topup_min' => 10, 'topup_max' => 1000,
                'pre_orders_enabled' => true, 'pre_order_category_ids' => $categoryIds,
                'pre_order_cutoff' => '07:30', 'school_days' => [7, 1, 2, 3, 4],
            ], $overrides))],
        );
    }

    public static function topUp(PosWorld $world, Account $student, float $amount): void
    {
        $response = (new PostTopupJournalAction())->execute($student->id, $amount, $world->cashAccountId, [
            'branch_id' => $world->branch->id,
            'model' => 'Test',
            'model_id' => 1,
        ], $world->user->id);

        expect($response['success'])->toBeTrue($response['message']);
    }

    /** App\Actions\Sale\CreateAction payload for [$amount], split between the card and cash. */
    public static function salePayload(PosWorld $world, int $accountId, float $amount, float $card, float $cash = 0): array
    {
        $inventoryId = DB::table('inventories')
            ->where('product_id', $world->product->id)
            ->where('branch_id', $world->branch->id)
            ->value('id');

        $payments = [];
        if ($card > 0) {
            $payments[] = ['payment_method_id' => Account::idBySlug('student_card'), 'amount' => $card];
        }
        if ($cash > 0) {
            $payments[] = ['payment_method_id' => $world->cashAccountId, 'amount' => $cash];
        }

        return [
            'branch_id' => $world->branch->id,
            'account_id' => $accountId,
            'date' => today()->toDateString(),
            'sale_type' => 'normal',
            'status' => 'completed',
            'gross_amount' => $amount,
            'item_discount' => 0,
            'tax_amount' => 0,
            'other_discount' => 0,
            'freight' => 0,
            'round_off' => 0,
            'paid' => $card + $cash,
            'items' => [[
                'inventory_id' => $inventoryId,
                'product_id' => $world->product->id,
                'unit_id' => $world->product->unit_id,
                'employee_id' => $world->user->id,
                'unit_price' => $amount,
                'quantity' => 1,
                'conversion_factor' => 1,
                'discount' => 0,
                'tax' => 0,
            ]],
            'payments' => $payments,
            'comboOffers' => [],
        ];
    }
}
