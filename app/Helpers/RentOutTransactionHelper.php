<?php

namespace App\Helpers;

use App\Actions\RentOut\Payment\StoreTransactionAction;
use App\Models\Account;
use App\Models\RentOut;
use App\Models\RentOutCheque;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutSecurity;
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

    protected function paymentGroupLabel(?RentOut $rentOut): string
    {
        return $rentOut?->agreement_type?->config()->paymentGroupLabel ?? 'Rent Payment';
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
            'source' => $rentOut->agreement_type?->sourceSlug(),
            'group' => 'Management Fee',
            'category' => 'management_fee',
            'remark' => $rentOut->management_fee_remarks ?: 'Management fee for RentOut:'.$rentOut->id,
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
            'source' => $rentOut->agreement_type?->sourceSlug(),
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
            'group' => $this->paymentGroupLabel($term->rentOut),
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
            'group' => $this->paymentGroupLabel($cheque->rentOut),
            'category' => $term->label ?? '',
            'payment_type' => 'Cheque',
            'remark' => $remark ?: ('Cheque #'.($cheque->cheque_no ?? '').' cleared'),
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Record a security deposit collection (money IN).
     * Journal: Dr Payment Method (Cash/Bank), Cr Security Deposit (liability).
     *
     * Two rent_out_transactions rows are written to mirror the ledger:
     *  - Payment-method leg  : credit (money received)
     *  - Security-deposit leg: debit  (contra), sharing the same journal.
     */
    public function storeSecurityCollection(RentOutSecurity $security, ?int $userId = null): array
    {
        return $this->storeSecurityPair($security, false, $userId);
    }

    /**
     * Record a security deposit refund (money OUT).
     * Journal: Dr Security Deposit (liability), Cr Payment Method (Cash/Bank).
     *
     * Two rent_out_transactions rows are written to mirror the ledger:
     *  - Payment-method leg  : debit  (money paid out)
     *  - Security-deposit leg: credit (contra), sharing the same journal.
     */
    public function storeSecurityRefund(RentOutSecurity $security, ?int $userId = null): array
    {
        return $this->storeSecurityPair($security, true, $userId);
    }

    /**
     * Store both ledger legs of a security deposit movement. The first (cash)
     * leg creates the journal; the second (Security Deposit account) leg is a
     * contra row reusing that journal so the ledger nets to a single entry.
     */
    protected function storeSecurityPair(RentOutSecurity $security, bool $isRefund, ?int $userId): array
    {
        $primary = $this->execute($this->securityData($security, $isRefund, $userId));
        if (! $primary['success']) {
            return $primary;
        }

        $journalId = $primary['data']->journal_id ?? null;
        $contra = $this->action()->storeContraRow($this->securityContraData($security, $isRefund, $userId, $journalId));
        if (! $contra['success']) {
            return $contra;
        }

        return $primary;
    }

    /**
     * Resolve the locked "Security Deposit" liability account, falling back to the
     * customer account if it has not been provisioned for the tenant.
     */
    protected function securityDepositAccountId(RentOutSecurity $security): ?int
    {
        $accounts = Cache::get('accounts_slug_id_map', []);

        return $accounts['security_deposit']
            ?? Account::where('tenant_id', $security->tenant_id)
                ->where('slug', 'security_deposit')
                ->where('is_locked', 1)
                ->value('id')
            ?? $security->rentOut?->account_id;
    }

    protected function securityData(RentOutSecurity $security, bool $isRefund, ?int $userId): array
    {
        $date = $isRefund
            ? ($security->returned_date?->format('Y-m-d') ?? now()->format('Y-m-d'))
            : ($security->collected_date?->format('Y-m-d')
                ?? $security->due_date?->format('Y-m-d')
                ?? now()->format('Y-m-d'));

        return [
            'rent_out_id' => $security->rent_out_id,
            'date' => $date,
            'credit' => $isRefund ? 0 : $security->amount,
            'debit' => $isRefund ? $security->amount : 0,
            'account_id' => $security->account_id,
            'counter_account_id' => $this->securityDepositAccountId($security),
            'journal_source' => 'security_deposit',
            'source' => 'SecurityDeposit',
            'source_id' => $security->id,
            'model' => 'RentOutSecurity',
            'model_id' => $security->id,
            'due_date' => $security->due_date?->format('Y-m-d'),
            'paid_date' => $date,
            'cheque_no' => $security->cheque_no,
            'bank_name' => $security->bank_name,
            'reason' => $isRefund ? 'Security Deposit Refund' : 'Security Deposit Collection',
            'group' => 'Security Deposit',
            'category' => $isRefund ? 'security_refund' : 'security_collection',
            'payment_type' => 'Security Deposit',
            'remark' => $security->remarks ?: ($isRefund ? 'Security deposit refunded' : 'Security deposit collected'),
            'created_by' => $userId ?? Auth::id(),
        ];
    }

    /**
     * Contra (mirror) leg for a security deposit movement. Same amount and
     * metadata as the primary leg, but posted against the Security Deposit
     * account with the opposite debit/credit side and no new journal (it reuses
     * the primary leg's journal via $journalId).
     */
    protected function securityContraData(RentOutSecurity $security, bool $isRefund, ?int $userId, ?int $journalId): array
    {
        $data = $this->securityData($security, $isRefund, $userId);

        // Mirror the opposite side onto the Security Deposit ledger account.
        $data['account_id'] = $this->securityDepositAccountId($security) ?? $data['account_id'];
        $data['credit'] = $isRefund ? $security->amount : 0;
        $data['debit'] = $isRefund ? 0 : $security->amount;
        $data['journal_id'] = $journalId;
        $data['reason'] = $isRefund ? 'Security Deposit Refund (Ledger)' : 'Security Deposit Collection (Ledger)';

        // The journal is already created by the primary leg; do not create another.
        unset($data['counter_account_id'], $data['journal_source']);

        return $data;
    }
}
