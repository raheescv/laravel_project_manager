<?php

namespace Database\Factories;

use App\Models\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketStatus>
 */
class TicketStatusFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'key' => strtolower($name),
            'name' => ucfirst($name),
            'color' => '#9787c4',
            'icon' => 'fa-flag',
            'sort_order' => 10,
        ];
    }
}
