<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * `code` and `subdomain` are both unique, so they are derived from one
     * random token rather than from a name faker can repeat within a run.
     */
    public function definition(): array
    {
        $slug = Str::lower(Str::random(12));

        return [
            'name' => fake()->company(),
            'code' => $slug,
            'subdomain' => $slug,
            'domain' => null,
            'is_active' => true,
            'description' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
