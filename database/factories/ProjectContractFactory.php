<?php

namespace Database\Factories;

use App\Enums\ProjectContract\ContractStatus;
use App\Enums\ProjectContract\PaymentFrequency;
use App\Models\ProjectContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Needs a resolved tenant and an existing vendor account: pass `account_id`
 * and `branch_id`, or set them with state().
 *
 * @extends Factory<ProjectContract>
 */
class ProjectContractFactory extends Factory
{
    protected $model = ProjectContract::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = today()->subMonth();

        return [
            'reference_no' => 'PC-'.$this->faker->unique()->numerify('9####'),
            'project_name' => $this->faker->streetName().' Fit-out',
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addYear()->subDay()->toDateString(),
            'no_of_terms' => 5,
            'payment_frequency' => PaymentFrequency::Milestone,
            'contract_amount' => 100000,
            'status' => ContractStatus::Draft,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => ContractStatus::Active, 'approved_at' => now()]);
    }
}
