<?php

namespace App\Actions\Student\Card;

use App\Actions\Student\EnsureAccountsAction;
use App\Actions\Student\GetBalanceAction;
use App\Models\Account;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Support\Student\StudentSettings;
use Exception;

/**
 * Refuse a completed sale that a student's card balance cannot cover.
 *
 * Runs inside the sale's transaction from App\Actions\Sale\CreateAction and
 * UpdateAction, after the lines and payments are written and BEFORE the journal
 * posts — so the ledger balance it reads excludes this sale (on an edit,
 * rollbackIfCompleted() has already removed the sale's old journal). Every channel
 * that saves a sale goes through those actions, so the POS, the web and imports
 * are all held to the same rule.
 *
 *  - The Student Card payment method may only pay a student's own purchase.
 *  - On a student account, whatever the sale leaves on the account (the card
 *    payment, plus anything left unpaid) must stay within balance + the school's
 *    overdraft limit.
 *
 * The student's account row is locked first so two tills charging the same card
 * at the same moment cannot both pass on the same balance.
 *
 * A sale return only gets the first rule: refunding to the card puts money back,
 * so there is no balance to check.
 */
class GuardCardPaymentAction
{
    public function execute(Sale|SaleReturn $sale): array
    {
        try {
            $cardMethodId = EnsureAccountsAction::cardMethodId();
            $payments = $sale->payments()->get(['payment_method_id', 'amount']);
            $cardAmount = $cardMethodId ? (float) $payments->where('payment_method_id', $cardMethodId)->sum('amount') : 0.0;

            $account = Account::query()->whereKey($sale->account_id)->lockForUpdate()->first();
            $isStudent = $account?->isStudent() ?? false;

            if ($cardAmount > 0 && ! $isStudent) {
                throw new Exception('Student Card can only pay for a student\'s own purchase. Choose the student as the customer.', 1);
            }

            if ($isStudent && $sale instanceof Sale) {
                $otherPaid = (float) $payments->sum('amount') - $cardAmount;
                // What lands on the student's balance: the card payment plus anything left unpaid.
                $charge = round((float) $sale->grand_total - $otherPaid, 2);

                if ($charge > 0) {
                    $balance = (new GetBalanceAction())->execute($account->id);
                    $limit = StudentSettings::current()->overdraftLimit;

                    if (round($balance - $charge, 2) < -$limit) {
                        $available = max(0, round($balance + $limit, 2));

                        throw new Exception(sprintf(
                            '%s\'s card cannot cover %s. Balance %s, overdraft limit %s, so at most %s can be charged to the card.',
                            $account->name,
                            currency($charge),
                            currency($balance),
                            currency($limit),
                            currency($available),
                        ), 1);
                    }
                }
            }

            $return['success'] = true;
            $return['message'] = 'Card balance covers the sale';
            $return['data'] = ['card_amount' => $cardAmount];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
