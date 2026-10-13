<?php

namespace App\Actions\ProjectContract\PaymentTerm;

use App\Models\ProjectContract;

/** The soft limits a contract's terms may cross: they warn, they never block. */
final class TermWarnings
{
    /**
     * @return array<int, string>
     */
    public static function for(ProjectContract $contract): array
    {
        $warnings = [];
        $scheduled = $contract->scheduledTotal();
        $over = round($scheduled - (float) $contract->contract_amount, 2);
        if ($over > 0) {
            $warnings[] = 'Payment terms now total '.number_format($over, 2).' more than the contract amount. Extend the contract if the amount has grown.';
        }

        $count = $contract->terms()->count();
        if ($count > $contract->no_of_terms) {
            $warnings[] = "This contract now has {$count} terms; {$contract->no_of_terms} were planned.";
        }

        return $warnings;
    }
}
