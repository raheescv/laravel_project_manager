<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TenantPayment>
 */
class TenantPaymentFactory extends Factory
{
    protected $model = TenantPayment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'paid_on' => now()->toDateString(),
            'type' => 'amc',
            'amount' => fake()->randomFloat(2, 100, 5000),
            'method' => 'Cash',
            'reference' => null,
            'note' => null,
        ];
    }
}
