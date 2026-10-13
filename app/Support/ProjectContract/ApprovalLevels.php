<?php

namespace App\Support\ProjectContract;

use App\Models\Configuration;

/**
 * Who signs off a project contract, an extension or a security settlement.
 *
 * There is no settings screen: the levels live in the tenant's
 * `project_contract_approval_levels` configuration as JSON and fall back to
 * the defaults below, so they can be changed later without code.
 */
final class ApprovalLevels
{
    public const CONFIG_KEY = 'project_contract_approval_levels';

    /**
     * Level 1 is the Project Manager role and always applies; level 2 is the
     * Admin role once the amount is above QAR 50,000.
     *
     * @return array<int, array{level: int, role: string, min_amount: float}>
     */
    public static function defaults(): array
    {
        return [
            ['level' => 1, 'role' => 'Project Manager', 'min_amount' => 0],
            ['level' => 2, 'role' => 'Admin', 'min_amount' => 50000],
        ];
    }

    /**
     * @return array<int, array{level: int, role: string, min_amount: float}>
     */
    public static function all(): array
    {
        $saved = json_decode((string) Configuration::where('key', self::CONFIG_KEY)->value('value'), true);
        $levels = is_array($saved) && $saved ? $saved : self::defaults();

        return collect($levels)
            ->filter(fn ($level): bool => is_array($level) && filled($level['role'] ?? null))
            ->map(fn (array $level, int $index): array => [
                'level' => (int) ($level['level'] ?? $index + 1),
                'role' => (string) $level['role'],
                'min_amount' => (float) ($level['min_amount'] ?? 0),
            ])
            ->sortBy('level')
            ->values()
            ->all();
    }

    /**
     * The levels a submission of this amount must pass, renumbered 1..n in order.
     *
     * @return array<int, array{level: int, role: string, min_amount: float}>
     */
    public static function for(float $amount): array
    {
        $levels = collect(self::all())->filter(fn (array $level): bool => $amount > $level['min_amount'] || $level['min_amount'] <= 0)->values();

        return $levels->map(fn (array $level, int $index): array => ['level' => $index + 1] + $level)->all();
    }
}
