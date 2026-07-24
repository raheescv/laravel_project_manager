<?php

namespace App\Actions\RentOut;

use App\Helpers\Facades\RentOutTransactionHelper;
use App\Models\Account;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutTransaction;
use App\Models\RentOutUtilityTerm;
use Illuminate\Console\Command;

class BackfillPropertyPaymentJournalEntriesAction
{
    public function execute(?int $tenantId = null, bool $dryRun = false, ?Command $command = null): array
    {
        $rentTermsCreated = $this->backfillRentPaymentTerms($tenantId, $dryRun, $command);
        $utilityTermsCreated = $this->backfillUtilityTerms($tenantId, $dryRun, $command);

        return [
            'rent_terms_created' => $rentTermsCreated,
            'utility_terms_created' => $utilityTermsCreated,
        ];
    }

    private function backfillRentPaymentTerms(?int $tenantId, bool $dryRun, ?Command $command): int
    {
        $query = RentOutPaymentTerm::query()
            ->with('rentOut')
            ->where('paid', '>', 0)
            ->when($tenantId !== null, fn ($builder) => $builder->where('tenant_id', $tenantId))
            ->orderBy('id');

        $count = $query->count();
        $command?->info("Checking {$count} paid rent payment terms...");

        $bar = $command?->getOutput()->createProgressBar($count);
        $created = 0;

        $query->chunkById(100, function ($terms) use ($tenantId, $dryRun, $command, $bar, &$created): void {
            foreach ($terms as $term) {
                $missingAmount = $this->resolveMissingAmount($term, 'RentOutPaymentTerm');
                $missingAmount = $this->capToAgreementShortfall($term, $missingAmount, 'rent');

                if ($missingAmount > 0 && $term->rentOut) {
                    $created += $this->backfillRentTerm($term, $missingAmount, $tenantId, $dryRun, $command);
                }

                $bar?->advance();
            }
        });

        $bar?->finish();
        if ($bar !== null) {
            $command?->newLine();
        }

        return $created;
    }

    private function backfillUtilityTerms(?int $tenantId, bool $dryRun, ?Command $command): int
    {
        $query = RentOutUtilityTerm::query()
            ->with(['rentOut', 'utility'])
            ->where('paid', '>', 0)
            ->when($tenantId !== null, fn ($builder) => $builder->where('tenant_id', $tenantId))
            ->orderBy('id');

        $count = $query->count();
        $command?->info("Checking {$count} paid utility terms...");

        $bar = $command?->getOutput()->createProgressBar($count);
        $created = 0;

        $query->chunkById(100, function ($terms) use ($tenantId, $dryRun, $command, $bar, &$created): void {
            foreach ($terms as $term) {
                $missingAmount = $this->resolveMissingAmount($term, 'RentOutUtilityTerm');
                $missingAmount = $this->capToAgreementShortfall($term, $missingAmount, 'utility');

                if ($missingAmount > 0 && $term->rentOut) {
                    $created += $this->backfillUtilityTerm($term, $missingAmount, $tenantId, $dryRun, $command);
                }

                $bar?->advance();
            }
        });

        $bar?->finish();
        if ($bar !== null) {
            $command?->newLine();
        }

        return $created;
    }

    private function backfillRentTerm(
        RentOutPaymentTerm $term,
        float $amount,
        ?int $tenantId,
        bool $dryRun,
        ?Command $command
    ): int {
        $paymentMethodId = $this->resolvePaymentMethodId($term->tenant_id, $term->payment_mode);
        if ($paymentMethodId === null) {
            $command?->warn("Skipping rent term #{$term->id}: no tenant payment method fallback was found.");

            return 0;
        }

        $date = $term->paid_date?->format('Y-m-d')
            ?? $term->due_date?->format('Y-m-d')
            ?? $term->updated_at?->format('Y-m-d')
            ?? now()->format('Y-m-d');

        if ($dryRun) {
            $command?->line("Would backfill rent term #{$term->id} with {$amount}.");

            return 1;
        }

        $response = RentOutTransactionHelper::storeRentPayment(
            $term->rent_out_id,
            $term,
            $amount,
            $paymentMethodId,
            $date,
            'Backfilled from existing paid property term'
        );

        if (! $response['success']) {
            $command?->warn("Failed backfilling rent term #{$term->id}: {$response['message']}");

            return 0;
        }

        return 1;
    }

