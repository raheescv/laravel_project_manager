<?php

namespace Database\Factories;

use App\Models\ProductOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductOffer>
 */
class ProductOfferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'product',
            'name' => fake()->words(2, true).' Sale',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => ['status' => 'disabled']);
    }
}
