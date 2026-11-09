<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('CODE####')),
            'title' => '10% off',
            'discount_type' => Coupon::TYPE_PERCENT,
            'discount_value' => 10,
            'min_bag_total' => 0,
            'applies_to' => Coupon::SCOPE_ALL,
            'starts_on' => now()->subDay()->toDateString(),
            'ends_on' => null,
            'is_searchable' => true,
            'is_active' => true,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function fixed(float $amount): static
    {
        return $this->state(['discount_type' => Coupon::TYPE_FIXED, 'discount_value' => $amount, 'title' => "{$amount} off"]);
    }

    public function private(): static
    {
        return $this->state(['is_searchable' => false]);
    }

    public function paused(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function expired(): static
    {
        return $this->state(['starts_on' => now()->subDays(10)->toDateString(), 'ends_on' => now()->subDay()->toDateString()]);
    }

    public function scheduled(): static
    {
        return $this->state(['starts_on' => now()->addDays(5)->toDateString()]);
    }
}
