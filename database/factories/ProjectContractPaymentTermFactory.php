<?php

namespace Database\Factories;

use App\Models\ProjectContractPaymentTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Pass `project_contract_id` and `branch_id`.
 *
 * @extends Factory<ProjectContractPaymentTerm>
 */
class ProjectContractPaymentTermFactory extends Factory
{
    protected $model = ProjectContractPaymentTerm::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => $this->faker->randomElement(['Mobilisation', 'Structural works', 'Finishing works']).' '.$this->faker->numberBetween(10, 30).'%',
            'due_date' => today()->addMonth()->toDateString(),
            'amount' => 20000,
            'paid' => 0,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
