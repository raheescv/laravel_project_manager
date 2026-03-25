<?php

namespace App\Helpers;

use App\Actions\RentOut\Payment\StoreTransactionAction;
use App\Models\RentOut;
use App\Models\RentOutCheque;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutUtilityTerm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class RentOutTransactionHelper
{
    protected function action(): StoreTransactionAction
    {
        return new StoreTransactionAction();
    }

    public function execute(array $data): array
    {
        return $this->action()->execute($data);
    }

    public function charge(int $rentOutId, array $data): array
    {
        return $this->action()->charge($rentOutId, $data);
    }

    public function chargeAndPay(int $rentOutId, array $data): array
    {
        return $this->action()->chargeAndPay($rentOutId, $data);
    }

    public function update(int $paymentId, array $data): array
    {
        return $this->action()->update($paymentId, $data);
    }

    public function revert(int $paymentId): array
    {
        return $this->action()->revert($paymentId);
    }

    public function storeManagementFee(RentOut $rentOut, int $userId): array
    {
        $accounts = Cache::get('accounts_slug_id_map', []);

        return $this->chargeAndPay($rentOut->id, [
            'date' => now()->format('Y-m-d'),
            'amount' => $rentOut->management_fee,
            'account_id' => $accounts['service_charge'] ?? $accounts['sale'] ?? 0,
            'source' => 'rent_out',
            'group' => 'Management Fee',
            'category' => 'management_fee',
            'remark' => $rentOut->management_fee_remarks ?:'Management fee for RentOut:'.$rentOut->id,
            'created_by' => $userId,
        ]);
    }

    public function storeDownPayment(RentOut $rentOut, int $userId): array
    {
        return $this->execute([
            'rent_out_id' => $rentOut->id,
            'date' => $rentOut->created_at ? $rentOut->created_at->format('Y-m-d') : now()->format('Y-m-d'),
            'debit' => 0,
            'credit' => $rentOut->down_payment,
            'account_id' => $rentOut->down_payment_payment_method_id ?? 0,
            'source' => 'rent_out',
            'group' => 'Down Payment',
            'category' => 'down_payment',
            'remark' => $rentOut->down_payment_remarks ?: 'RentOut Down Payment: '.$rentOut->id,
            'created_by' => $userId,
        ]);
    }

    public function storeServiceCharge(int $rentOutId, string $date, float $amount, int $serviceId, string $remark = ''): array
    {
        return $this->charge($rentOutId, [
            'date' => $date,
            'amount' => $amount,
            'source' => 'ServiceCharge',
            'model' => 'RentOutService',
            'model_id' => $serviceId,
            'paid_date' => $date,
            'reason' => 'Service Charge',
            'group' => 'Service Charge',
            'category' => 'Service Charge',
            'payment_type' => 'Services',
            'remark' => $remark,
            'created_by' => Auth::id(),
        ]);
    }

    public function storeServicePayLater(int $rentOutId, array $data): array
    {
        return $this->charge($rentOutId, $this->serviceData($data));
    }

    public function storeServicePayNow(int $rentOutId, array $data): array
    {
        return $this->chargeAndPay($rentOutId, $this->serviceData($data));
    }

    protected function serviceData(array $data): array
    {
        return [
            'date' => $data['date'],
            'amount' => $data['amount'],
            'account_id' => $data['account_id'] ?? '',
            'source' => 'Service',
            'model' => 'RentOutService',
            'paid_date' => $data['date'],
            'reason' => $data['category'] ?: 'Service',
            'group' => 'Service',
            'category' => $data['category'],
            'payment_type' => 'Services',
            'remark' => $data['remark'] ?? '',
            'created_by' => Auth::id(),
        ];
    }

    public function storePayout(int $rentOutId, string $date, float $amount, int $accountId, string $remark = ''): array
    {
        return $this->execute([
            'rent_out_id' => $rentOutId,
            'date' => $date,
            'credit' => 0,
            'debit' => $amount,
            'account_id' => $accountId,
            'source' => 'Payout',
            'model' => 'RentOut',
            'model_id' => $rentOutId,
            'paid_date' => $date,
            'reason' => $remark ?: 'Payout',
            'group' => 'Payout',
            'category' => 'Payout',
            'payment_type' => 'Payout',
            'remark' => $remark,
            'created_by' => Auth::id(),
        ]);
    }

    public function storeRentPayment(int $rentOutId, RentOutPaymentTerm $term, float $amount, int $accountId, string $payDate, string $remark = ''): array
    {
        return $this->execute([
            'rent_out_id' => $rentOutId,
            'date' => $payDate,
            'credit' => $amount,
            'debit' => 0,
            'account_id' => $accountId,
            'source' => 'PaymentTerm',
            'source_id' => $term->id,
            'model' => 'RentOutPaymentTerm',
            'model_id' => $term->id,
            'due_date' => $term->due_date?->format('Y-m-d'),
            'paid_date' => $payDate,
            'reason' => $term->label ?? 'Rent Payment',
            'group' => 'Rent Payment',
            'category' => $term->label ?? '',
            'payment_type' => 'Rent',
            'remark' => $remark,
            'created_by' => Auth::id(),
        ]);
    }

    public function storeUtilityPayment(int $rentOutId, RentOutUtilityTerm $term, float $amount, int $accountId, string $payDate, string $remark = ''): array
    {
        return $this->execute([
            'rent_out_id' => $rentOutId,
            'date' => $payDate,
            'credit' => $amount,
            'debit' => 0,
            'account_id' => $accountId,
            'source' => 'UtilityTerm',
            'source_id' => $term->id,
            'model' => 'RentOutUtilityTerm',
            'model_id' => $term->id,
            'due_date' => $term->date?->format('Y-m-d'),
            'paid_date' => $payDate,
            'reason' => $term->utility?->name ?? 'Utility Payment',
            'group' => 'Utility Payment',
            'category' => $term->utility?->name ?? '',
            'payment_type' => 'Utility',
            'remark' => $remark,
            'created_by' => Auth::id(),
        ]);
    }

    public function storeChequePayment(RentOutCheque $cheque, RentOutPaymentTerm $term, float $amount, int $accountId, ?string $journalDate = null, string $remark = ''): array
    {
        $date = $journalDate ?? $cheque->date->format('Y-m-d');

        return $this->execute([
            'rent_out_id' => $cheque->rent_out_id,
            'date' => $date,
            'credit' => $amount,
            'debit' => 0,
            'account_id' => $accountId,
            'source' => 'PaymentTerm',
            'source_id' => $term->id,
            'model' => 'RentOutCheque',
            'model_id' => $cheque->id,
            'due_date' => $term->due_date?->format('Y-m-d'),
            'paid_date' => $date,
            'cheque_date' => $cheque->date->format('Y-m-d'),
            'cheque_no' => $cheque->cheque_no,
            'bank_name' => $cheque->bank_name,
            'reason' => 'Cheque #'.($cheque->cheque_no ?? '').' cleared',
            'group' => 'Rent Payment',
            'category' => $term->label ?? '',
            'payment_type' => 'Cheque',
            'remark' => $remark ?: ('Cheque #'.($cheque->cheque_no ?? '').' cleared'),
            'created_by' => Auth::id(),
        ]);
    }
}