    private function backfillUtilityTerm(
        RentOutUtilityTerm $term,
        float $amount,
        ?int $tenantId,
        bool $dryRun,
        ?Command $command
    ): int {
        $paymentMethodId = $this->resolvePaymentMethodId($term->tenant_id, $term->payment_mode);
        if ($paymentMethodId === null) {
            $command?->warn("Skipping utility term #{$term->id}: no tenant payment method fallback was found.");

            return 0;
        }

        $date = $term->paid_date?->format('Y-m-d')
            ?? $term->date?->format('Y-m-d')
            ?? $term->updated_at?->format('Y-m-d')
            ?? now()->format('Y-m-d');

        if ($dryRun) {
            $command?->line("Would backfill utility term #{$term->id} with {$amount}.");

            return 1;
        }

        $response = RentOutTransactionHelper::storeUtilityPayment(
            $term->rent_out_id,
            $term,
            $amount,
            $paymentMethodId,
            $date,
            'Backfilled from existing paid utility term'
        );

        if (! $response['success']) {
            $command?->warn("Failed backfilling utility term #{$term->id}: {$response['message']}");

            return 0;
        }

        return 1;
    }

    /**
     * How much this agreement may still be backfilled, per kind of term.
     *
     * A term looks unpaid whenever no receipt carries its id, but migrated
     * payments often have no term link at all - the old system recorded the money
     * without tying it to a schedule row - and others sit against a sibling term.
     * Judging each term on its own therefore invents receipts for money the
     * agreement has already received, and counts the payment twice.
     *
     * So the ceiling is set per agreement: what its terms say has been paid, less
     * what it has actually received. An agreement already holding as much cash as
     * its terms claim gets nothing, however the individual rows are attributed.
     *
     * @var array<string,float>
     */
    private array $agreementShortfall = [];

    private function capToAgreementShortfall(object $term, float $missingAmount, string $kind): float
    {
        if ($missingAmount <= 0) {
            return $missingAmount;
        }

        $rentOutId = (int) $term->rent_out_id;
        $key = $kind.':'.$rentOutId;

        if (! array_key_exists($key, $this->agreementShortfall)) {
            $this->agreementShortfall[$key] = $this->shortfallFor($rentOutId, $kind);
        }

        $allowed = round(min($missingAmount, $this->agreementShortfall[$key]), 2);
        $this->agreementShortfall[$key] -= $allowed;

        return max($allowed, 0);
    }

    private function shortfallFor(int $rentOutId, string $kind): float
    {
        if ($kind === 'utility') {
            $paid = (float) RentOutUtilityTerm::query()->where('rent_out_id', $rentOutId)->sum('paid');
            $received = (float) RentOutTransaction::query()
                ->where('rent_out_id', $rentOutId)
                ->where('credit', '>', 0)
                ->where('source', 'UtilityTerm')
                ->sum('credit');

            return round(max($paid - $received, 0), 2);
        }

        $paid = (float) RentOutPaymentTerm::query()->where('rent_out_id', $rentOutId)->sum('paid');
        // Rent receipts are those booked against a term plus the unattributed
        // ones, which in the source were rent collections with no schedule link.
        $received = (float) RentOutTransaction::query()
            ->where('rent_out_id', $rentOutId)
            ->where('credit', '>', 0)
            ->where(fn ($q) => $q->where('source', 'PaymentTerm')->orWhereNull('model'))
            ->sum('credit');

        return round(max($paid - $received, 0), 2);
    }

    private function resolveMissingAmount(object $term, string $model): float
    {
        $existingAmount = (float) RentOutTransaction::query()
            ->where('model', $model)
            ->where('model_id', $term->id)
            ->sum('credit');

        return round(max(((float) $term->paid) - $existingAmount, 0), 2);
    }

    private function resolvePaymentMethodId(int $tenantId, mixed $paymentMode): ?int
    {
        if (is_numeric($paymentMode) && Account::query()->where('tenant_id', $tenantId)->whereKey((int) $paymentMode)->exists()) {
            return (int) $paymentMode;
        }

        return $this->resolveDefaultPaymentMethodId($tenantId);
    }

    private function resolveDefaultPaymentMethodId(int $tenantId): ?int
    {
        return Account::query()
            ->where('tenant_id', $tenantId)
            ->where('slug', 'cash')
            ->where('is_locked', 1)
            ->value('id');
    }
}
